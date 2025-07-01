<?php
defined('ABSPATH') || exit;

function aiohm_kb_enqueue_assets() {
    wp_enqueue_style('aiohm-chat-style', plugin_dir_url(__FILE__) . '../assets/css/aiohm-chat.css', [], '1.0');
    wp_enqueue_script('aiohm-chat-script', plugin_dir_url(__FILE__) . '../assets/js/aiohm-chat.js', ['jquery'], '1.0', true);

    wp_localize_script('aiohm-chat-script', 'aiohmChatAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('aiohm_chat_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'aiohm_kb_enqueue_assets');

function aiohm_kb_handle_chat_request() {
    check_ajax_referer('aiohm_chat_nonce', 'nonce');

    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    if (empty($query)) {
        wp_send_json_error('Empty query');
    }

    require_once plugin_dir_path(__FILE__) . 'ai-gpt-client.php';
    $response = aiohm_kb_get_assistant_response($query);

    wp_send_json_success(['reply' => $response]);
}
add_action('wp_ajax_aiohm_chat_send', 'aiohm_kb_handle_chat_request');
add_action('wp_ajax_nopriv_aiohm_chat_send', 'aiohm_kb_handle_chat_request');
