<?php
/**
 * RAG (Retrieval-Augmented Generation) Engine for vector embeddings and semantic search
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_RAG_Engine {
    
    private $vector_entries;
    private $chunk_size;
    private $chunk_overlap;
    
    public function __construct() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        $this->chunk_size = isset($settings['chunk_size']) ? $settings['chunk_size'] : 1000;
        $this->chunk_overlap = isset($settings['chunk_overlap']) ? $settings['chunk_overlap'] : 200;
        $this->load_vector_entries();
    }
    
    /**
     * Load vector entries from database/options
     */
    private function load_vector_entries() {
        $this->vector_entries = get_option('aiohm_vector_entries', array());
    }
    
    /**
     * Save vector entries to database/options
     */
    private function save_vector_entries() {
        return update_option('aiohm_vector_entries', $this->vector_entries);
    }
    
    /**
     * Add new entry to vector database
     */
    public function add_entry($content, $content_type, $title, $metadata = array()) {
        // Generate unique entry ID
        $entry_id = $this->generate_entry_id($content, $content_type, $title);
        
        // Check if entry already exists
        if (isset($this->vector_entries[$entry_id])) {
            // Update existing entry
            return $this->update_entry($entry_id, $content, $content_type, $title, $metadata);
        }
        
        // Chunk the content
        $chunks = $this->chunk_content($content);
        
        // Generate embeddings for each chunk
        $embedded_chunks = array();
        foreach ($chunks as $chunk_index => $chunk) {
            $embedding = $this->generate_embedding($chunk);
            
            $embedded_chunks[] = array(
                'chunk_index' => $chunk_index,
                'content' => $chunk,
                'embedding' => $embedding,
                'length' => strlen($chunk)
            );
        }
        
        // Store entry
        $this->vector_entries[$entry_id] = array(
            'id' => $entry_id,
            'title' => $title,
            'content_type' => $content_type,
            'original_content' => $content,
            'chunks' => $embedded_chunks,
            'metadata' => $metadata,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $this->save_vector_entries();
        
        AIOHM_KB_Core_Init::log("Added entry: {$entry_id} with " . count($embedded_chunks) . " chunks");
        
        return $entry_id;
    }
    
    /**
     * Update existing entry
     */
    public function update_entry($entry_id, $content, $content_type, $title, $metadata = array()) {
        if (!isset($this->vector_entries[$entry_id])) {
            return false;
        }
        
        // Re-chunk and re-embed content
        $chunks = $this->chunk_content($content);
        $embedded_chunks = array();
        
        foreach ($chunks as $chunk_index => $chunk) {
            $embedding = $this->generate_embedding($chunk);
            
            $embedded_chunks[] = array(
                'chunk_index' => $chunk_index,
                'content' => $chunk,
                'embedding' => $embedding,
                'length' => strlen($chunk)
            );
        }
        
        // Update entry
        $this->vector_entries[$entry_id]['title'] = $title;
        $this->vector_entries[$entry_id]['content_type'] = $content_type;
        $this->vector_entries[$entry_id]['original_content'] = $content;
        $this->vector_entries[$entry_id]['chunks'] = $embedded_chunks;
        $this->vector_entries[$entry_id]['metadata'] = array_merge(
            isset($this->vector_entries[$entry_id]['metadata']) ? $this->vector_entries[$entry_id]['metadata'] : array(),
            $metadata
        );
        $this->vector_entries[$entry_id]['updated_at'] = current_time('mysql');
        
        $this->save_vector_entries();
        
        AIOHM_KB_Core_Init::log("Updated entry: {$entry_id}");
        
        return $entry_id;
    }
    
    /**
     * Delete entry from vector database
     */
    public function delete_entry($entry_id) {
        if (isset($this->vector_entries[$entry_id])) {
            unset($this->vector_entries[$entry_id]);
            $this->save_vector_entries();
            
            AIOHM_KB_Core_Init::log("Deleted entry: {$entry_id}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Find relevant context for a query
     */
    public function find_relevant_context($query, $max_results = 5, $similarity_threshold = 0.7) {
        if (empty($this->vector_entries) || !is_array($this->vector_entries)) {
            return array();
        }
        
        // Generate embedding for query
        $query_embedding = $this->generate_embedding($query);
        
        $results = array();
        
        // Search through all chunks
        foreach ($this->vector_entries as $entry_id => $entry) {
            if (!isset($entry['chunks']) || !is_array($entry['chunks'])) {
                continue;
            }
            
            foreach ($entry['chunks'] as $chunk) {
                if (!isset($chunk['embedding'])) {
                    continue;
                }
                
                $similarity = $this->calculate_similarity($query_embedding, $chunk['embedding']);
                
                if ($similarity >= $similarity_threshold) {
                    $results[] = array(
                        'entry_id' => $entry_id,
                        'title' => isset($entry['title']) ? $entry['title'] : '',
                        'content' => isset($chunk['content']) ? $chunk['content'] : '',
                        'content_type' => isset($entry['content_type']) ? $entry['content_type'] : '',
                        'similarity' => $similarity,
                        'metadata' => isset($entry['metadata']) ? $entry['metadata'] : array(),
                        'chunk_index' => isset($chunk['chunk_index']) ? $chunk['chunk_index'] : 0
                    );
                }
            }
        }
        
        // Sort by similarity (highest first)
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Return top results
        return array_slice($results, 0, $max_results);
    }
    
    /**
     * Get all entries
     */
    public function get_all_entries() {
        return $this->vector_entries;
    }
    
    /**
     * Get entry by ID
     */
    public function get_entry($entry_id) {
        return isset($this->vector_entries[$entry_id]) ? $this->vector_entries[$entry_id] : null;
    }
    
    /**
     * Generate entry ID
     */
    private function generate_entry_id($content, $content_type, $title) {
        return $content_type . '_' . md5($title . $content);
    }
    
    /**
     * Chunk content into smaller pieces
     */
    private function chunk_content($content) {
        $chunks = array();
        $content_length = strlen($content);
        
        if ($content_length <= $this->chunk_size) {
            return array($content);
        }
        
        $start = 0;
        $chunk_index = 0;
        
        while ($start < $content_length) {
            $end = $start + $this->chunk_size;
            
            // If this isn't the last chunk, try to find a good breaking point
            if ($end < $content_length) {
                // Look for sentence endings
                $break_point = $this->find_break_point($content, $start, $end);
                if ($break_point !== false) {
                    $end = $break_point;
                }
            } else {
                $end = $content_length;
            }
            
            $chunk = substr($content, $start, $end - $start);
            $chunk = trim($chunk);
            
            if (!empty($chunk)) {
                $chunks[] = $chunk;
            }
            
            // Move start position with overlap
            $start = $end - $this->chunk_overlap;
            if ($start < 0) {
                $start = 0;
            }
            
            $chunk_index++;
        }
        
        return $chunks;
    }
    
    /**
     * Find good breaking point for chunks
     */
    private function find_break_point($content, $start, $end) {
        $search_range = min(200, $this->chunk_overlap); // Look back up to 200 chars
        $search_start = max($start, $end - $search_range);
        
        // Look for sentence endings
        $sentence_endings = array('.', '!', '?', '\n\n');
        
        for ($i = $end; $i >= $search_start; $i--) {
            $char = substr($content, $i, 1);
            if (in_array($char, $sentence_endings)) {
                return $i + 1;
            }
        }
        
        // Look for paragraph breaks
        $pos = strrpos(substr($content, $search_start, $end - $search_start), "\n");
        if ($pos !== false) {
            return $search_start + $pos + 1;
        }
        
        // Look for word boundaries
        $pos = strrpos(substr($content, $search_start, $end - $search_start), " ");
        if ($pos !== false) {
            return $search_start + $pos + 1;
        }
        
        return false;
    }
    
    /**
     * Generate embedding for text (simplified version)
     */
    private function generate_embedding($text) {
        // This is a simplified embedding generation
        // In a real implementation, you would use OpenAI's embedding API
        
        // For now, we'll create a simple hash-based embedding
        $words = str_word_count(strtolower($text), 1);
        $embedding = array();
        
        // Create a 384-dimensional embedding (common size)
        for ($i = 0; $i < 384; $i++) {
            $embedding[$i] = 0.0;
        }
        
        // Simple word-based embedding
        foreach ($words as $word) {
            $hash = crc32($word);
            $index = abs($hash) % 384;
            $embedding[$index] += 1.0;
        }
        
        // Normalize the embedding
        $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $embedding)));
        if ($magnitude > 0) {
            $embedding = array_map(function($x) use ($magnitude) { 
                return $x / $magnitude; 
            }, $embedding);
        }
        
        return $embedding;
    }
    
    /**
     * Calculate cosine similarity between two embeddings
     */
    private function calculate_similarity($embedding1, $embedding2) {
        if (!is_array($embedding1) || !is_array($embedding2) || count($embedding1) !== count($embedding2)) {
            return 0.0;
        }
        
        $dot_product = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        for ($i = 0; $i < count($embedding1); $i++) {
            $dot_product += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0.0 || $magnitude2 == 0.0) {
            return 0.0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Generate Q&A dataset from vector entries
     */
    public function generate_qa_dataset() {
        $qa_pairs = array();
        
        if (!is_array($this->vector_entries) || empty($this->vector_entries)) {
            return $qa_pairs;
        }
        
        foreach ($this->vector_entries as $entry) {
            if (!isset($entry['original_content'])) {
                continue;
            }
            
            $content = $entry['original_content'];
            
            // Extract potential questions from content
            $questions = $this->extract_questions($content);
            
            foreach ($questions as $question) {
                // Find the most relevant chunk for this question
                $relevant_chunks = $this->find_relevant_context($question, 1, 0.5);
                
                if (!empty($relevant_chunks)) {
                    $answer = $relevant_chunks[0]['content'];
                    
                    $qa_pairs[] = array(
                        'question' => $question,
                        'answer' => $answer,
                        'source_title' => isset($entry['title']) ? $entry['title'] : '',
                        'source_type' => isset($entry['content_type']) ? $entry['content_type'] : '',
                        'confidence' => $relevant_chunks[0]['similarity']
                    );
                }
            }
        }
        
        // Save Q&A dataset
        update_option('aiohm_qa_dataset', $qa_pairs);
        
        return $qa_pairs;
    }
    
    /**
     * Extract questions from content
     */
    private function extract_questions($content) {
        $questions = array();
        
        // Find lines that end with question marks
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check if line ends with question mark
            if (substr($line, -1) === '?') {
                // Clean up the question
                $question = preg_replace('/^\W+/', '', $line); // Remove leading non-word chars
                $question = trim($question);
                
                if (strlen($question) > 10) { // Minimum question length
                    $questions[] = $question;
                }
            }
            
            // Also look for common question patterns
            if (preg_match('/^(what|how|why|when|where|who|which|can|could|would|should|is|are|do|does|did)\s+/i', $line)) {
                $question = trim($line);
                if (strlen($question) > 10) {
                    $questions[] = $question;
                }
            }
        }
        
        return array_unique($questions);
    }
    
    /**
     * Export knowledge base for external use
     */
    public function export_knowledge_base() {
        $export_data = array(
            'version' => defined('AIOHM_KB_VERSION') ? AIOHM_KB_VERSION : '1.0',
            'exported_at' => current_time('mysql'),
            'total_entries' => is_array($this->vector_entries) ? count($this->vector_entries) : 0,
            'entries' => array()
        );
        
        if (!empty($this->vector_entries) && is_array($this->vector_entries)) {
            foreach ($this->vector_entries as $entry) {
                $chunks = isset($entry['chunks']) && (is_array($entry['chunks']) || $entry['chunks'] instanceof Countable) 
                    ? $entry['chunks'] 
                    : array();
                    
                $export_data['entries'][] = array(
                    'id' => isset($entry['id']) ? $entry['id'] : '',
                    'title' => isset($entry['title']) ? $entry['title'] : '',
                    'content_type' => isset($entry['content_type']) ? $entry['content_type'] : '',
                    'content' => isset($entry['original_content']) ? $entry['original_content'] : '',
                    'metadata' => isset($entry['metadata']) ? $entry['metadata'] : array(),
                    'created_at' => isset($entry['created_at']) ? $entry['created_at'] : current_time('mysql'),
                    'chunk_count' => is_array($chunks) ? count($chunks) : 0
                );
            }
        }
        
        return $export_data;
    }
    
    /**
     * Get statistics about the knowledge base
     */
    public function get_stats() {
        $stats = array(
            'total_entries' => is_array($this->vector_entries) ? count($this->vector_entries) : 0,
            'total_chunks' => 0,
            'by_type' => array(),
            'total_content_length' => 0,
            'average_chunks_per_entry' => 0
        );
        
        if (!empty($this->vector_entries) && is_array($this->vector_entries)) {
            foreach ($this->vector_entries as $entry) {
                // Check if chunks array exists and is countable
                $chunks = isset($entry['chunks']) && (is_array($entry['chunks']) || $entry['chunks'] instanceof Countable) 
                    ? $entry['chunks'] 
                    : array();
                    
                $chunk_count = is_array($chunks) ? count($chunks) : 0;
                $content_length = isset($entry['original_content']) ? strlen($entry['original_content']) : 0;
                
                $stats['total_chunks'] += $chunk_count;
                $stats['total_content_length'] += $content_length;
                
                $type = isset($entry['content_type']) ? $entry['content_type'] : 'unknown';
                if (!isset($stats['by_type'][$type])) {
                    $stats['by_type'][$type] = array(
                        'count' => 0,
                        'chunks' => 0,
                        'content_length' => 0
                    );
                }
                
                $stats['by_type'][$type]['count']++;
                $stats['by_type'][$type]['chunks'] += $chunk_count;
                $stats['by_type'][$type]['content_length'] += $content_length;
            }
            
            if ($stats['total_entries'] > 0) {
                $stats['average_chunks_per_entry'] = $stats['total_chunks'] / $stats['total_entries'];
            }
        }
        
        return $stats;
    }
}