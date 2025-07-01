<?php
/**
 * AI GPT Client for handling API requests to OpenAI and Claude
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_AI_GPT_Client {
    
    private $settings;
    private $openai_api_key;
    private $claude_api_key;
    private $default_model;
    
    public function __construct() {
        $this->settings = AIOHM_KB_Core_Init::get_settings();
        $this->openai_api_key = !empty($this->settings['openai_api_key']) ? $this->settings['openai_api_key'] : getenv('OPENAI_API_KEY');
        $this->claude_api_key = !empty($this->settings['claude_api_key']) ? $this->settings['claude_api_key'] : getenv('CLAUDE_API_KEY');
        $this->default_model = $this->settings['default_model'];
    }
    
    /**
     * Generate response using AI model
     */
    public function generate_response($query, $context = array(), $model = null) {
        if (!$model) {
            $model = $this->default_model;
        }
        
        // Prepare context for the AI
        $context_text = $this->prepare_context($context);
        
        // Generate system prompt
        $system_prompt = $this->get_system_prompt();
        
        // Generate user prompt with context
        $user_prompt = $this->build_user_prompt($query, $context_text);
        
        try {
            if ($model === 'openai' && !empty($this->openai_api_key)) {
                return $this->call_openai_api($system_prompt, $user_prompt);
            } elseif ($model === 'claude' && !empty($this->claude_api_key)) {
                return $this->call_claude_api($system_prompt, $user_prompt);
            } else {
                throw new Exception('No valid API key found for selected model: ' . $model);
            }
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('AI API Error: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($system_prompt, $user_prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = array(
            'Authorization: Bearer ' . $this->openai_api_key,
            'Content-Type: application/json'
        );
        
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $user_prompt
                )
            ),
            'max_tokens' => intval($this->settings['max_tokens']),
            'temperature' => floatval($this->settings['temperature']),
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );
        
        $response = $this->make_http_request($url, $headers, $data);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        } else {
            throw new Exception('Invalid response from OpenAI API');
        }
    }
    
    /**
     * Call Claude API
     */
    private function call_claude_api($system_prompt, $user_prompt) {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $headers = array(
            'x-api-key: ' . $this->claude_api_key,
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01'
        );
        
        $data = array(
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => intval($this->settings['max_tokens']),
            'temperature' => floatval($this->settings['temperature']),
            'system' => $system_prompt,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $user_prompt
                )
            )
        );
        
        $response = $this->make_http_request($url, $headers, $data);
        
        if (isset($response['content'][0]['text'])) {
            return trim($response['content'][0]['text']);
        } else {
            throw new Exception('Invalid response from Claude API');
        }
    }
    
    /**
     * Make HTTP request to API
     */
    private function make_http_request($url, $headers, $data) {
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30,
            'sslverify' => true
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
            throw new Exception("API Error ({$response_code}): {$error_message}");
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $decoded_response;
    }
    
    /**
     * Prepare context from relevant chunks
     */
    private function prepare_context($context_chunks) {
        if (empty($context_chunks)) {
            return '';
        }
        
        $context_parts = array();
        
        foreach ($context_chunks as $chunk) {
            $context_part = "Source: " . $chunk['title'] . " (" . $chunk['content_type'] . ")\n";
            $context_part .= "Content: " . $chunk['content'] . "\n";
            $context_parts[] = $context_part;
        }
        
        return implode("\n---\n", $context_parts);
    }
    
    /**
     * Get system prompt
     */
    private function get_system_prompt() {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        
        return "You are a helpful AI assistant for {$site_name}. " .
               (!empty($site_description) ? "The website is described as: {$site_description}. " : "") .
               "You have access to knowledge from the website content including posts, pages, uploaded documents, and images. " .
               "Use the provided context to answer questions accurately and helpfully. " .
               "If you don't have enough information in the context to answer a question, say so politely. " .
               "Keep your responses concise and relevant. " .
               "Always base your answers on the provided context when possible.";
    }
    
    /**
     * Build user prompt with context
     */
    private function build_user_prompt($query, $context_text) {
        $prompt = "Question: {$query}\n\n";
        
        if (!empty($context_text)) {
            $prompt .= "Relevant context from the knowledge base:\n{$context_text}\n\n";
            $prompt .= "Please answer the question based on the above context. ";
        } else {
            $prompt .= "No specific context was found in the knowledge base for this question. ";
            $prompt .= "Please provide a helpful general response or indicate that you don't have specific information about this topic. ";
        }
        
        return $prompt;
    }
    
    /**
     * Generate embeddings using OpenAI API
     */
    public function generate_embeddings($text) {
        if (empty($this->openai_api_key)) {
            throw new Exception('OpenAI API key is required for embeddings');
        }
        
        $url = 'https://api.openai.com/v1/embeddings';
        
        $headers = array(
            'Authorization: Bearer ' . $this->openai_api_key,
            'Content-Type: application/json'
        );
        
        $data = array(
            'model' => 'text-embedding-ada-002',
            'input' => $text
        );
        
        $response = $this->make_http_request($url, $headers, $data);
        
        if (isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        } else {
            throw new Exception('Invalid embedding response from OpenAI API');
        }
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection($model = null) {
        if (!$model) {
            $model = $this->default_model;
        }
        
        try {
            $test_response = $this->generate_response(
                "Hello, this is a connection test. Please respond with 'Connection successful.'",
                array(),
                $model
            );
            
            return array(
                'success' => true,
                'model' => $model,
                'response' => $test_response
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'model' => $model,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get available models
     */
    public function get_available_models() {
        $models = array();
        
        if (!empty($this->openai_api_key)) {
            $models['openai'] = array(
                'name' => 'OpenAI GPT',
                'description' => 'OpenAI GPT-3.5 Turbo',
                'available' => true
            );
        }
        
        if (!empty($this->claude_api_key)) {
            $models['claude'] = array(
                'name' => 'Anthropic Claude',
                'description' => 'Claude 3 Sonnet',
                'available' => true
            );
        }
        
        return $models;
    }
    
    /**
     * Validate API keys
     */
    public function validate_api_keys() {
        $validation = array(
            'openai' => array(
                'provided' => !empty($this->openai_api_key),
                'valid' => false
            ),
            'claude' => array(
                'provided' => !empty($this->claude_api_key),
                'valid' => false
            )
        );
        
        // Test OpenAI key
        if ($validation['openai']['provided']) {
            try {
                $test = $this->test_api_connection('openai');
                $validation['openai']['valid'] = $test['success'];
            } catch (Exception $e) {
                $validation['openai']['error'] = $e->getMessage();
            }
        }
        
        // Test Claude key
        if ($validation['claude']['provided']) {
            try {
                $test = $this->test_api_connection('claude');
                $validation['claude']['valid'] = $test['success'];
            } catch (Exception $e) {
                $validation['claude']['error'] = $e->getMessage();
            }
        }
        
        return $validation;
    }
}
