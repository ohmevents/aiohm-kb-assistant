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
        $this->settings = AIOHM_KB_Assistant::get_settings();
    }
    
    public static function init() {
        $instance = new self();
        
        // Hook into ARMember events
        add_action('arm_after_user_plan_change', array($instance, 'sync_user_plan_change'), 10, 3);
        add_action('arm_after_user_register', array($instance, 'sync_new_user'), 10, 2);
        add_action('arm_after_user_plan_cancel', array($instance, 'sync_plan_cancellation'), 10, 2);
        
        // Add AJAX handlers (bulk sync already exists in Core_Init now)
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
            
            AIOHM_KB_Assistant::log("Synced user {$user_id} plan change: {$action} for plan {$plan_id}");
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('ARMember sync error: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Sync new user registration
     */
    public function sync_new_user($user_id, $posted_data) {
        try {
            $user_data = $this->get_armember_user_data($user_id);
            $this->create_user_knowledge_profile($user_id, $user_data);
            
            AIOHM_KB_Assistant::log("Created knowledge profile for new user {$user_id}");
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('New user sync error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Manually trigger sync for a specific user.
     * This method can be called via AJAX for on-demand synchronization.
     */
    public function sync_user_profile_on_demand($user_id) {
        try {
            $user_data = $this->get_armember_user_data($user_id);
            $this->update_user_knowledge_access($user_id, $user_data);
            AIOHM_KB_Assistant::log("Manually synced ARMember profile for user {$user_id}.", 'info');
        } catch (Exception $e) {
            throw new Exception("Failed to sync ARMember profile for user {$user_id}: " . $e->getMessage());
        }
    }

    /**
     * Syncs user on plan cancellation.
     */
    public function sync_plan_cancellation($user_id, $plan_id) {
        try {
            // After cancellation, re-evaluate user's access based on remaining plans (if any)
            $user_data = $this->get_armember_user_data($user_id);
            $this->update_user_knowledge_access($user_id, $user_data);
            AIOHM_KB_Assistant::log("Synced user {$user_id} plan cancellation for plan {$plan_id}", 'info');
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('ARMember plan cancellation sync error: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Get ARMember user data either from local plugin or from aiohm.app API.
     * @param int $user_id The local WordPress user ID.
     * @return array Containing user's membership data.
     * @throws Exception If no ARMember source is available or API call fails.
     */
    private function get_armember_user_data($user_id) {
        global $wpdb;
        
        // Option 1: Integrate with local ARMember plugin
        if (class_exists('ARMemberLite')) {
            AIOHM_KB_Assistant::log("Fetching ARMember data from local plugin for user {$user_id}.", 'info');
            // Get user's current plans
            $user_plans = get_user_meta($user_id, 'arm_user_plan_ids', true);
            $user_plans = !empty($user_plans) ? (array)$user_plans : array();
            
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
        
        // Option 2: Fetch data from aiohm.app API if local ARMember is not active
        $settings = AIOHM_KB_Assistant::get_settings();
        $aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';
        $personal_api_key_set = !empty($settings['personal_api_key'] ?? ''); // Check general aiohm.app connection

        if ($personal_api_key_set && !empty($aiohm_app_arm_user_id)) {
            AIOHM_KB_Assistant::log("Fetching ARMember data from aiohm.app API for user {$user_id} (remote ID: {$aiohm_app_arm_user_id}).", 'info');
            $api_client = new AIOHM_App_API_Client();
            
            $remote_member_details = $api_client->get_member_details($aiohm_app_arm_user_id);
            if (is_wp_error($remote_member_details)) {
                throw new Exception("API Error fetching remote member details: " . $remote_member_details->get_error_message());
            }

            $remote_member_memberships = $api_client->get_member_memberships($aiohm_app_arm_user_id);
            if (is_wp_error($remote_member_memberships)) {
                throw new Exception("API Error fetching remote member memberships: " . $remote_member_memberships->get_error_message());
            }

            // Map remote data to local profile format
            $plans = [];
            $access_level = 'basic';
            if (!empty($remote_member_memberships['plans']) && is_array($remote_member_memberships['plans'])) {
                foreach ($remote_member_memberships['plans'] as $remote_plan) {
                    $plan_type = $remote_plan['plan_subscription_type'] ?? ($remote_plan['plan_type'] ?? 'free_plan'); // Adjust key based on actual API response
                    $plans[] = [
                        'plan_id' => $remote_plan['plan_id'],
                        'plan_name' => $remote_plan['plan_name'],
                        'plan_type' => $plan_type,
                        'plan_status' => $remote_plan['plan_status'],
                        'access_level' => $this->determine_access_level(['arm_subscription_plan_type' => $plan_type]),
                        'plan_start_date' => $remote_plan['plan_start_date'] ?? null,
                        'plan_end_date' => $remote_plan['plan_end_date'] ?? null,
                    ];
                }
                $access_level = $this->calculate_user_access_level($plans);
            }

            return array(
                'user_id' => $user_id, // Local WP user ID
                'arm_user_id_on_aiohm_app' => $aiohm_app_arm_user_id, // Store the remote ID for reference
                'plans' => $plans,
                'payment_history' => $remote_member_details['payment_history'] ?? [], // Assuming API response contains this, adjust key if needed
                'access_level' => $access_level,
                'sync_timestamp' => current_time('mysql'),
                'source' => 'aiohm.app_api' // Indicate data source
            );
        }

        // If neither local ARMember nor aiohm.app API configuration is complete
        throw new Exception('ARMember integration not configured. Please install local ARMember or set AIOHM.app ARMember User ID and Personal API Key in settings.');
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
        
        $plan_type = $plan_data['arm_subscription_plan_type'] ?? $plan_data['plan_type'] ?? ''; // Handle different possible keys
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
            if (isset($access_hierarchy[$current_level]) && $access_hierarchy[$current_level] > $access_hierarchy[$highest_level]) {
                $highest_level = $current_level;
            }
        }
        
        return $highest_level;
    }
    
    /**
     * Get user payment history - local only for now. Remote would require separate API call.
     */
    private function get_user_payment_history($user_id) {
        global $wpdb;
        // This method is called only by local ARMember path in get_armember_user_data.
        // Remote payment history would need a specific API endpoint from aiohm.app.
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
            'updated_at' => current_time('mysql'),
            'source' => $user_data['source'] ?? 'local_armember', // Indicate source of data
        );
        // Include remote ARMember User ID if applicable
        if(isset($user_data['arm_user_id_on_aiohm_app'])) {
            $profile['arm_user_id_on_aiohm_app'] = $user_data['arm_user_id_on_aiohm_app'];
        }
        
        update_user_meta($user_id, 'aiohm_knowledge_profile', $profile);
        
        // Add to global profiles option for quick access
        $all_profiles = get_option('aiohm_user_profiles', array());
        $all_profiles[$user_id] = $profile;
        update_option('aiohm_user_profiles', $all_profiles);
    }
    
    /**
     * Update user knowledge access
     */
    public function update_user_knowledge_access($user_id, $user_data) {
        $existing_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        
        if ($existing_profile) {
            $existing_profile['access_level'] = $user_data['access_level'];
            $existing_profile['plans'] = $user_data['plans'];
            $existing_profile['updated_at'] = current_time('mysql');
            $existing_profile['source'] = $user_data['source'] ?? $existing_profile['source'] ?? 'local_armember'; // Update source
            if(isset($user_data['arm_user_id_on_aiohm_app'])) {
                $existing_profile['arm_user_id_on_aiohm_app'] = $user_data['arm_user_id_on_aiohm_app'];
            }
            // Payment history is not merged/updated here from remote data without specific API.
            // Keeping existing payment history if available, or setting empty if not.
            $existing_profile['payment_history'] = $user_data['payment_history'] ?? $existing_profile['payment_history'] ?? [];
            
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
        // This method can be integrated into Core_Init's bulk sync handler if needed,
        // but for now, it's not directly called by a button from the frontend context.
        // Keeping it here for reference/future expansion.
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