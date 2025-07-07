// Existing includes/api-client-app.php
<?php
/**
 * Handles all API communication with the main aiohm.app website.
 * This version is complete and verified to be stable.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_App_API_Client {

    private $api_key;
    // Updated base_url to include the common v1 path
    private $base_url = 'https://www.aiohm.app/wp-json/armember/v1/';

    public function __construct() {
        // The API key is now hard-coded here for security and simplicity.
        // It is no longer a user-facing setting.
        // This is the global plugin API key for armember endpoints.
        $this->api_key = '45zNQcAA6MnTzVud1YhFROC95SVAGC';
    }

    /**
     * Helper function to make an authenticated GET request to the API.
     * @param string $endpoint The API endpoint relative to base_url.
     * @param array $args Query parameters.
     * @return array|WP_Error Decoded API response or WP_Error on failure.
     */
    private function make_request($endpoint, $args = []) {
        if (empty($this->api_key)) {
            return new WP_Error('api_key_missing', 'The main AIOHM.app API Key (internal) is not configured in the plugin.');
        }

        $args['arm_api_key'] = $this->api_key; // Use the fixed plugin API key for these endpoints
        $request_url = add_query_arg($args, $this->base_url . $endpoint);

        $response = wp_remote_get($request_url, ['timeout' => 20]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_json_decode_error', 'API response could not be decoded.');
        }
        
        // Check for specific error status from the API
        if (isset($data['status']) && $data['status'] == 0 && isset($data['message'])) {
             return new WP_Error('api_error_response', $data['message'], ['status_code' => 200, 'api_data' => $data]);
        }
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']);
        }
        
        return $data;
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
            return new WP_Error('missing_plan_id', 'Plan ID is required.');
        }
        return $this->make_request('arm_membership_details', ['arm_plan_id' => $plan_id]);
    }

    /**
     * Fetches details of a specific member from aiohm.app.
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param array $metakeys Optional: Specific meta keys to retrieve.
     * @return array|WP_Error
     */
    public function get_member_details($arm_user_id, $metakeys = []) {
        if (empty($arm_user_id)) {
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
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param int $page Optional: Page number.
     * @param int $perpage Optional: Items per page.
     * @return array|WP_Error
     */
    public function get_member_memberships($arm_user_id, $page = 1, $perpage = 5) {
        if (empty($arm_user_id)) {
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
     * @param int $arm_user_id The member's user ID on aiohm.app.
     * @param int $plan_id The plan ID to check against.
     * @return array|WP_Error
     */
    public function check_member_membership($arm_user_id, $plan_id) {
        if (empty($arm_user_id) || empty($plan_id)) {
            return new WP_Error('missing_params', 'ARMember User ID and Plan ID are required.');
        }
        $args = [
            'arm_user_id' => $arm_user_id,
            'arm_plan_id' => $plan_id
        ];
        return $this->make_request('arm_check_member_membership', $args);
    }

    // You can add other API methods from the table here as needed, e.g., check_coupon_code, add_plan_to_member, cancel_member_plan.
}