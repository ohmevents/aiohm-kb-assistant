<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine.
 * This version includes performance optimizations, improved error handling, and live URL research.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_RAG_Engine {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aiohm_vector_entries';
    }
    
    public function get_table_name() {
        return $this->table_name;
    }

    public function add_entry($content, $content_type, $title, $metadata = [], $user_id = 0) {
        try {
            global $wpdb;
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $settings = AIOHM_KB_Assistant::get_settings();
            $chunk_size = $settings['chunk_size'] ?? 1000;
            $chunk_overlap = $settings['chunk_overlap'] ?? 200;

            $chunks = $this->chunk_content($content, $chunk_size, $chunk_overlap);

            if (empty($chunks)) {
                throw new Exception('Content was empty or could not be chunked.');
            }
            
            $content_id = $this->generate_entry_id($title, $content);
            $this->delete_entry_by_content_id($content_id);

            foreach ($chunks as $chunk_index => $chunk) {
                $embedding = $ai_client->generate_embeddings($chunk);
                $chunk_metadata = array_merge($metadata, ['chunk_index' => $chunk_index]);
                
                $result = $wpdb->insert(
                    $this->table_name,
                    [
                        'user_id' => $user_id, 
                        'content_id' => $content_id, 
                        'content_type' => $content_type, 
                        'title' => $title, 
                        'content' => $chunk, 
                        'vector_data' => json_encode($embedding), 
                        'metadata' => json_encode($chunk_metadata)
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
                );

                if ($result === false) {
                    throw new Exception('Failed to insert a chunk into the database.');
                }
            }
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Failed to add entry for "' . $title . '": ' . $e->getMessage(), 'error');
            return new WP_Error(
                'add_entry_failed',
                'Could not add the entry "' . esc_html($title) . '" to the knowledge base. Please check the logs for more details.',
                ['title' => $title]
            );
        }
        return true;
    }
    
    public function query($query_text, $scope = 'site', $user_id = 0) {
        try {
            $research_prefix = "Please research the following URL and provide a summary of its key points:";

            if (strpos($query_text, $research_prefix) === 0) {
                preg_match('/(https?:\/\/[^\s]+)/', $query_text, $matches);
                $url = $matches[0] ?? null;

                if (!$url) {
                    return "I couldn't find a valid URL in your request. Please try again with the full URL (e.g., https://example.com).";
                }

                $result = $this->research_and_add_url($url, $user_id);

                if (is_wp_error($result)) {
                    return "I encountered an error trying to research that URL: " . $result->get_error_message();
                }
                
                return $this->summarize_new_context($url, $user_id);
            }

            if ($scope === 'private') {
                $context_entries = $this->find_context_for_user($query_text, $user_id);
            } else {
                $context_entries = $this->find_relevant_context($query_text);
            }

            $context = "";
            foreach ($context_entries as $entry) {
                $context .= "Title: " . $entry['entry']['title'] . "\n";
                $context .= "Content: " . $entry['entry']['content'] . "\n\n";
            }

            $settings = AIOHM_KB_Assistant::get_settings();
            $ai_settings = ($scope === 'private') ? $settings['muse_mode'] : $settings['mirror_mode'];
            
            $system_message_key = ($scope === 'private') ? 'system_prompt' : 'qa_system_message';
            $system_message = $ai_settings[$system_message_key] ?? 'You are a helpful assistant.';
            $model_name = $ai_settings['ai_model'] ?? 'gpt-3.5-turbo';
            $temperature = $ai_settings['temperature'] ?? 0.7;
            
            $enriched_user_message = "Here is some context to help you answer:\n\n---\n\n{$context}\n\n---\n\nBased on that context, please answer the following question:\n\n{$query_text}";

            $ai_client = new AIOHM_KB_AI_GPT_Client();
            return $ai_client->get_chat_completion(
                $system_message,
                $enriched_user_message,
                $temperature,
                $model_name
            );

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('AI Query Error: ' . $e->getMessage(), 'error');
            // ================== START: BUG FIX ==================
            // Return the ACTUAL error message to the user for debugging.
            return "AI Error: " . $e->getMessage();
            // =================== END: BUG FIX ===================
        }
    }

    public function research_and_add_url($url, $user_id) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'The provided URL is not valid.');
        }

        $response = wp_remote_get($url, ['timeout' => 25]);

        if (is_wp_error($response)) {
            return new WP_Error('fetch_failed', 'Could not retrieve content: ' . $response->get_error_message());
        }

        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (strpos($content_type, 'text/html') === false) {
            return new WP_Error('invalid_content_type', 'The URL does not appear to be an HTML page.');
        }

        $html_content = wp_remote_retrieve_body($response);
        $title = 'Web Research: ' . $url;
        if (preg_match('/<title>(.*?)<\/title>/i', $html_content, $matches)) {
            $title = trim($matches[1]);
        }

        $plain_text = wp_strip_all_tags($html_content);
        $plain_text = preg_replace('/\s+/', ' ', $plain_text);

        if (empty(trim($plain_text))) {
            return new WP_Error('no_content', 'Could not extract any readable text from the URL.');
        }

        $metadata = ['source_url' => $url];
        return $this->add_entry($plain_text, 'external_url', $title, $metadata, $user_id);
    }
    
    private function summarize_new_context($url, $user_id) {
        $summary_prompt = "You have just successfully read the content from the URL: {$url}. Now, provide a concise summary of its key points based on the context you've just learned.";
        return $this->query($summary_prompt, 'private', $user_id);
    }
    
    public function find_relevant_context($query_text, $limit = 5) {
        global $wpdb;
        $ai_client = new AIOHM_KB_AI_GPT_Client();
        $query_embedding = $ai_client->generate_embeddings($query_text);

        $keywords = preg_replace('/[^a-z0-9\s]/i', '', strtolower($query_text));
        
        $where_clauses = ["user_id = 0"];
        $query_args = [];
        if (!empty(trim($keywords))) {
            $where_clauses[] = "MATCH(content) AGAINST(%s IN BOOLEAN MODE)";
            $query_args[] = '+' . str_replace(' ', ' +', trim($keywords));
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        $pre_filtered_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, content, content_type, metadata, vector_data FROM {$this->table_name} WHERE {$where_sql}",
            $query_args
        ), ARRAY_A);

        if (empty($pre_filtered_entries)) {
             $pre_filtered_entries = $wpdb->get_results("SELECT id, title, content, content_type, metadata, vector_data FROM {$this->table_name} WHERE user_id = 0 ORDER BY RAND() LIMIT 100", ARRAY_A);
        }

        $similarities = [];
        foreach ($pre_filtered_entries as $entry) {
            $vector = json_decode($entry['vector_data'], true);
            if (is_array($vector)) {
                $dot_product = array_sum(array_map(fn($a, $b) => $a * $b, $query_embedding, $vector));
                $mag_a = sqrt(array_sum(array_map(fn($a) => $a * $a, $query_embedding)));
                $mag_b = sqrt(array_sum(array_map(fn($b) => $b * $b, $vector)));
                if ($mag_a > 0 && $mag_b > 0) {
                    $similarities[] = ['score' => $dot_product / ($mag_a * $mag_b), 'entry' => $entry];
                }
            }
        }
        usort($similarities, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($similarities, 0, $limit);
    }
    
    public function get_all_entries_paginated($per_page = 20, $page_number = 1) {
        global $wpdb;
        $offset = ($page_number - 1) * $per_page;
        $sql = $wpdb->prepare(
            "SELECT id, title, content_type, user_id, content_id, metadata, created_at
             FROM {$this->table_name} 
             GROUP BY content_id 
             ORDER BY id DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        );
        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function get_total_entries_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(DISTINCT content_id) FROM {$this->table_name}");
    }

    public function delete_entry_by_content_id($content_id) {
        global $wpdb;
        $metadata_json = $wpdb->get_var($wpdb->prepare(
            "SELECT metadata FROM {$this->table_name} WHERE content_id = %s LIMIT 1",
            $content_id
        ));
        $deleted = $wpdb->delete($this->table_name, ['content_id' => $content_id], ['%s']);
        if ($deleted > 0 && !empty($metadata_json)) {
            $metadata = json_decode($metadata_json, true);
            $original_item_id = null;
            if (isset($metadata['post_id'])) {
                $original_item_id = (int) $metadata['post_id'];
            } elseif (isset($metadata['attachment_id'])) {
                $original_item_id = (int) $metadata['attachment_id'];
            }
            if ($original_item_id) {
                delete_post_meta($original_item_id, '_aiohm_indexed');
                clean_post_cache($original_item_id);
            }
        }
        return $deleted;
    }

    private function generate_entry_id($title, $content) {
        return md5($title . $content);
    }
    
    private function chunk_content($content, $chunk_size, $chunk_overlap) {
        $chunks = []; $content = trim($content); $content_length = strlen($content);
        if ($content_length === 0) return [];
        if ($content_length <= $chunk_size) return [$content];
        $start = 0;
        while ($start < $content_length) {
            $chunks[] = substr($content, $start, $chunk_size);
            $start += ($chunk_size - $chunk_overlap);
        }
        return $chunks;
    }

    public function export_knowledge_base() {
        global $wpdb;
        $data = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE user_id = 0", ARRAY_A);
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function import_knowledge_base($json_data) {
        global $wpdb;
        $data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new Exception('Invalid JSON data provided.');
        }
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->delete($this->table_name, ['user_id' => 0], ['%d']);
            foreach ($data as $row) {
                if (isset($row['content_id'], $row['content_type'], $row['title'], $row['content'])) {
                    $wpdb->insert($this->table_name, [
                        'user_id'      => 0,
                        'content_id'   => $row['content_id'],
                        'content_type' => $row['content_type'],
                        'title'        => $row['title'],
                        'content'      => $row['content'],
                        'vector_data'  => $row['vector_data'] ?? '[]',
                        'metadata'     => $row['metadata'] ?? '[]',
                    ]);
                }
            }
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        return count($data);
    }
    
    public function find_context_for_user($query_text, $user_id, $limit = 5) {
        global $wpdb;
        $ai_client = new AIOHM_KB_AI_GPT_Client();
        $query_embedding = $ai_client->generate_embeddings($query_text);
        $sql = $wpdb->prepare(
            "SELECT id, title, content, content_type, metadata, vector_data 
             FROM {$this->table_name} 
             WHERE user_id = 0 OR user_id = %d",
            $user_id
        );
        $all_entries = $wpdb->get_results($sql, ARRAY_A);
        $similarities = [];
        foreach ($all_entries as $entry) {
            $vector = json_decode($entry['vector_data'], true);
            if (is_array($vector)) {
                $dot_product = array_sum(array_map(fn($a, $b) => $a * $b, $query_embedding, $vector));
                $mag_a = sqrt(array_sum(array_map(fn($a) => $a * $a, $query_embedding)));
                $mag_b = sqrt(array_sum(array_map(fn($b) => $b * $b, $vector)));
                if ($mag_a > 0 && $mag_b > 0) {
                    $similarities[] = ['score' => $dot_product / ($mag_a * $mag_b), 'entry' => $entry];
                }
            }
        }
        usort($similarities, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($similarities, 0, $limit);
    }

    public function update_entry_scope_by_content_id($content_id, $new_user_id) {
        global $wpdb;
        return $wpdb->update($this->table_name, ['user_id' => $new_user_id], ['content_id' => $content_id], ['%d'], ['%s']);
    }

    public function get_random_chunk() {
        global $wpdb;
        $random_entry = $wpdb->get_var("SELECT content FROM {$this->table_name} WHERE user_id = 0 ORDER BY RAND() LIMIT 1");
        return $random_entry;
    }
}