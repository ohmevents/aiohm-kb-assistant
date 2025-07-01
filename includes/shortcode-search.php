<?php
/**
 * Search shortcode implementation - [aiohm_search]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Shortcode_Search {
    
    public static function init() {
        add_shortcode('aiohm_search', array(__CLASS__, 'render_search_shortcode'));
        add_action('wp_ajax_aiohm_search_knowledge', array(__CLASS__, 'handle_search_ajax'));
        add_action('wp_ajax_nopriv_aiohm_search_knowledge', array(__CLASS__, 'handle_search_ajax'));
    }
    
    /**
     * Render search shortcode
     */
    public static function render_search_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'placeholder' => __('Search knowledge base...', 'aiohm-kb-assistant'),
            'show_categories' => 'true',
            'show_results_count' => 'true',
            'max_results' => '10',
            'excerpt_length' => '150',
            'show_content_type' => 'true',
            'enable_instant_search' => 'true',
            'min_chars' => '3'
        ), $atts, 'aiohm_search');
        
        // Generate unique search ID
        static $search_counter = 0;
        $search_counter++;
        $search_id = 'aiohm-search-' . $search_counter;
        
        // Enqueue search assets
        wp_enqueue_script('aiohm-chat'); // Reuse chat script for AJAX functionality
        wp_enqueue_style('aiohm-chat');
        
        $output = '<div class="aiohm-search-container" id="' . esc_attr($search_id) . '">';
        
        // Search form
        $output .= '<div class="aiohm-search-form">';
        $output .= '<div class="aiohm-search-input-wrapper">';
        $output .= '<input type="text" class="aiohm-search-input" placeholder="' . esc_attr($atts['placeholder']) . '" />';
        $output .= '<button type="button" class="aiohm-search-btn">';
        $output .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $output .= '<circle cx="11" cy="11" r="8"></circle>';
        $output .= '<path d="m21 21-4.35-4.35"></path>';
        $output .= '</svg>';
        $output .= '</button>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Search filters (if enabled)
        if ($atts['show_categories'] === 'true') {
            $output .= '<div class="aiohm-search-filters">';
            $output .= '<div class="aiohm-filter-group">';
            $output .= '<label>' . __('Content Type:', 'aiohm-kb-assistant') . '</label>';
            $output .= '<select class="aiohm-content-type-filter">';
            $output .= '<option value="">' . __('All Types', 'aiohm-kb-assistant') . '</option>';
            $output .= '<option value="post">' . __('Posts', 'aiohm-kb-assistant') . '</option>';
            $output .= '<option value="page">' . __('Pages', 'aiohm-kb-assistant') . '</option>';
            $output .= '<option value="pdf">' . __('Documents', 'aiohm-kb-assistant') . '</option>';
            $output .= '<option value="image">' . __('Images', 'aiohm-kb-assistant') . '</option>';
            $output .= '<option value="manual">' . __('Manual Entries', 'aiohm-kb-assistant') . '</option>';
            $output .= '</select>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // Search status
        $output .= '<div class="aiohm-search-status" style="display: none;">';
        $output .= '<span class="aiohm-search-loading">' . __('Searching...', 'aiohm-kb-assistant') . '</span>';
        $output .= '</div>';
        
        // Search results
        $output .= '<div class="aiohm-search-results">';
        $output .= '<div class="aiohm-search-placeholder">';
        $output .= '<div class="aiohm-search-icon">';
        $output .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">';
        $output .= '<circle cx="11" cy="11" r="8"></circle>';
        $output .= '<path d="m21 21-4.35-4.35"></path>';
        $output .= '</svg>';
        $output .= '</div>';
        $output .= '<p>' . __('Enter your search query to find relevant content from our knowledge base.', 'aiohm-kb-assistant') . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        // Add search configuration
        $search_config = array(
            'search_id' => $search_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_search_nonce'),
            'settings' => array(
                'max_results' => intval($atts['max_results']),
                'excerpt_length' => intval($atts['excerpt_length']),
                'show_content_type' => $atts['show_content_type'] === 'true',
                'enable_instant_search' => $atts['enable_instant_search'] === 'true',
                'min_chars' => intval($atts['min_chars']),
                'show_results_count' => $atts['show_results_count'] === 'true'
            ),
            'strings' => array(
                'no_results' => __('No results found for your search.', 'aiohm-kb-assistant'),
                'error' => __('Search failed. Please try again.', 'aiohm-kb-assistant'),
                'results_count' => __('Found %d result(s)', 'aiohm-kb-assistant'),
                'searching' => __('Searching...', 'aiohm-kb-assistant')
            )
        );
        
        $output .= '<script type="text/javascript">';
        $output .= 'if (typeof window.aiohm_search_configs === "undefined") window.aiohm_search_configs = {};';
        $output .= 'window.aiohm_search_configs["' . $search_id . '"] = ' . json_encode($search_config) . ';';
        $output .= '</script>';
        
        return $output;
    }
    
    /**
     * Handle search AJAX request
     */
    public static function handle_search_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_search_nonce')) {
            wp_die('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $max_results = intval($_POST['max_results']) ?: 10;
        $excerpt_length = intval($_POST['excerpt_length']) ?: 150;
        
        if (empty($query)) {
            wp_send_json_error('Search query is required');
        }
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            // Find relevant context
            $results = $rag_engine->find_relevant_context($query, $max_results, 0.3); // Lower threshold for search
            
            // Filter by content type if specified
            if (!empty($content_type)) {
                $results = array_filter($results, function($result) use ($content_type) {
                    return $result['content_type'] === $content_type;
                });
                $results = array_values($results); // Reindex array
            }
            
            // Format results for display
            $formatted_results = array();
            foreach ($results as $result) {
                $excerpt = wp_trim_words($result['content'], $excerpt_length / 6); // Rough word count estimation
                
                $formatted_result = array(
                    'title' => $result['title'],
                    'excerpt' => $excerpt,
                    'content_type' => $result['content_type'],
                    'similarity' => round($result['similarity'] * 100, 1),
                    'url' => isset($result['metadata']['url']) ? $result['metadata']['url'] : '',
                    'metadata' => $result['metadata']
                );
                
                $formatted_results[] = $formatted_result;
            }
            
            wp_send_json_success(array(
                'results' => $formatted_results,
                'total_count' => count($formatted_results),
                'query' => $query
            ));
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Search Error: ' . $e->getMessage(), 'error');
            wp_send_json_error('Search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Render search results template
     */
    public static function render_search_results($results, $settings) {
        $output = '';
        
        if (empty($results)) {
            $output .= '<div class="aiohm-no-results">';
            $output .= '<div class="aiohm-no-results-icon">🔍</div>';
            $output .= '<p>' . $settings['strings']['no_results'] . '</p>';
            $output .= '</div>';
            return $output;
        }
        
        if ($settings['show_results_count']) {
            $output .= '<div class="aiohm-results-count">';
            $output .= sprintf($settings['strings']['results_count'], count($results));
            $output .= '</div>';
        }
        
        $output .= '<div class="aiohm-results-list">';
        
        foreach ($results as $result) {
            $output .= '<div class="aiohm-search-result">';
            
            // Result header
            $output .= '<div class="aiohm-result-header">';
            
            if (!empty($result['url'])) {
                $output .= '<h3 class="aiohm-result-title">';
                $output .= '<a href="' . esc_url($result['url']) . '" target="_blank">';
                $output .= esc_html($result['title']);
                $output .= '</a>';
                $output .= '</h3>';
            } else {
                $output .= '<h3 class="aiohm-result-title">' . esc_html($result['title']) . '</h3>';
            }
            
            if ($settings['show_content_type']) {
                $output .= '<span class="aiohm-content-type-badge aiohm-type-' . esc_attr($result['content_type']) . '">';
                $output .= esc_html(ucfirst($result['content_type']));
                $output .= '</span>';
            }
            
            $output .= '<div class="aiohm-similarity-score">';
            $output .= '<span class="aiohm-score-label">' . __('Relevance:', 'aiohm-kb-assistant') . '</span>';
            $output .= '<span class="aiohm-score-value">' . $result['similarity'] . '%</span>';
            $output .= '</div>';
            
            $output .= '</div>';
            
            // Result content
            $output .= '<div class="aiohm-result-content">';
            $output .= '<p>' . esc_html($result['excerpt']) . '</p>';
            $output .= '</div>';
            
            // Result metadata
            if (!empty($result['metadata'])) {
                $output .= '<div class="aiohm-result-meta">';
                
                if (isset($result['metadata']['categories']) && !empty($result['metadata']['categories'])) {
                    $output .= '<div class="aiohm-meta-item">';
                    $output .= '<strong>' . __('Categories:', 'aiohm-kb-assistant') . '</strong> ';
                    $output .= esc_html(implode(', ', $result['metadata']['categories']));
                    $output .= '</div>';
                }
                
                if (isset($result['metadata']['tags']) && !empty($result['metadata']['tags'])) {
                    $output .= '<div class="aiohm-meta-item">';
                    $output .= '<strong>' . __('Tags:', 'aiohm-kb-assistant') . '</strong> ';
                    $output .= esc_html(implode(', ', $result['metadata']['tags']));
                    $output .= '</div>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}
