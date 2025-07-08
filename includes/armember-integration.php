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
        
        add_action('wp_ajax_aiohm_get_user_access_level', array($instance, 'get_user_access_level'));
        
        add_filter('aiohm_filter_context_by_access', array($instance, 'filter_context_by_user_access'), 10, 2);
    }
    
    /**
     * Manually trigger sync for a specific user.
     * This method can be called via AJAX for on-demand synchronization.
     * @param int $user_id The local WordPress user ID.
     * @throws Exception If synchronization fails.
     */
    public function sync_user_profile_on_demand($user_id) {
        try {
            $user_data = $this->get_armember_user_data($user_id);
            $this->update_user_knowledge_access($user_id, $user_data);
            AIOHM_KB_Assistant::log("Manually synced ARMember profile for user {$user_id} via aiohm.app API.", 'info');
        } catch (Exception $e) {
            throw new Exception("Failed to sync ARMember profile for user {$user_id}: " . $e->getMessage());
        }
    }

    /**
     * Get ARMember user data from aiohm.app API.
     * @param int $user_id The local WordPress user ID.
     * @return array Containing user's membership data.
     * @throws Exception If aiohm.app API configuration is incomplete or API call fails.
     */
    private function get_armember_user_data($user_id) {
        $settings = AIOHM_KB_Assistant::get_settings();
        $aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';
        $aiohm_app_email = $settings['aiohm_app_email'] ?? '';
        
        $has_identifier = !empty($aiohm_app_arm_user_id) || !empty($aiohm_app_email);

        if ($has_identifier) {
            $api_client = new AIOHM_App_API_Client();
            $remote_member_details = null;

            if (!empty($aiohm_app_arm_user_id)) {
                AIOHM_KB_Assistant::log("Fetching ARMember data from aiohm.app API using stored user ID {$aiohm_app_arm_user_id}.", 'info');
                $remote_member_details = $api_client->get_member_details($aiohm_app_arm_user_id);
            } elseif (!empty($aiohm_app_email)) {
                AIOHM_KB_Assistant::log("Fetching ARMember data from aiohm.app API using stored email {$aiohm_app_email}.", 'info');
                $email_lookup_result = $api_client->get_member_details_by_email($aiohm_app_email);
                if (!is_wp_error($email_lookup_result) && !empty($email_lookup_result['response']['result']['ID'])) {
                    $aiohm_app_arm_user_id = $email_lookup_result['response']['result']['ID'];
                    $new_settings = AIOHM_KB_Assistant::get_settings();
                    $new_settings['aiohm_app_arm_user_id'] = $aiohm_app_arm_user_id;
                    update_option('aiohm_kb_settings', $new_settings);
                    $remote_member_details = $api_client->get_member_details($aiohm_app_arm_user_id);
                } else {
                    AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration: Email lookup failed: ' . (is_wp_error($email_lookup_result) ? $email_lookup_result->get_error_message() : 'No user ID found for email.'), 'error');
                    throw new Exception('Email lookup failed for ARMember integration.');
                }
            } else {
                throw new Exception('AIOHM.app API integration for ARMember is not configured with either user ID or email.');
            }

            if (is_wp_error($remote_member_details)) {
                AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration: WP_Error fetching remote member details: ' . $remote_member_details->get_error_message(), 'error');
                throw new Exception("API Error fetching remote member details: " . $remote_member_details->get_error_message());
            }

            $remote_member_memberships = $api_client->get_member_memberships($aiohm_app_arm_user_id);
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration: Raw Membership API Response: ' . print_r($remote_member_memberships, true), 'debug');

            if (is_wp_error($remote_member_memberships)) {
                AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration: WP_Error fetching remote member memberships: ' . $remote_member_memberships->get_error_message(), 'error');
                throw new Exception("API Error fetching remote member memberships: " . $remote_member_memberships->get_error_message());
            }
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration: Decoded Membership Data: ' . print_r($remote_member_memberships, true), 'debug');
            
            $plans = [];
            $access_level = 'basic';
            if (!empty($remote_member_memberships['response']['result']['memberships']) && is_array($remote_member_memberships['response']['result']['memberships'])) {
                foreach ($remote_member_memberships['response']['result']['memberships'] as $remote_plan) {
                    $plan_type = $remote_plan['plan_subscription_type'] ?? ($remote_plan['plan_type'] ?? 'free_plan');
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
                'payment_history' => $remote_member_details['payment_history'] ?? [],
                'access_level' => $access_level,
                'sync_timestamp' => current_time('mysql'),
                'source' => 'aiohm.app_api'
            );
        }

        throw new Exception('AIOHM.app API integration for ARMember is not configured. Please set AIOHM.app Email on the License page.');
    }
    
    /**
     * Determine access level from plan data
     */
    private function determine_access_level($plan_data) {
        $access_mapping = array(
            'free_plan' => 'basic',
            'paid_finite' => 'premium',
            'paid_infinite' => 'premium_plus',
            'recurring' => 'premium_plus'
        );
        
        $plan_type = $plan_data['arm_subscription_plan_type'] ?? $plan_data['plan_type'] ?? '';
        return isset($access_mapping[$plan_type]) ? $access_mapping[$plan_type] : 'basic';
    }
    
    /**
     * Calculate overall user access level
     */
    private function calculate_user_access_level($plan_details) {
        if (empty($plan_details)) {
            return 'basic';
        }
        
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
            'source' => $user_data['source'] ?? 'aiohm.app_api',
        );
        if(isset($user_data['arm_user_id_on_aiohm_app'])) {
            $profile['arm_user_id_on_aiohm_app'] = $user_data['arm_user_id_on_aiohm_app'];
        }
        
        update_user_meta($user_id, 'aiohm_knowledge_profile', $profile);
        
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
            $existing_profile['source'] = $user_data['source'] ?? 'aiohm.app_api';
            if(isset($user_data['arm_user_id_on_aiohm_app'])) {
                $existing_profile['arm_user_id_on_aiohm_app'] = $user_data['arm_user_id_on_aiohm_app'];
            }
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
            return $context;
        }
        
        $user_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        $access_level = $user_profile ? $user_profile['access_level'] : 'basic';
        
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
     * Bulk sync users.
     */
    public function bulk_sync_users() {
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

    /**
     * Helper function to check if the current user has Club access.
     * This method fetches the stored ARMember User ID and checks against Club plan IDs.
     * It uses ARMember's built-in API endpoint for verification.
     * @return bool True if user has Club access, false otherwise.
     */
    public static function aiohm_user_has_club_access() {
        $settings = AIOHM_KB_Assistant::get_settings();
        $aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';
        $user_email = $settings['aiohm_app_email'] ?? ''; // Still need email for potential fallback lookup

        // Must have an ARMember User ID from aiohm.app to check membership
        if (empty($aiohm_app_arm_user_id) || !class_exists('AIOHM_App_API_Client')) {
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: Missing AIOHM App ARMember User ID or API Client.', 'debug');
            return false;
        }

        static $club_access_status = null;
        if ($club_access_status !== null) {
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: Returning cached status: ' . ($club_access_status ? 'active' : 'inactive'), 'debug');
            return $club_access_status;
        }

        $api_client = new AIOHM_App_API_Client();
        $club_plan_ids = ['3', '4']; // AIOHM CLUB Month & Year (from your screenshot)
        $has_active_club_plan = false;

        AIOHM_KB_Assistant::log("AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: Checking club membership for ARMember User ID {$aiohm_app_arm_user_id}.", 'debug');

        foreach ($club_plan_ids as $plan_id) {
            $result = $api_client->check_member_membership($aiohm_app_arm_user_id, $plan_id);

            if (!is_wp_error($result) && isset($result['response']['result']['status']) && $result['response']['result']['status'] === 'active') {
                $has_active_club_plan = true;
                break; // Found an active club plan, no need to check others
            }
             AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: Check for Plan ID ' . $plan_id . ' Result: ' . print_r($result, true), 'debug');
        }
        
        if ($has_active_club_plan) {
            $club_access_status = true;
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: Club access granted.', 'info');
            return true;
        } else {
            $club_access_status = false;
            AIOHM_KB_Assistant::log('AIOHM_KB_ARMember_Integration::aiohm_user_has_club_access: No active Club plan found for user.', 'info');
            return false;
        }
    }
}