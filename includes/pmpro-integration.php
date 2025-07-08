<?php
/**
 * Paid Memberships Pro Integration for AIOHM Knowledge Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_PMP_Integration {

    public static function init() {
        // This function is reserved for any future hooks.
    }

    /**
     * Helper function to check if the current user has Club access by calling the aiohm.app API.
     *
     * @return bool True if user has Club access, false otherwise.
     */
    public static function aiohm_user_has_club_access() {
        $settings = AIOHM_KB_Assistant::get_settings();
        $user_email = $settings['aiohm_app_email'] ?? '';

        // User must have an email set in the settings to check their membership.
        if (empty($user_email) || !is_email($user_email) || !class_exists('AIOHM_App_API_Client')) {
            AIOHM_KB_Assistant::log('AIOHM_KB_PMP_Integration::aiohm_user_has_club_access: Missing user email or API Client.', 'debug');
            return false;
        }

        // Use a static variable to cache the result for the duration of the page load.
        static $club_access_status = null;
        if ($club_access_status !== null) {
            return $club_access_status;
        }

        $api_client = new AIOHM_App_API_Client();
        
        AIOHM_KB_Assistant::log("AIOHM_KB_PMP_Integration: Checking club membership for email {$user_email}.", 'debug');
        
        $result = $api_client->check_club_access_by_email($user_email);

        if (!is_wp_error($result) && isset($result['has_access']) && $result['has_access'] === true) {
            $club_access_status = true;
            AIOHM_KB_Assistant::log('AIOHM_KB_PMP_Integration: Club access granted.', 'info');
            return true;
        } else {
            $club_access_status = false;
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'No active club plan found.';
            AIOHM_KB_Assistant::log('AIOHM_KB_PMP_Integration: ' . $error_message, 'info');
            return false;
        }
    }
}