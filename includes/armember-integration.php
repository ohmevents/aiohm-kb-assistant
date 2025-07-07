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
        
        // Removed: Hooks into local ARMember events.
        // These hooks are only relevant if ARMember plugin is installed locally.
        // add_action('arm_after_user_plan_change', array($instance, 'sync_user_plan_change'), 10, 3);
        // add_action('arm_after_user_register', array($instance, 'sync_new_user'), 10, 2);
        // add_action('arm_after_user_plan_cancel', array($instance, 'sync_plan_cancellation'), 10, 2);
        
        // Add AJAX handlers
        add_action('wp_ajax_aiohm_get_user_access_level', array($instance, 'get_user_access_level'));
        
        // Filter chat responses based on user access
        add_filter('aiohm_filter_context_by_access', array($instance, 'filter_context_by_user_access'), 10, 2);
    }
    
    // Removed: sync_user_plan_change method, as it was tied to local ARMember hooks.
    // public function sync_user_plan_change($user_id, $plan_id, $action) {
    //     try {
    //         $user_data = $this->get_armember_user_data($user_id);
    //         $this->update_user_knowledge_access($user_id, $user_data);
    //         AIOHM_KB_Assistant::log("Synced user {$user_id} plan change: {$action} for plan {$plan_id}");
    //     } catch (Exception $e) {
    //         AIOHM_KB_Assistant::log('ARMember sync error: ' . $e->getMessage(), 'error');
    //     }
    // }
    
    // Removed: sync_new_user method, as it was tied to local ARMember hooks.
    // public function sync_new_user($user_id, $posted_data) {
    //     try {
    //         $user_data = $this->get_armember_user_data($user_id);
    //         $this->create_user_knowledge_profile($user_id, $user_data);
    //         AIOHM_KB_Assistant::log("Created knowledge profile for new user {$user_id}");
    //     } catch (Exception $e) {
    //         AIOHM_KB_Assistant::log('New user sync error: ' . $e->getMessage(), 'error');
    //     }
    // }

    /**
     * Manually trigger sync for a specific user.
     * This method can be called via AJAX for on-demand synchronization.
     */
    public function sync_user_profile_on_demand($user_id) {
        try {
            // This method now exclusively uses the aiohm.app API path.
            $user_data = $this->get_armember_user_data($user_id);
            $this->update_user_knowledge_access($user_id, $user_data);
            AIOHM_KB_Assistant::log("Manually synced ARMember profile for user {$user_id} via aiohm.app API.", 'info');
        } catch (Exception $e) {
            throw new Exception("Failed to sync ARMember profile for user {$user_id}: " . $e->getMessage());
        }
    }

    // Removed: sync_plan_cancellation method, as it was tied to local ARMember hooks.
    // public function sync_plan_cancellation($user_id, $plan_id) {
    //     try {
    //         $user_data = $this->get_armember_user_data($user_id);
    //         $this->update_user_knowledge_access($user_id, $user_data);
    //         AIOHM_KB_Assistant::log("Synced user {$user_id} plan cancellation for plan {$plan_id} via aiohm.app API", 'info');
    //     } catch (Exception $e) {
    //         AIOHM_KB_Assistant::log('ARMember plan cancellation sync error: ' . $e->getMessage(), 'error');
    //     }
    // }
    
    /**
     * Get ARMember user data ONLY from aiohm.app API.
     * The local ARMember plugin detection has been removed.
     * @param int $user_id The local WordPress user ID.
     * @return array Containing user's membership data.
     * @throws Exception If aiohm.app API configuration is incomplete or API call fails.
     */
    private function get_armember_user_data($user_id) {
        global $wpdb;
        
        // Removed: Option 1: Integrate with local ARMember plugin
        // if (class_exists('ARMemberLite')) {
        //     AIOHM_KB_Assistant::log("Fetching ARMember data from local plugin for user {$user_id}.", 'info');
        //     // Get user's current plans
        //     $user_plans = get_user_meta($user_id, 'arm_user_plan_ids', true);
        //     $user_plans = !empty($user_plans) ? (array)$user_plans : array();
            
        //     // Get plan details
        //     $plan_details = array();
        //     if (!empty($user_plans)) {
        //         foreach ($user_plans as $plan_id) {
        //             $plan_data = $wpdb->get_row(
        //                 $wpdb->prepare(
        //                     "SELECT * FROM {$wpdb->prefix}arm_subscription_plans WHERE arm_subscription_plan_id = %d",
        //                     $plan_id
        //                 ),
        //                 ARRAY_A
        //             );
                    
        //             if ($plan_data) {
        //                 $plan_details[] = array(
        //                     'plan_id' => $plan_id,
        //                     'plan_name' => $plan_data['arm_subscription_plan_name'],
        //                     'plan_type' => $plan_data['arm_subscription_plan_type'],
        //                     'plan_status' => $plan_data['arm_subscription_plan_status'],
        //                     'access_level' => $this->determine_access_level($plan_data)
        //                 );
        //             }
        //         }
        //     }
            
        //     // Get user's payment history
        //     $payment_history = $this->get_user_payment_history($user_id);
            
        //     return array(
        //         'user_id' => $user_id,
        //         'plans' => $plan_details,
        //         'payment_history' => $payment_history,
        //         'access_level' => $this->calculate_user_access_level($plan_details),
        //         'sync_timestamp' => current_time('mysql')
        //     );
        // } 
        
        // This section (Option 2) now becomes the primary and only method for fetching ARMember data.
        $settings = AIOHM_KB_Assistant::get_settings();
        $aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';
        $personal_api_key_set = !empty($settings['personal_api_key'] ?? '');

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

        // If aiohm.app API configuration is not complete (and local ARMember check is removed)
        throw new Exception('AIOHM.app API integration for ARMember is not configured. Please set AIOHM.app ARMember User ID and Personal API Key in settings.');
    }
    
    // Removed: get_user_payment_history method, as it was only called by the local ARMember path.
    // private function get_user_payment_history($user_id) {
    //     global $wpdb;
    //     $payments = $wpdb->get_results(
    //         $wpdb->prepare(
    //             "SELECT * FROM {$wpdb->prefix}arm_payment_log WHERE arm_user_id = %d ORDER BY arm_created_date DESC LIMIT 10",
    //             $user_id
    //         ),
    //         ARRAY_A
    //     );
    //     return $payments ? $payments : array();
    // }
    
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
            'source' => $user_data['source'] ?? 'aiohm.app_api', // Ensure source is always aiohm.app_api
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
            $existing_profile['source'] = $user_data['source'] ?? 'aiohm.app_api'; // Ensure source is always aiohm.app_api
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
     * Bulk sync users (Now explicitly not applicable for local ARMember).
     * If you need a bulk sync from aiohm.app, a new function would be needed.
     */
    public function bulk_sync_users() {
        // The original implementation of bulk_sync_users was tied to local ARMember data.
        // Since the integration is now strictly with aiohm.app, this function's
        // behavior needs to be re-evaluated or removed if no corresponding
        // bulk sync mechanism exists or is desired from the aiohm.app API.
        AIOHM_KB_Assistant::log('Bulk sync users (ARMember Integration): Not applicable in aiohm.app API-only mode.', 'info');
        wp_send_json_error('Bulk sync not applicable in this configuration (aiohm.app API-only ARMember integration).');
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