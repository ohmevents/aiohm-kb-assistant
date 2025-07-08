<?php
/**
 * Handles all API communication with the main aiohm.app website.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_App_API_Client {

    private $base_url = 'https://www.aiohm.app/wp-json/aiohm/v1/';

    public function __construct() {
    }

    private function make_request($endpoint, $args = []) {
        $request_url = $this->base_url . $endpoint;
        $request_url = add_query_arg($args, $request_url);

        $response = wp_remote_get($request_url, ['timeout' => 20]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body_content = wp_remote_retrieve_body($response);
        $data = json_decode($body_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_json_decode_error', 'API response could not be decoded.');
        }

        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']);
        }

        return $data;
    }

    /**
     * Gets all available membership details for a user by email.
     *
     * @param string $email The user's email address.
     * @return array|WP_Error
     */
    public function get_member_details_by_email($email) {
        if (empty($email) || !is_email($email)) {
            return new WP_Error('invalid_email', 'A valid email is required.');
        }

        return $this->make_request('get-member-details', ['email' => $email]);
    }
}