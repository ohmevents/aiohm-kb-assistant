<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine.
 * This version fixes the deletion logic to correctly update post meta status
 * and includes a method to fetch a random content chunk.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_RAG_Engine {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aiohm_vector_entries';
    }
    
    /**
     * Returns the full table name for the knowledge base entries.
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }

    public function add_entry($content, $content_type, $title, $metadata = [], $user_id = 0) {
        global $wpdb;
        $ai_client = new AIOHM_KB_AI_GPT_Client();
        $settings = AIOHM_KB_Assistant::get_settings();
        $chunk_size = $settings['chunk_size'] ?? 1000;
        $chunk_overlap = $settings['chunk_overlap'] ?? 200;
        $chunks = $this->chunk_content($content, $chunk_size, $chunk_overlap);
        if (empty($chunks)) { throw new Exception('Content chunking failed for title: ' . $title); }
        $content_id = $this->generate_entry_id($title, $content);
        $this->delete_entry_by_content_id($content_id);
        foreach ($chunks as $chunk_index => $chunk) {
            $embedding = $ai_client->generate_embeddings($chunk);
            $chunk_metadata = array_merge($metadata, ['chunk_index' => $chunk_index]);
            $wpdb->insert(
                $this->table_name,
                ['user_id' => $user_id, 'content_id' => $content_id, 'content_type' => $content_type, 'title' => $title, 'content' => $chunk, 'vector_data' => json_encode($embedding), 'metadata' => json_encode($chunk_metadata)],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
        return true;
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

    /**
     * Deletes an entry by its content ID and also removes the '_aiohm_indexed' post meta
     * from the original post or attachment to keep the status in sync.
     */
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
    
    public function find_relevant_context($query_text, $limit = 5) {
        global $wpdb;
        $ai_client = new AIOHM_KB_AI_GPT_Client();
        $query_embedding = $ai_client->generate_embeddings($query_text);
        $all_entries = $wpdb->get_results("SELECT id, title, content, content_type, metadata, vector_data FROM {$this->table_name} WHERE user_id = 0", ARRAY_A);
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

    /**
     * Gets a single random content chunk from the knowledge base.
     * @return string|null The content of a random entry, or null if the table is empty.
     */
    public function get_random_chunk() {
        global $wpdb;
        $random_entry = $wpdb->get_var("SELECT content FROM {$this->table_name} WHERE user_id = 0 ORDER BY RAND() LIMIT 1");
        return $random_entry;
    }
}