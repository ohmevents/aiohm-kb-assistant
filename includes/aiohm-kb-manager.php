<?php
/**
 * Handles the display and processing of the "Manage Knowledge Base" admin page.
 * This is the complete and final version of this file.
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class AIOHM_KB_List_Table extends WP_List_Table {
    private $rag_engine;

    function __construct() {
        parent::__construct(['singular' => 'kb_entry', 'plural' => 'kb_entries', 'ajax' => false]);
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'title'        => __('Title', 'aiohm-kb-assistant'),
            'content_type' => __('Content Type', 'aiohm-kb-assistant'),
            'user_id'      => __('Visibility', 'aiohm-kb-assistant'),
            'scope_toggle' => __('Actions', 'aiohm-kb-assistant'),
        ];
    }
    
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="entry_ids[]" value="%s" />', esc_attr($item['content_id']));
    }

    function column_title($item) {
        return sprintf('<strong>%s</strong>', esc_html($item['title']));
    }

    function column_scope_toggle($item) {
        $action_links_html = [];
        $current_user_id = get_current_user_id();
        $is_global = ($item['user_id'] == 0);
        $is_mine = ($item['user_id'] == $current_user_id);
        
        // Toggle Scope button
        if ($is_global || $is_mine) {
            $new_scope = $is_global ? 'private' : 'public';
            $button_text = $is_global ? 'Make Private' : 'Make Public';
            $action_links_html[] = sprintf(
                '<button type="button" class="button button-secondary button-small scope-toggle-btn" data-content-id="%s" data-new-scope="%s">%s</button>',
                esc_attr($item['content_id']),
                $new_scope,
                $button_text
            );
        }
        
        // View Original Source button
        $metadata = isset($item['metadata']) ? json_decode($item['metadata'], true) : null;
        if (is_array($metadata)) {
            $view_url = '';
            $view_text = __('View', 'aiohm-kb-assistant');

            if (isset($metadata['post_id']) && get_post_type($metadata['post_id'])) {
                $view_url = get_permalink($metadata['post_id']);
            } elseif (isset($metadata['attachment_id'])) {
                $view_url = wp_get_attachment_url($metadata['attachment_id']);
            } elseif (isset($metadata['url'])) { // For web content that isn't a WordPress post/page/attachment
                $view_url = $metadata['url'];
            }

            if ($view_url) {
                $action_links_html[] = sprintf('<a href="%s" target="_blank" class="button button-secondary button-small">%s</a>', esc_url($view_url), $view_text);
            }
        }

        // Delete button
        $delete_nonce = wp_create_nonce('aiohm_delete_entry_nonce');
        $delete_url = sprintf('?page=%s&action=delete&content_id=%s&_wpnonce=%s', esc_attr($_REQUEST['page']), esc_attr($item['content_id']), $delete_nonce);
        $action_links_html[] = sprintf('<a href="%s" onclick="return confirm(\'Are you sure you want to delete this entry?\')" class="button-link-delete" style="vertical-align: middle;">Delete</a>', $delete_url);
        
        return '<div style="display: flex; gap: 8px; align-items: center;">' . implode('', $action_links_html) . '</div>';
    }

    function get_bulk_actions() {
        return ['bulk-delete' => __('Delete', 'aiohm-kb-assistant')];
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'content_type':
                $type = esc_html($item[$column_name]);
                $display_type = $type;
                $type_class = '';

                // Enhance display for common types and assign classes
                if (strpos($type, 'application/') === 0) {
                    $sub_type = explode('/', $type)[1];
                    if ($sub_type === 'pdf') {
                        $display_type = 'PDF';
                        $type_class = 'type-application'; // Consistent with scan-website.php for PDFs
                    } elseif ($sub_type === 'json') {
                        $display_type = 'JSON';
                        $type_class = 'type-application'; // Using 'application' as a base type
                    }
                } elseif (strpos($type, 'text/') === 0) {
                    $sub_type = explode('/', $type)[1];
                     if ($sub_type === 'plain') {
                        $display_type = 'TXT';
                        $type_class = 'type-text'; // Consistent with scan-website.php for TXT
                    } elseif ($sub_type === 'csv') {
                        $display_type = 'CSV';
                        $type_class = 'type-text'; // Consistent with scan-website.php for CSV
                    }
                } elseif ($type === 'post') {
                    $display_type = 'Post';
                    $type_class = 'type-post';
                } elseif ($type === 'page') {
                    $display_type = 'Page';
                    $type_class = 'type-page';
                }
                
                // If a specific type class isn't set, try to derive from main mime type part
                if (empty($type_class) && strpos($type, '/') !== false) {
                     $main_type = explode('/', $type)[0];
                     $type_class = 'type-' . esc_attr($main_type);
                }
                
                return sprintf('<span class="aiohm-content-type-badge %s">%s</span>', $type_class, $display_type);

            case 'user_id':
                $visibility = $item[$column_name] == 0 ? 'Public' : 'Private';
                $visibility_class = $item[$column_name] == 0 ? 'visibility-public' : 'visibility-private';
                return sprintf('<span class="visibility-text %s">%s</span>', esc_attr($visibility_class), $visibility);
            default:
                return '';
        }
    }

    function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $this->rag_engine->get_total_entries_count();
        $total_items = $total_items ?? 0;
        $this->set_pagination_args(['total_items' => (int) $total_items, 'per_page' => $per_page]);
        $this->items = $this->rag_engine->get_all_entries_paginated($per_page, $current_page);
    }
}

class AIOHM_KB_Manager {
    private $rag_engine;
    private $list_table;

    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
        $this->list_table = new AIOHM_KB_List_Table();
    }
    
    public function display_page() {
        $this->handle_actions();
        $list_table = $this->list_table;
        $list_table->prepare_items();
        $settings = AIOHM_KB_Assistant::get_settings();
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/admin-manage-kb.php';
    }

    private function handle_actions() {
        $current_action = $this->list_table->current_action();
        if ('delete' === $current_action && isset($_GET['content_id']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'aiohm_delete_entry_nonce')) {
            if ($this->rag_engine->delete_entry_by_content_id(sanitize_text_field($_GET['content_id']))) {
                echo '<div class="notice notice-success is-dismissible"><p>Entry deleted successfully.</p></div>';
            }
        }
        if ('bulk-delete' === $current_action && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk-' . $this->list_table->_args['plural'])) {
            if (isset($_POST['entry_ids']) && is_array($_POST['entry_ids'])) {
                $deleted_count = 0;
                foreach ($_POST['entry_ids'] as $content_id) {
                    if ($this->rag_engine->delete_entry_by_content_id(sanitize_text_field($content_id))) {
                        $deleted_count++;
                    }
                }
                if ($deleted_count > 0) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf('%d entries deleted successfully.', $deleted_count) . '</p></div>';
                }
            }
        }
    }
}