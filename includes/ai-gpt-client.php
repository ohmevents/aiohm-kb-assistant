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
    private $claude_api_key;
    private $ollama_server_url;
    private $ollama_model;

    public function __construct($settings = null) {
        if ($settings === null) {
            $this->settings = AIOHM_KB_Assistant::get_settings();
        } else {
            $this->settings = $settings;
        }
        $this->openai_api_key = $this->settings['openai_api_key'] ?? '';
        $this->gemini_api_key = $this->settings['gemini_api_key'] ?? '';
        $this->claude_api_key = $this->settings['claude_api_key'] ?? '';
        $this->ollama_server_url = $this->settings['private_llm_server_url'] ?? '';
        $this->ollama_model = $this->settings['private_llm_model'] ?? 'llama2';
    }
    
    /**
     * Check if API key is properly configured for the given provider
     * @param string $provider The AI provider (openai, gemini, claude, ollama)
     * @return bool True if API key is configured
     */
    public function is_api_key_configured($provider) {
        switch ($provider) {
            case 'openai':
                return !empty($this->openai_api_key);
            case 'gemini':
                return !empty($this->gemini_api_key);
            case 'claude':
                return !empty($this->claude_api_key);
            case 'ollama':
                return !empty($this->ollama_server_url);
            default:
                return false;
        }
    }
    
    /**
     * Check rate limit for API calls
     * @param string $provider The AI provider
     * @return bool True if within rate limit
     */
    private function check_rate_limit($provider) {
        $user_id = get_current_user_id();
        $transient_key = "aiohm_rate_limit_{$provider}_{$user_id}";
        
        $current_count = get_transient($transient_key);
        $max_requests = 100; // Max requests per hour
        
        if ($current_count === false) {
            set_transient($transient_key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($current_count >= $max_requests) {
            return false;
        }
        
        set_transient($transient_key, $current_count + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    private function sanitize_text_for_json($text) {
        if (is_string($text)) {
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        return $text;
    }

    public function generate_embeddings($text) {
        $provider = $this->settings['default_ai_provider'] ?? 'openai';
        
        switch ($provider) {
            case 'gemini':
                return $this->generate_gemini_embeddings($text);
            case 'claude':
                return $this->generate_claude_embeddings($text);
            case 'ollama':
                return $this->generate_ollama_embeddings($text);
            case 'openai':
            default:
                return $this->generate_openai_embeddings($text);
        }
    }
    
    private function generate_openai_embeddings($text) {
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
            throw new Exception(esc_html($error_message));
        }
    }
    
    private function generate_gemini_embeddings($text) {
        if (empty($this->gemini_api_key)) {
            throw new Exception('Gemini API key is required for embeddings.');
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent';
        $data = [
            'content' => [
                'parts' => [
                    ['text' => $this->sanitize_text_for_json($text)]
                ]
            ]
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode embedding request. Content may contain invalid characters.');
        }

        $response = $this->make_http_request($url, $body, 'gemini');
        
        if (isset($response['embedding']['values'])) {
            return $response['embedding']['values'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid embedding response from Gemini API.';
            throw new Exception(esc_html($error_message));
        }
    }
    
    private function generate_claude_embeddings($text) {
        if (empty($this->claude_api_key)) {
            throw new Exception('Claude API key is required for embeddings.');
        }
        
        // Note: Claude doesn't have a native embedding API, so we'll use a text-based approach
        // This creates a simple hash-based embedding as a fallback
        // In production, you might want to use a different embedding service or OpenAI as fallback
        $normalized_text = strtolower(trim($this->sanitize_text_for_json($text)));
        
        // Create a simple 1536-dimensional embedding (same as OpenAI) using text characteristics
        $embedding = [];
        $text_length = strlen($normalized_text);
        $word_count = str_word_count($normalized_text);
        $char_distribution = array_count_values(str_split($normalized_text));
        
        // Generate embedding based on text characteristics
        for ($i = 0; $i < 1536; $i++) {
            $char_index = $i % 256; // ASCII range
            $char = chr($char_index);
            $char_freq = $char_distribution[$char] ?? 0;
            
            // Combine various text metrics for embedding values
            $value = (($char_freq / max($text_length, 1)) * 0.5) + 
                    (sin($i * 0.1) * 0.3) + 
                    (cos($word_count * $i * 0.01) * 0.2);
            
            $embedding[] = $value;
        }
        
        return $embedding;
    }
    
    private function generate_ollama_embeddings($text) {
        if (empty($this->ollama_server_url)) {
            throw new Exception('Ollama server URL is required for embeddings.');
        }
        
        // Use Ollama's embedding endpoint if available, fallback to simple embedding
        $base_url = rtrim($this->ollama_server_url, '/');
        $url = $base_url . '/api/embeddings';
        
        $data = [
            'model' => $this->ollama_model,
            'prompt' => $this->sanitize_text_for_json($text)
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode embedding request. Content may contain invalid characters.');
        }

        $response = $this->make_http_request($url, $body, 'ollama');
        
        if (isset($response['embedding'])) {
            return $response['embedding'];
        } else {
            // Fallback: create a simple hash-based embedding
            $normalized_text = strtolower(trim($this->sanitize_text_for_json($text)));
            $embedding = [];
            $text_length = strlen($normalized_text);
            $char_distribution = array_count_values(str_split($normalized_text));
            
            for ($i = 0; $i < 1536; $i++) {
                $char_index = $i % 256;
                $char = chr($char_index);
                $char_freq = $char_distribution[$char] ?? 0;
                
                $value = (($char_freq / max($text_length, 1)) * 0.5) + 
                        (sin($i * 0.1) * 0.3) + 
                        (cos($text_length * $i * 0.01) * 0.2);
                
                $embedding[] = $value;
            }
            return $embedding;
        }
    }

    public function get_chat_completion($system_message, $user_message, $temperature = 0.7, $model = 'gpt-3.5-turbo') {
        if (strpos($model, 'gemini') === 0) {
            return $this->get_gemini_chat_completion($system_message, $user_message, $temperature, $model);
        }
        
        if (strpos($model, 'claude') === 0) {
            return $this->get_claude_chat_completion($system_message, $user_message, $temperature, $model);
        }
        
        if ($model === 'ollama' || $this->settings['default_ai_provider'] === 'ollama') {
            return $this->get_ollama_chat_completion($system_message, $user_message, $temperature, $model);
        }
        
        return $this->get_openai_chat_completion($system_message, $user_message, $temperature, $model);
    }
    
    private function get_gemini_chat_completion($system_message, $user_message, $temperature, $model) {
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
    
    private function get_claude_chat_completion($system_message, $user_message, $temperature, $model) {
        if (empty($this->claude_api_key)) {
            throw new Exception('Claude API key is required for chat completions.');
        }
        
        // Map UI model names to actual Claude API model names
        $claude_model_map = [
            'claude-3-sonnet' => 'claude-3-sonnet-20240229',
            'claude-3-haiku' => 'claude-3-haiku-20240307',
            'claude-3-opus' => 'claude-3-opus-20240229'
        ];
        
        $actual_model = $claude_model_map[$model] ?? 'claude-3-sonnet-20240229';
        $url = 'https://api.anthropic.com/v1/messages';
        
        $data = [
            'model' => $actual_model,
            'max_tokens' => 4000,
            'temperature' => floatval($temperature),
            'system' => $this->sanitize_text_for_json($system_message),
            'messages' => [
                ['role' => 'user', 'content' => $this->sanitize_text_for_json($user_message)]
            ]
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode chat request.');
        }

        $response = $this->make_http_request($url, $body, 'claude');
        
        if (isset($response['content'][0]['text'])) {
            return $response['content'][0]['text'];
        } else {
            $error_message = $response['error']['message'] ?? 'Invalid chat response from Claude API.';
            throw new Exception(esc_html($error_message));
        }
    }
    
    private function get_ollama_chat_completion($system_message, $user_message, $temperature, $model) {
        if (empty($this->ollama_server_url)) {
            throw new Exception('Ollama server URL is required for chat completions.');
        }
        
        // Use Ollama's correct API endpoint
        $base_url = rtrim($this->ollama_server_url, '/');
        $url = $base_url . '/api/generate';
        $prompt = $system_message . "\n\n" . $user_message;
        
        $data = [
            'model' => $this->ollama_model,
            'prompt' => $this->sanitize_text_for_json($prompt),
            'stream' => false
        ];
        
        $body = json_encode($data);
        if ($body === false) {
            throw new Exception('Failed to JSON-encode chat request.');
        }

        $response = $this->make_http_request($url, $body, 'ollama');
        
        if (isset($response['response'])) {
            return $response['response'];
        } else {
            $error_message = $response['error'] ?? 'Invalid chat response from Ollama server.';
            throw new Exception(esc_html($error_message));
        }
    }
    
    private function get_openai_chat_completion($system_message, $user_message, $temperature, $model) {
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
            throw new Exception(esc_html($error_message));
        }
    }

    private function make_http_request($url, $body, $api_type) {
        $headers = ['Content-Type' => 'application/json'];
        if ($api_type === 'openai') {
            $headers['Authorization'] = 'Bearer ' . $this->openai_api_key;
        } elseif ($api_type === 'gemini') {
            $url = add_query_arg('key', $this->gemini_api_key, $url);
        } elseif ($api_type === 'claude') {
            $headers['Authorization'] = 'Bearer ' . $this->claude_api_key;
            $headers['anthropic-version'] = '2023-06-01';
        } elseif ($api_type === 'ollama') {
            // Ollama doesn't require authentication headers, just content-type
        }

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . esc_html($response->get_error_message()));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = $decoded_response['error']['message'] ?? 'API request failed with status ' . $response_code;
            throw new Exception('API Error (' . intval($response_code) . '): ' . esc_html($error_message));
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

    public function test_claude_api_connection() {
        if (empty($this->claude_api_key)) {
            return ['success' => false, 'error' => 'Claude API key is missing.'];
        }
        try {
            $this->get_chat_completion("Test prompt", "Say 'hello'.", 0.5, 'claude-3-sonnet');
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function test_ollama_api_connection() {
        if (empty($this->ollama_server_url)) {
            return ['success' => false, 'error' => 'Ollama server URL is missing.'];
        }
        try {
            // Test using Ollama's correct API endpoint
            $base_url = rtrim($this->ollama_server_url, '/');
            $url = $base_url . '/api/generate';
            
            $data = [
                'model' => $this->ollama_model,
                'prompt' => 'Say hello',
                'stream' => false
            ];
            
            $body = json_encode($data);
            $response = $this->make_http_request($url, $body, 'ollama');
            
            if (isset($response['response'])) {
                return ['success' => true, 'message' => 'Connected successfully'];
            } else {
                return ['success' => false, 'error' => 'No response from server'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}