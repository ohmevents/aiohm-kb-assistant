<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine for vector embeddings and semantic search.
 * This version fixes a bug where metadata was not being fetched for the KB management page.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_RAG_Engine {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aiohm_vector_entries';
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
    
    /**
     * Gets a paginated list of unique entries for the "Manage KB" page.
     * This query now correctly includes the metadata column.
     */
    public function get_all_entries_paginated($per_page = 20, $page_number = 1) {
        global $wpdb;
        $offset = ($page_number - 1) * $per_page;
        // ** THE FIX IS HERE: Added `metadata` to the SELECT statement **
        $sql = $wpdb->prepare(
            "SELECT id, title, content_type, user_id, content_id, metadata
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
        return $wpdb->delete($this->table_name, ['content_id' => $content_id], ['%s']);
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
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}