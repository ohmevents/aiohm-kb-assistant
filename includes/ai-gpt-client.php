<?php
defined('ABSPATH') || exit;

// Core GPT Request Handler
function aiohm_get_ai_response($prompt) {
    $api_key = get_option('aiohm_kb_api_key');
    if (!$api_key || !$prompt) return false;

    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $payload = [
        'model' => 'gpt-4',  // You can change this to gpt-3.5-turbo or Claude/Mistral endpoints
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
            ['role' => 'user', 'content' => sanitize_text_field($prompt)],
        ],
        'temperature' => 0.7,
    ];

    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return 'API error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['message']['content'] ?? 'No response from AI.';
}

// AJAX Hook
function aiohm_handle_chat_ajax() {
    check_ajax_referer('aiohm_chat_nonce', 'nonce');

    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    if (empty($query)) {
        wp_send_json_error(['message' => 'Empty prompt']);
    }

    $reply = aiohm_get_ai_response($query);
    wp_send_json_success(['reply' => $reply]);
}
add_action('wp_ajax_aiohm_chat_send', 'aiohm_handle_chat_ajax');
add_action('wp_ajax_nopriv_aiohm_chat_send', 'aiohm_handle_chat_ajax');
