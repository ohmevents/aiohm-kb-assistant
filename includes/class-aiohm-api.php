<?php
class AIOHM_API {
    private $api_key;
    private $base_url;

    public function __construct() {
        $this->api_key = get_option('aiohm_app_api_key');
        $this->base_url = 'https://www.aiohm.app/wp-json/armember/v1/';
    }

    private function request($endpoint, $params = [], $method = 'GET') {
        $url = $this->base_url . $endpoint . '?arm_api_key=' . $this->api_key;
        if ($method === 'GET') {
            $url .= '&' . http_build_query($params);
            return wp_remote_get($url);
        } else {
            $params['arm_api_key'] = $this->api_key;
            return wp_remote_post($url, ['body' => $params]);
        }
    }

    public function get_membership_plans() {
        return $this->request('arm_memberships');
    }

    public function get_member_details($user_id) {
        return $this->request('arm_member_details', ['arm_user_id' => $user_id]);
    }

    public function add_member_membership($user_id, $plan_id) {
        return $this->request('arm_add_member_membership', [
            'arm_user_id' => $user_id,
            'arm_plan_id' => $plan_id
        ], 'POST');
    }
}