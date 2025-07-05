<?php
/**
 * Handles the display and processing of the "Manage Knowledge Base" admin page.
 * This version fixes the "Undefined array key" warning.
 */
if (!defined('ABSPATH')) exit;

// We need to extend the WP_List_Table class to display our data
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * AIOHM_KB_List_Table class to display KB entries in a WordPress-style table.
 */
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
            'user_id'      => __('Scope', 'aiohm-kb-assistant'),
            'content_id'   => __('Entry ID', 'aiohm-kb-assistant'),
        ];
    }
    
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="entry_ids[]" value="%s" />', esc_attr($item['content_id']));
    }

    function column_title($item) {
        $actions = [];
        $delete_nonce = wp_create_nonce('aiohm_delete_entry_nonce');
        $delete_url = sprintf('?page=%s&action=delete&content_id=%s&_wpnonce=%s', esc_attr($_REQUEST['page']), esc_attr($item['content_id']), $delete_nonce);
        
        // ** THE FIX IS HERE: Safely check if metadata and post_id exist before creating the link **
        $metadata = isset($item['metadata']) ? json_decode($item['metadata'], true) : null;
        if (is_array($metadata) && isset($metadata['post_id']) && get_post_type($metadata['post_id'])) {
            $actions['edit'] = sprintf('<a href="%s">Edit</a>', get_edit_post_link($metadata['post_id']));
        }
        
        $actions['delete'] = sprintf('<a href="%s" onclick="return confirm(\'Are you sure you want to delete all chunks for this entry?\')" style="color:#a00;">Delete</a>', $delete_url);
        
        return sprintf('<strong>%1$s</strong>%2$s', esc_html($item['title']), $this->row_actions($actions));
    }

    function get_bulk_actions() {
        return ['bulk-delete' => __('Delete', 'aiohm-kb-assistant')];
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'content_type':
            case 'content_id':
                return '<code>' . esc_html($item[$column_name]) . '</code>';
            case 'user_id':
                return $item[$column_name] == 0 ? 'Global' : 'Personal (User #' . $item[$column_name] . ')';
            default:
                return '';
        }
    }

    function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $this->rag_engine->get_total_entries_count();
        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
        $this->items = $this->rag_engine->get_all_entries_paginated($per_page, $current_page);
    }
}


/**
 * AIOHM_KB_Manager class.
 * This is the main controller for the "Manage KB" page.
 */
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
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/admin-manage-kb.php';
    }

    private function handle_actions() {
        $current_action = $this->list_table->current_action();

        if ('delete' === $current_action && isset($_GET['content_id']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'aiohm_delete_entry_nonce')) {
            $this->rag_engine->delete_entry_by_content_id(sanitize_text_field($_GET['content_id']));
            echo '<div class="notice notice-success is-dismissible"><p>Entry deleted successfully.</p></div>';
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