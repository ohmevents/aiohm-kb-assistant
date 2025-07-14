<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine.
 * This version includes performance optimizations and improved error handling.
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

    /**
     * Adds a new entry to the knowledge base, with robust error handling.
     *
     * @param string $content      The text content to add.
     * @param string $content_type The type of content (e.g., 'post', 'page').
     * @param string $title        The title of the content.
     * @param array  $metadata     Additional metadata for the entry.
     * @param int    $user_id      The user ID for private entries (0 for public).
     * @return bool|WP_Error       True on success, WP_Error on failure.
     */
    public function add_entry($content, $content_type, $title, $metadata = [], $user_id = 0) {
        try {
            global $wpdb;
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $settings = AIOHM_KB_Assistant::get_settings();
            $chunk_size = $settings['chunk_size'] ?? 1000;
            $chunk_overlap = $settings['chunk_overlap'] ?? 200;

            $chunks = $this->chunk_content($content, $chunk_size, $chunk_overlap);

            // --- Start of Fix ---
            // Gracefully handle cases where chunking results in an empty array.
            if (empty($chunks)) {
                throw new Exception('Content was empty or could not be chunked.');
            }
            
            $content_id = $this->generate_entry_id($title, $content);
            $this->delete_entry_by_content_id($content_id);

            foreach ($chunks as $chunk_index => $chunk) {
                // The generate_embeddings call can also throw an exception.
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
            // --- End of Fix ---

        } catch (Exception $e) {
            // Log the detailed technical error for debugging purposes.
            AIOHM_KB_Assistant::log('Failed to add entry for "' . $title . '": ' . $e->getMessage(), 'error');
            
            // Return a standard WordPress error object with a user-friendly message.
            return new WP_Error(
                'add_entry_failed',
                'Could not add the entry "' . esc_html($title) . '" to the knowledge base. Please check the logs for more details.',
                ['title' => $title]
            );
        }

        return true;
    }
    
    /**
     * **MODIFIED FOR PERFORMANCE**
     * This function now uses a `MATCH() AGAINST()` query on the `FULLTEXT` index,
     * which is significantly faster for keyword searches than the previous `LIKE` query.
     */
    public function find_relevant_context($query_text, $limit = 5) {
        global $wpdb;
        $ai_client = new AIOHM_KB_AI_GPT_Client();
        $query_embedding = $ai_client->generate_embeddings($query_text);

        // Optimization: Pre-filter entries using a FULLTEXT search to reduce the search space.
        $keywords = preg_replace('/[^a-z0-9\s]/i', '', strtolower($query_text));
        
        $where_clauses = ["user_id = 0"];
        $query_args = [];
        if (!empty(trim($keywords))) {
            $where_clauses[] = "MATCH(content) AGAINST(%s IN BOOLEAN MODE)";
            $query_args[] = '+' . str_replace(' ', ' +', trim($keywords)); // Add '+' to require all words
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        $pre_filtered_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, content, content_type, metadata, vector_data FROM {$this->table_name} WHERE {$where_sql}",
            $query_args
        ), ARRAY_A);

        // If keyword search yields no results, fall back to a random sample to ensure there's some context.
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
    
    public function query($query_text, $scope = 'site', $user_id = 0) {
    // 1. Find relevant context from the knowledge base
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

    // 2. Get AI settings
    $settings = AIOHM_KB_Assistant::get_settings();
    if ($scope === 'private') {
        $ai_settings = $settings['muse_mode'];
        $system_message = $ai_settings['system_prompt'];
    } else {
        $ai_settings = $settings['mirror_mode'];
        $system_message = $ai_settings['qa_system_message'];
    }

    // 3. Prepare the final prompt for the AI
    $final_prompt = str_replace('{context}', $context, $system_message);

    // 4. Send to the AI and get the response
    $ai_client = new AIOHM_KB_AI_GPT_Client();
    try {
        $response = $ai_client->get_chat_completion(
            $final_prompt,
            $query_text,
            $ai_settings['temperature'],
            $ai_settings['ai_model']
        );
        return $response;
    } catch (Exception $e) {
        // Log the error and return a user-friendly message
        AIOHM_KB_Assistant::log('AI Query Error: ' . $e->getMessage(), 'error');
        return 'I am currently unable to answer. Please try again later.';
    }
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