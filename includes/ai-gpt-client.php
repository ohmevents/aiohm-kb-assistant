<?php
/**
 * AI GPT Client for handling API requests.
 * This version includes an increased timeout for more stability and corrected Gemini API support.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_AI_GPT_Client {
    
    private $settings;
    private $openai_api_key;
    private $gemini_api_key;

    public function __construct($settings = null) {
        if ($settings === null) {
            $this->settings = AIOHM_KB_Assistant::get_settings();
        } else {
            $this->settings = $settings;
        }
        $this->openai_api_key = $this->settings['openai_api_key'] ?? '';
        $this->gemini_api_key = $this->settings['gemini_api_key'] ?? '';
    }
    
    private function sanitize_text_for_json($text) {
        if (is_string($text)) {
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        return $text;
    }

    public function generate_embeddings($text) {
        if (empty($this->openai_api_key)) {
            throw new Exception('OpenAI API key is required for embeddings.');
        }
        
        $url = 'https://api.openai.com/v1/embeddings';
        $data = [
            'model' => 'text-embedding-ada-002',
            'input' => $this->sanitize_text_for_json($text)
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode embedding request. Content may contain invalid characters.');
        }

        $response = $this->make_http_request($url, $body, 'openai');
        
        if (isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid embedding response from OpenAI API.';
            throw new Exception($error_message);
        }
    }

    public function get_chat_completion($system_message, $user_message, $temperature = 0.7, $model = 'gpt-3.5-turbo') {
        if (strpos($model, 'gemini') === 0) {
            if (empty($this->gemini_api_key)) {
                throw new Exception('Gemini API key is required for chat completions.');
            }
            
            // Map UI model names to actual Gemini API model names
            $gemini_model_map = [
                'gemini-pro' => 'gemini-1.5-flash-latest',
                'gemini-1.5-flash' => 'gemini-1.5-flash-latest',
                'gemini-1.5-pro' => 'gemini-1.5-pro-latest'
            ];
            
            $actual_model = $gemini_model_map[$model] ?? 'gemini-1.5-flash-latest';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$actual_model}:generateContent";
            
            $data = [ 'contents' => [ [ 'parts' => [ ['text' => $system_message . "\n\n" . $user_message] ] ] ] ];
            $response = $this->make_http_request($url, json_encode($data), 'gemini');
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                return $response['candidates'][0]['content']['parts'][0]['text'];
            } else {
                 throw new Exception('Invalid chat response from Gemini API.');
            }
        }

        if (empty($this->openai_api_key)) {
            throw new Exception('OpenAI API key is required for chat completions.');
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->sanitize_text_for_json($system_message)],
                ['role' => 'user', 'content' => $this->sanitize_text_for_json($user_message)]
            ],
            'temperature' => floatval($temperature),
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode chat request.');
        }

        $response = $this->make_http_request($url, $body, 'openai');
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid chat response from OpenAI API.';
            throw new Exception($error_message);
        }
    }

    private function make_http_request($url, $body, $api_type) {
        $headers = ['Content-Type' => 'application/json'];
        if ($api_type === 'openai') {
            $headers['Authorization'] = 'Bearer ' . $this->openai_api_key;
        } elseif ($api_type === 'gemini') {
            $url = add_query_arg('key', $this->gemini_api_key, $url);
        }

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = $decoded_response['error']['message'] ?? 'API request failed with status ' . $response_code;
            throw new Exception('API Error (' . intval($response_code) . '): ' . $error_message);
        }
        
        return $decoded_response;
    }

    public function test_api_connection() {
        try {
            $this->generate_embeddings("test");
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function test_gemini_api_connection() {
        if (empty($this->gemini_api_key)) {
            return ['success' => false, 'error' => 'Gemini API key is missing.'];
        }
        try {
            $this->get_chat_completion("Test prompt", "Say 'hello'.", 0.5, 'gemini-pro');
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}