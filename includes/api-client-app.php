<?php
/**
 * AI GPT Client for handling API requests.
 *
 * *** UPDATED: Fix for 'Cannot declare class' fatal error by adding a class_exists check.
 * Includes new standalone AJAX functions for Muse Mode chat and Live Research. ***
 */
if (!defined('ABSPATH')) exit;

// =================================================================================
// AJAX ACTION HOOKS
// =================================================================================

add_action('wp_ajax_aiohm_private_chat', 'aiohm_handle_private_chat_ajax');
add_action('wp_ajax_aiohm_live_research', 'aiohm_handle_live_research_ajax');

/**
 * Handles the AJAX request for a standard private chat message.
 */
function aiohm_handle_private_chat_ajax() {
    check_ajax_referer('aiohm_muse_mode_nonce', 'nonce');
    $user_prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

    if (empty($user_prompt)) {
        wp_send_json_error(['message' => 'Prompt is empty.']);
    }

    $settings = AIOHM_KB_Assistant::get_settings();
    $muse_settings = $settings['muse_mode'] ?? [];
    $system_prompt = $muse_settings['system_prompt'] ?? 'You are Muse, a private brand assistant.';
    
    $ai_response = aiohm_send_to_gpt($user_prompt, $system_prompt);

    if ($ai_response) {
        wp_send_json_success(['message' => $ai_response]);
    } else {
        wp_send_json_error(['message' => 'Failed to get a response from the AI.']);
    }
}

/**
 * Handles the AJAX request for the "Research Online" feature.
 */
function aiohm_handle_live_research_ajax() {
    check_ajax_referer('aiohm_muse_mode_nonce', 'nonce');
    $url_to_research = isset($_POST['research_url']) ? esc_url_raw($_POST['research_url']) : '';

    if (empty($url_to_research)) {
        wp_send_json_error(['message' => 'No URL provided for research.']);
    }

    $content = aiohm_fetch_url_content($url_to_research);

    if ($content === false) {
        wp_send_json_error(['message' => 'Failed to retrieve content from the URL.']);
    }

    $truncated_content = substr($content, 0, 12000);
    $system_prompt = "You are a research analyst. Summarize the key information and latest updates from the following text scraped from a website.";
    
    $ai_response = aiohm_send_to_gpt($truncated_content, $system_prompt);

    if ($ai_response) {
        wp_send_json_success(['message' => $ai_response]);
    } else {
        wp_send_json_error(['message' => 'The AI failed to process the website content.']);
    }
}


/**
 * Fetches and cleans plain text content from a given URL.
 */
function aiohm_fetch_url_content($url) {
    $response = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }
    $body = wp_remote_retrieve_body($response);
    $text = strip_tags($body);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}


/**
 * Central function to send requests to the AI model.
 */
function aiohm_send_to_gpt($user_prompt, $system_prompt) {
    $settings = AIOHM_KB_Assistant::get_settings();
    $muse_settings = $settings['muse_mode'] ?? [];
    $api_key = $settings['openai_api_key'] ?? '';
    $model = $muse_settings['ai_model'] ?? 'gpt-4o';
    $temperature = floatval($muse_settings['temperature'] ?? 0.7);

    if (empty($api_key)) {
        return 'OpenAI API Key is not configured.';
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
        'body'    => json_encode([
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt],
            ],
            'max_tokens'  => 2000,
            'temperature' => $temperature,
        ]),
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return 'Failed to connect to AI service: ' . $response->get_error_message();
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['choices'][0]['message']['content'])) {
        return trim($response_body['choices'][0]['message']['content']);
    } elseif (isset($response_body['error']['message'])) {
        return 'AI API Error: ' . $response_body['error']['message'];
    }

    return false;
}


// =================================================================================
// ORIGINAL AIOHM_KB_AI_GPT_Client Class (with safety check)
// =================================================================================

if (!class_exists('AIOHM_KB_AI_GPT_Client')) :
    class AIOHM_KB_AI_GPT_Client {
        
        private $settings;
        private $openai_api_key;
        private $gemini_api_key;
        private $claude_api_key;

        public function __construct($settings = null) {
            if ($settings === null) {
                $this->settings = AIOHM_KB_Assistant::get_settings();
            } else {
                $this->settings = $settings;
            }
            $this->openai_api_key = $this->settings['openai_api_key'] ?? '';
            $this->gemini_api_key = $this->settings['gemini_api_key'] ?? '';
            $this->claude_api_key = $this->settings['claude_api_key'] ?? '';
        }
        
        private function sanitize_text_for_json($text) {
            if (is_string($text)) {
                return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
            return $text;
        }

        public function generate_embeddings($text) {
            if (empty($this->openai_api_key)) throw new Exception('OpenAI API key is required for embeddings.');
            $response = $this->make_http_request('https://api.openai.com/v1/embeddings', json_encode(['model' => 'text-embedding-ada-002', 'input' => $this->sanitize_text_for_json($text)]), 'openai');
            if (isset($response['data'][0]['embedding'])) {
                return $response['data'][0]['embedding'];
            }
            throw new Exception($response['error']['message'] ?? 'Invalid embedding response from OpenAI API.');
        }

        public function get_chat_completion($system_message, $user_message, $temperature = 0.7, $model = 'gpt-3.5-turbo') {
            if (strpos($model, 'gemini') === 0) {
                if (empty($this->gemini_api_key)) throw new Exception('Gemini API key is required.');
                $response = $this->make_http_request('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent', json_encode(['contents' => [['parts' => [['text' => $system_message . "\n\n" . $user_message]]]]]), 'gemini');
                if (isset($response['candidates'][0]['content']['parts'][0]['text'])) return $response['candidates'][0]['content']['parts'][0]['text'];
                throw new Exception('Invalid chat response from Gemini API.');
            }

            if (empty($this->openai_api_key)) throw new Exception('OpenAI API key is required.');
            $response = $this->make_http_request('https://api.openai.com/v1/chat/completions', json_encode(['model' => $model, 'messages' => [['role' => 'system', 'content' => $this->sanitize_text_for_json($system_message)], ['role' => 'user', 'content' => $this->sanitize_text_for_json($user_message)]], 'temperature' => floatval($temperature)]), 'openai');
            if (isset($response['choices'][0]['message']['content'])) return $response['choices'][0]['message']['content'];
            throw new Exception($response['error']['message'] ?? 'Invalid chat response from OpenAI API.');
        }

        private function make_http_request($url, $body, $api_type) {
            $headers = ['Content-Type' => 'application/json'];
            if ($api_type === 'openai') {
                $headers['Authorization'] = 'Bearer ' . $this->openai_api_key;
            } elseif ($api_type === 'gemini') {
                $url = add_query_arg('key', $this->gemini_api_key, $url);
            }

            $response = wp_remote_post($url, ['headers' => $headers, 'body' => $body, 'timeout' => 60]);
            
            if (is_wp_error($response)) throw new Exception('HTTP request failed: ' . $response->get_error_message());
            
            $response_code = wp_remote_retrieve_response_code($response);
            $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

            if ($response_code !== 200) throw new Exception("API Error ({$response_code}): " . ($decoded_response['error']['message'] ?? 'API request failed'));
            
            return $decoded_response;
        }

        public function test_api_connection() {
            try { $this->generate_embeddings("test"); return ['success' => true]; } catch (Exception $e) { return ['success' => false, 'error' => $e->getMessage()]; }
        }

        public function test_gemini_api_connection() {
            if (empty($this->gemini_api_key)) return ['success' => false, 'error' => 'Gemini API key is missing.'];
            try { $this->get_chat_completion("Test prompt", "Say 'hello'.", 0.5, 'gemini-1.5-flash-latest'); return ['success' => true]; } catch (Exception $e) { return ['success' => false, 'error' => $e->getMessage()]; }
        }
    }
endif; // End the class_exists check