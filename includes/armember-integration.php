<?php
/**
 * ARMember Integration for AIOHM Knowledge Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_ARMember_Integration {
    
    private $rag_engine;
    private $settings;
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
        $this->settings = AIOHM_KB_Core_Init::get_settings();
    }
    
    public static function init() {
        $instance = new self();
        
        // Hook into ARMember events
        add_action('arm_after_user_plan_change', array($instance, 'sync_user_plan_change'), 10, 3);
        add_action('arm_after_user_register', array($instance, 'sync_new_user'), 10, 2);
        add_action('arm_after_user_plan_cancel', array($instance, 'sync_plan_cancellation'), 10, 2);
        
        // Add AJAX handlers
        add_action('wp_ajax_aiohm_sync_armember_users', array($instance, 'bulk_sync_users'));
        add_action('wp_ajax_aiohm_get_user_access_level', array($instance, 'get_user_access_level'));
        
        // Filter chat responses based on user access
        add_filter('aiohm_filter_context_by_access', array($instance, 'filter_context_by_user_access'), 10, 2);
    }
    
    /**
     * Sync user when their plan changes
     */
    public function sync_user_plan_change($user_id, $plan_id, $action) {
        try {
            $user_data = $this->get_armember_user_data($user_id);
            $this->update_user_knowledge_access($user_id, $user_data);
            
            AIOHM_KB_Core_Init::log("Synced user {$user_id} plan change: {$action} for plan {$plan_id}");
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('ARMember sync error: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Sync new user registration
     */
    public function sync_new_user($user_id, $posted_data) {
        try {
            $user_data = $this->get_armember_user_data($user_id);
            $this->create_user_knowledge_profile($user_id, $user_data);
            
            AIOHM_KB_Core_Init::log("Created knowledge profile for new user {$user_id}");
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('New user sync error: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Get ARMember user data via API
     */
    private function get_armember_user_data($user_id) {
        global $wpdb, $ARMemberLite;
        
        if (!class_exists('ARMemberLite')) {
            throw new Exception('ARMember plugin not found');
        }
        
        // Get user's current plans
        $user_plans = get_user_meta($user_id, 'arm_user_plan_ids', true);
        $user_plans = !empty($user_plans) ? $user_plans : array();
        
        // Get plan details
        $plan_details = array();
        if (!empty($user_plans)) {
            foreach ($user_plans as $plan_id) {
                $plan_data = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}arm_subscription_plans WHERE arm_subscription_plan_id = %d",
                        $plan_id
                    ),
                    ARRAY_A
                );
                
                if ($plan_data) {
                    $plan_details[] = array(
                        'plan_id' => $plan_id,
                        'plan_name' => $plan_data['arm_subscription_plan_name'],
                        'plan_type' => $plan_data['arm_subscription_plan_type'],
                        'plan_status' => $plan_data['arm_subscription_plan_status'],
                        'access_level' => $this->determine_access_level($plan_data)
                    );
                }
            }
        }
        
        // Get user's payment history
        $payment_history = $this->get_user_payment_history($user_id);
        
        return array(
            'user_id' => $user_id,
            'plans' => $plan_details,
            'payment_history' => $payment_history,
            'access_level' => $this->calculate_user_access_level($plan_details),
            'sync_timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Determine access level from plan data
     */
    private function determine_access_level($plan_data) {
        // Map plan types to access levels
        $access_mapping = array(
            'free_plan' => 'basic',
            'paid_finite' => 'premium',
            'paid_infinite' => 'premium_plus',
            'recurring' => 'premium_plus'
        );
        
        $plan_type = $plan_data['arm_subscription_plan_type'];
        return isset($access_mapping[$plan_type]) ? $access_mapping[$plan_type] : 'basic';
    }
    
    /**
     * Calculate overall user access level
     */
    private function calculate_user_access_level($plan_details) {
        if (empty($plan_details)) {
            return 'basic';
        }
        
        // Hierarchy: basic < premium < premium_plus
        $access_hierarchy = array('basic' => 1, 'premium' => 2, 'premium_plus' => 3);
        $highest_level = 'basic';
        
        foreach ($plan_details as $plan) {
            $current_level = $plan['access_level'];
            if ($access_hierarchy[$current_level] > $access_hierarchy[$highest_level]) {
                $highest_level = $current_level;
            }
        }
        
        return $highest_level;
    }
    
    /**
     * Get user payment history
     */
    private function get_user_payment_history($user_id) {
        global $wpdb;
        
        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}arm_payment_log WHERE arm_user_id = %d ORDER BY arm_created_date DESC LIMIT 10",
                $user_id
            ),
            ARRAY_A
        );
        
        return $payments ? $payments : array();
    }
    
    /**
     * Create user knowledge profile
     */
    private function create_user_knowledge_profile($user_id, $user_data) {
        $profile = array(
            'user_id' => $user_id,
            'access_level' => $user_data['access_level'],
            'plans' => $user_data['plans'],
            'preferences' => array(),
            'chat_history' => array(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'aiohm_knowledge_profile', $profile);
        
        // Add to global profiles option for quick access
        $all_profiles = get_option('aiohm_user_profiles', array());
        $all_profiles[$user_id] = $profile;
        update_option('aiohm_user_profiles', $all_profiles);
    }
    
    /**
     * Update user knowledge access
     */
    private function update_user_knowledge_access($user_id, $user_data) {
        $existing_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        
        if ($existing_profile) {
            $existing_profile['access_level'] = $user_data['access_level'];
            $existing_profile['plans'] = $user_data['plans'];
            $existing_profile['updated_at'] = current_time('mysql');
            
            update_user_meta($user_id, 'aiohm_knowledge_profile', $existing_profile);
        } else {
            $this->create_user_knowledge_profile($user_id, $user_data);
        }
    }
    
    /**
     * Filter context based on user access level
     */
    public function filter_context_by_user_access($context, $user_id) {
        if (!$user_id) {
            return $context; // Guest access - return basic content
        }
        
        $user_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        $access_level = $user_profile ? $user_profile['access_level'] : 'basic';
        
        // Filter context based on access level
        $filtered_context = array();
        foreach ($context as $item) {
            $required_level = isset($item['metadata']['access_level']) ? $item['metadata']['access_level'] : 'basic';
            
            if ($this->user_has_access($access_level, $required_level)) {
                $filtered_context[] = $item;
            }
        }
        
        return $filtered_context;
    }
    
    /**
     * Check if user has required access level
     */
    private function user_has_access($user_level, $required_level) {
        $access_hierarchy = array('basic' => 1, 'premium' => 2, 'premium_plus' => 3);
        
        $user_level_value = isset($access_hierarchy[$user_level]) ? $access_hierarchy[$user_level] : 1;
        $required_level_value = isset($access_hierarchy[$required_level]) ? $access_hierarchy[$required_level] : 1;
        
        return $user_level_value >= $required_level_value;
    }
    
    /**
     * Bulk sync all ARMember users
     */
    public function bulk_sync_users() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        try {
            global $wpdb;
            
            // Get all users with ARMember data
            $users_with_plans = $wpdb->get_results(
                "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'arm_user_plan_ids'",
                ARRAY_A
            );
            
            $synced_count = 0;
            foreach ($users_with_plans as $user_row) {
                $user_id = $user_row['user_id'];
                $user_data = $this->get_armember_user_data($user_id);
                $this->update_user_knowledge_access($user_id, $user_data);
                $synced_count++;
            }
            
            wp_send_json_success(array(
                'message' => "Successfully synced {$synced_count} users",
                'synced_count' => $synced_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user access level via AJAX
     */
    public function get_user_access_level() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_success(array('access_level' => 'basic'));
        }
        
        $user_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        $access_level = $user_profile ? $user_profile['access_level'] : 'basic';
        
        wp_send_json_success(array(
            'access_level' => $access_level,
            'user_id' => $user_id
        ));
    }
    
    /**
     * Add content with access level metadata
     */
    public function add_content_with_access_level($content, $content_type, $title, $access_level = 'basic', $metadata = array()) {
        $metadata['access_level'] = $access_level;
        return $this->rag_engine->add_entry($content, $content_type, $title, $metadata);
    }
}