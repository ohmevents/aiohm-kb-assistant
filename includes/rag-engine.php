<?php
// File: includes/rag-engine.php

if (!defined('ABSPATH')) exit;

// Core logic for embedding, vector storage, and retrieval
class AIOHM_RAG_Engine {

  private $index = [];
  private $index_option_key = 'aiohm_vector_index';

  public function __construct() {
    $this->load_index();
  }

  private function load_index() {
    $stored = get_option($this->index_option_key, []);
    $this->index = is_array($stored) ? $stored : [];
  }

  private function save_index() {
    update_option($this->index_option_key, $this->index);
  }

  public function clear_index() {
    $this->index = [];
    $this->save_index();
  }

  public function add_document($id, $text, $meta = []) {
    if (empty($text)) return;
    $embedding = $this->embed($text);
    if ($embedding) {
      $this->index[$id] = [
        'embedding' => $embedding,
        'meta' => $meta,
      ];
      $this->save_index();
    }
  }

  public function remove_document($id) {
    unset($this->index[$id]);
    $this->save_index();
  }

  public function search($query, $top_k = 5) {
    $q_embed = $this->embed($query);
    if (!$q_embed) return [];

    $scores = [];
    foreach ($this->index as $id => $entry) {
      $score = $this->cosine_similarity($q_embed, $entry['embedding']);
      $scores[$id] = $score;
    }

    arsort($scores);
    return array_slice($scores, 0, $top_k, true);
  }

  private function embed($text) {
    // Uses OpenAI API to get embedding
    $api_key = get_option('aiohm_openai_key');
    $body = json_encode([
      'input' => $text,
      'model' => 'text-embedding-ada-002'
    ]);

    $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
      ],
      'body' => $body,
    ]);

    if (is_wp_error($response)) return null;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return $data['data'][0]['embedding'] ?? null;
  }

  private function cosine_similarity($a, $b) {
    $dot = 0;
    $norm_a = 0;
    $norm_b = 0;

    for ($i = 0; $i < count($a); $i++) {
      $dot += $a[$i] * $b[$i];
      $norm_a += $a[$i] ** 2;
      $norm_b += $b[$i] ** 2;
    }

    return $norm_a && $norm_b ? $dot / (sqrt($norm_a) * sqrt($norm_b)) : 0;
  }
}

// Global instance
$GLOBALS['aiohm_rag_engine'] = new AIOHM_RAG_Engine();
