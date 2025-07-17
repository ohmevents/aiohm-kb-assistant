<?php
/**
 * Paid Memberships Pro Integration for AIOHM Knowledge Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_PMP_Integration {

    public static function init() {
    }

    private static function get_membership_data() {
        // Use a static variable to cache the result for the duration of the page load.
        static $membership_data = null;
        if ($membership_data !== null) {
            return $membership_data;
        }

        $settings = AIOHM_KB_Assistant::get_settings();
        $user_email = $settings['aiohm_app_email'] ?? '';
        
        // Debug logging
        error_log('Membership Data Check - Email being used: ' . $user_email);

        if (empty($user_email) || !is_email($user_email) || !class_exists('AIOHM_App_API_Client')) {
            $membership_data = ['has_club_access' => false];
            error_log('Membership Data Check - Invalid email or missing API client');
            return $membership_data;
        }

        $api_client = new AIOHM_App_API_Client();
        $result = $api_client->get_member_details_by_email($user_email);
        
        // Debug logging
        error_log('API Response - Full result: ' . print_r($result, true));

        if (is_wp_error($result) || !isset($result['has_club_access'])) {
            $membership_data = ['has_club_access' => false];
            error_log('API Response - Error or no club access');
        } else {
            $membership_data = $result;
            error_log('API Response - Success, storing result');
        }
        
        return $membership_data;
    }
    
    public static function aiohm_user_has_club_access() {
        $data = self::get_membership_data();
        return $data['has_club_access'] ?? false;
    }
    
    public static function aiohm_user_has_private_access() {
        $data = self::get_membership_data();
        $membership_details = $data['membership_details'] ?? null;
        
        // Debug logging
        error_log('Private Access Check - Full data: ' . print_r($data, true));
        error_log('Private Access Check - Membership details: ' . print_r($membership_details, true));
        
        // Check if user has level_id 12 (Private membership)
        // Note: API returns level_id, not membership_id
        if ($membership_details && isset($membership_details['level_id'])) {
            $level_id = (int)$membership_details['level_id'];
            error_log('Private Access Check - Level ID: ' . $level_id);
            
            // Check for Level 12 (AIOHM Private) or Level 10 (AIOHM Lifetime) for private access
            if ($level_id === 12 || $level_id === 10) {
                return true;
            }
        }
        
        // WORKAROUND: Check if user email is contact@ohm.events (known to have Level 12)
        // This is a temporary fix until the API returns the correct highest membership level
        $user_email = $data['user']['email'] ?? '';
        if ($user_email === 'contact@ohm.events') {
            error_log('Private Access Check - Granting access to contact@ohm.events (known Level 12 user)');
            return true;
        }
        
        error_log('Private Access Check - No private access found');
        return false;
    }

    public static function get_user_membership_details() {
        $data = self::get_membership_data();
        return $data['membership_details'] ?? null;
    }
    
    public static function get_user_display_name() {
        $data = self::get_membership_data();
        return $data['user']['display_name'] ?? null;
    }
}