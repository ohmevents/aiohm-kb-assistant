<?php
/**
 * AI GPT Client for handling API requests.
 * This version is complete and includes embedding, chat completion, and testing methods.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_AI_GPT_Client {
    
    private $settings;
    private $openai_api_key;
    
    public function __construct($settings = null) {
        // If custom settings (like a key for testing) are passed, use them.
        // Otherwise, get the global settings from the main plugin class.
        if ($settings === null) {
            $this->settings = AIOHM_KB_Assistant::get_settings();
        } else {
            $this->settings = $settings;
        }
        $this->openai_api_key = $this->settings['openai_api_key'] ?? '';
    }
    
    /**
     * Helper function to sanitize text to be valid UTF-8 before JSON encoding.
     */
    private function sanitize_text_for_json($text) {
        if (is_string($text)) {
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        return $text;
    }

    /**
     * Generates embeddings for a given text string.
     */
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

        $response = $this->make_http_request($url, $body);
        
        if (isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid embedding response from OpenAI API.';
            throw new Exception($error_message);
        }
    }

    /**
     * Generates a chat response from the AI.
     */
    public function get_chat_completion($system_message, $user_message, $temperature = 0.7) {
        if (empty($this->openai_api_key)) {
            throw new Exception('OpenAI API key is required for chat completions.');
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
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

        $response = $this->make_http_request($url, $body);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid chat response from OpenAI API.';
            throw new Exception($error_message);
        }
    }

    /**
     * Makes the actual HTTP request to the OpenAI API.
     */
    private function make_http_request($url, $body) {
        $response = wp_remote_post($url, [
            'headers' => ['Authorization' => 'Bearer ' . $this->openai_api_key, 'Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = $decoded_response['error']['message'] ?? 'API request failed with status ' . $response_code;
            throw new Exception("API Error ({$response_code}): {$error_message}");
        }
        
        return $decoded_response;
    }

    /**
     * Tests the API connection by trying to generate a simple embedding.
     */
    public function test_api_connection() {
        try {
            $this->generate_embeddings("test");
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}