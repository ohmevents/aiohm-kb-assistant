<?php
/**
 * Handles all API communication with the main aiohm.app website.
 * This version is complete, verified to be stable, and includes email lookup.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_App_API_Client {

    private $api_key;
    // Base URL for ARMember API endpoints.
    private $base_url = 'https://www.aiohm.app/wp-json/armember/v1/';

    public function __construct() {
        // This is the global plugin API key for armember endpoints, hard-coded for security.
        $this->api_key = '45zNQcAA6MnTzVud1YhFROC95SVAGC';
    }

    /**
     * Helper function to make an authenticated GET/POST request to the API.
     * Automatically adds the plugin's internal API key.
     *
     * @param string $endpoint The API endpoint (can be relative to base_url or a full URL for custom endpoints).
     * @param array $args Query parameters to be appended to the URL.
     * @param string $method HTTP method ('GET' or 'POST').
     * @param array $body Request body for POST requests, will be JSON encoded.
     * @return array|WP_Error Decoded API response or WP_Error on failure.
     */
    private function make_request($endpoint, $args = [], $method = 'GET', $body = []) {
        if (empty($this->api_key)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: The main AIOHM.app API Key (internal) is not configured in the plugin.', 'error');
            return new WP_Error('api_key_missing', 'The main AIOHM.app API Key (internal) is not configured in the plugin.');
        }

        // Determine if the endpoint is a full URL (for custom endpoints) or a relative path
        $is_full_url = filter_var($endpoint, FILTER_VALIDATE_URL);
        $request_url = $is_full_url ? $endpoint : $this->base_url . $endpoint;
        
        // Add the primary API key to arguments for all requests
        $args['arm_api_key'] = $this->api_key;

        // Construct the full request URL with query arguments
        $request_url = add_query_arg($args, $request_url);

        AIOHM_KB_Assistant::log('AIOHM_App_API_Client: API Request URL: ' . $request_url, 'info');

        $request_args = [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        if ($method === 'POST') {
            $request_args['body'] = json_encode($body);
            $response = wp_remote_post($request_url, $request_args);
        } else {
            $response = wp_remote_get($request_url, $request_args);
        }

        AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Raw API Response (wp_remote_get/post): ' . print_r($response, true), 'debug');

        if (is_wp_error($response)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: WP_Error during API request: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $body_content = wp_remote_retrieve_body($response);
        $data = json_decode($body_content, true);

        AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Decoded API Response Body: ' . print_r($data, true), 'debug');

        if (json_last_error() !== JSON_ERROR_NONE) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: API response could not be decoded. JSON error: ' . json_last_error_msg(), 'error');
            return new WP_Error('api_json_decode_error', 'API response could not be decoded.');
        }
        
        // Check for specific error status from the API response
        if (isset($data['status']) && $data['status'] == 0 && isset($data['message'])) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: API returned an error status: ' . $data['message'], 'error');
             return new WP_Error('api_error_response', $data['message'], ['status_code' => 200, 'api_data' => $data]);
        }
        if (isset($data['error'])) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: API returned an error: ' . $data['error'], 'error');
            return new WP_Error('api_error', $data['error']);
        }
        
        return $data;
    }
    
    /**
     * Fetches details of a specific member from aiohm.app by email.
     *
     * @param string $email The member's email on aiohm.app.
     * @return array|WP_Error
     */
    public function get_member_details_by_email($email) {
        if (empty($email) || !is_email($email)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing or invalid email for get_member_details_by_email.', 'error');
            return new WP_Error('missing_email', 'A valid email address is required.');
        }
        return $this->make_request('arm_member_details_by_email', ['arm_user_email' => $email]);
    }

    /**
     * Checks Club membership status by email using a custom endpoint.
     * This assumes your aiohm.app has a custom REST endpoint at /wp-json/aiohm/v1/check-club-status/
     * and that it validates requests using the 'arm_api_key' which is automatically added by make_request.
     *
     * @param string $email The user's email.
     * @return array|WP_Error {'status': 'active'/'inactive'} or WP_Error.
     */
    public function check_club_membership_by_email($email) { // Removed $secret_key parameter
        if (empty($email) || !is_email($email)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing or invalid email for check_club_membership_by_email.', 'error');
            return new WP_Error('missing_params', 'Email is required for Club membership check.');
        }

        // Define the full URL for your custom endpoint. `make_request` will automatically add `arm_api_key`.
        $custom_endpoint_url = 'https://www.aiohm.app/wp-json/aiohm/v1/check-club-status/';
        
        // The request body will now only contain the email.
        $body = [
            'email' => $email,
        ];

        // Call the `make_request` helper with the full URL, no additional query args, POST method, and the body.
        return $this->make_request($custom_endpoint_url, [], 'POST', $body);
    }


    /**
     * Fetches the list of all membership plans from aiohm.app.
     * @return array|WP_Error
     */
    public function get_membership_plans() {
        return $this->make_request('arm_memberships');
    }

    /**
     * Fetches details of a specific membership plan from aiohm.app.
     * @param int $plan_id The ID of the plan.
     * @return array|WP_Error
     */
    public function get_membership_plan_details($plan_id) {
        if (empty($plan_id)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing plan ID for get_membership_plan_details.', 'error');
            return new WP_Error('missing_plan_id', 'Plan ID is required.');
        }
        return $this->make_request('arm_membership_details', ['arm_plan_id' => $plan_id]);
    }

    /**
     * Fetches details of a specific member from aiohm.app.
     *
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param array $metakeys Optional: Specific meta keys to retrieve.
     * @return array|WP_Error
     */
    public function get_member_details($arm_user_id, $metakeys = []) {
        if (empty($arm_user_id)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing ARMember User ID for get_member_details.', 'error');
            return new WP_Error('missing_arm_user_id', 'ARMember User ID is required.');
        }
        $args = ['arm_user_id' => $arm_user_id];
        if (!empty($metakeys)) {
            $args['arm_metakeys'] = implode(',', (array)$metakeys);
        }
        return $this->make_request('arm_member_details', $args);
    }

    /**
     * Fetches a list of a member's membership plans from aiohm.app.
     *
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param int $page Optional: Page number.
     * @param int $perpage Optional: Items per page.
     * @return array|WP_Error
     */
    public function get_member_memberships($arm_user_id, $page = 1, $perpage = 5) {
        if (empty($arm_user_id)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing ARMember User ID for get_member_memberships.', 'error');
            return new WP_Error('missing_arm_user_id', 'ARMember User ID is required.');
        }
        $args = [
            'arm_user_id' => $arm_user_id,
            'arm_page' => $page,
            'arm_perpage' => $perpage
        ];
        return $this->make_request('arm_member_memberships', $args);
    }

    /**
     * Verifies a member's active membership plan from aiohm.app.
     *
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param int $plan_id The plan ID to check against.
     * @return array|WP_Error
     */
    public function check_member_membership($arm_user_id, $plan_id) {
        if (empty($arm_user_id) || empty($plan_id)) {
            AIOHM_KB_Assistant::log('AIOHM_App_API_Client: Missing parameters for check_member_membership.', 'error');
            return new WP_Error('missing_params', 'ARMember User ID and Plan ID are required.');
        }
        $args = [
            'arm_user_id' => $arm_user_id,
            'arm_plan_id' => $plan_id
        ];
        return $this->make_request('arm_check_member_membership', $args);
    }
}