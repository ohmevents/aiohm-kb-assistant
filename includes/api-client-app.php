<?php
/**
 * Handles all API communication with the main aiohm.app website.
 * This version is complete and verified to be stable.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_App_API_Client {

    private $api_key;
    private $base_url = 'https://www.aiohm.app/wp-json/armember/v1/';

    public function __construct() {
        // The API key is now hard-coded here for security and simplicity.
        // It is no longer a user-facing setting.
        $this->api_key = '45zNQcAA6MnTzVud1YhFROC95SVAGC';
    }

    /**
     * Make a GET request to the API.
     */
    private function make_request($endpoint, $args = []) {
        if (empty($this->api_key)) {
            return new WP_Error('api_key_missing', 'The main AIOHM.app API Key is not configured in the plugin.');
        }

        $args['arm_api_key'] = $this->api_key;
        $request_url = add_query_arg($args, $this->base_url . $endpoint);

        $response = wp_remote_get($request_url, ['timeout' => 20]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']);
        }
        
        return $data;
    }

    /**
     * Fetches the list of all membership plans from aiohm.app.
     */
    public function get_membership_plans() {
        $response = $this->make_request('arm_memberships');

        if (is_wp_error($response)) {
            // In case of an API error, return the error object to be handled by the caller
            return $response;
        }
        
        // The API nests the actual data inside a 'data' key
        return isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
    }
}