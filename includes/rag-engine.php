<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine.
 * This version includes full backup (with vectors) and restore functionality.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_RAG_Engine {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aiohm_vector_entries';
    }
    
    // ... (keep add_entry, get_all_entries_paginated, etc. as they are) ...
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
    
    public function get_all_entries_paginated($per_page = 20, $page_number = 1) { /* ... keep as is ... */ }
    public function get_total_entries_count() { /* ... keep as is ... */ }
    public function delete_entry_by_content_id($content_id) { /* ... keep as is ... */ }
    private function generate_entry_id($title, $content) { /* ... keep as is ... */ }
    private function chunk_content($content, $chunk_size, $chunk_overlap) { /* ... keep as is ... */ }
    public function find_context_for_user($query_text, $user_id, $limit = 5) { /* ... keep as is ... */ }
    public function update_entry_scope_by_content_id($content_id, $new_user_id) { /* ... keep as is ... */ }


    /**
     * Exports the entire global knowledge base, including vector data for restore.
     */
    public function export_knowledge_base() {
        global $wpdb;
        $data = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE user_id = 0", ARRAY_A);
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Imports knowledge base entries from a JSON file, replacing existing global data.
     */
    public function import_knowledge_base($json_data) {
        global $wpdb;
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new Exception('Invalid JSON data provided.');
        }

        // Start a transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Clear all existing global entries (user_id = 0)
            $wpdb->delete($this->table_name, ['user_id' => 0], ['%d']);

            // Insert the new entries from the backup
            foreach ($data as $row) {
                // Basic validation for essential columns
                if (isset($row['content_id'], $row['content_type'], $row['title'], $row['content'])) {
                    $wpdb->insert($this->table_name, [
                        'user_id'      => 0, // Ensure all imported data is global
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
            throw $e; // Re-throw the exception
        }

        return count($data);
    }
}