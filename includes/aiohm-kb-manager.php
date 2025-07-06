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
            'title'        => __('Title', 'aiohm-kb-assistant'), // Correct: simple string label
            'content_type' => __('Content Type', 'aiohm-kb-assistant'), // Correct: simple string label
            'user_id'      => __('Visibility', 'aiohm-kb-assistant'), // Correct: simple string label
            'last_updated' => __('Last Updated', 'aiohm-kb-assistant'), // Correct: simple string label
            'scope_toggle' => __('Actions', 'aiohm-kb-assistant'),
        ];
    }
    
    /**
     * Defines which columns are sortable.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        $sortable_columns = [
            'title'        => ['title', false],          // Column slug => array(db column name, boolean initial order)
            'content_type' => ['content_type', false],
            'user_id'      => ['user_id', false],
            'last_updated' => ['created_at', true],      // default descending (newest first)
        ];
        return $sortable_columns;
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="entry_ids[]" value="%s" />', esc_attr($item['content_id']));
    }

    // `column_title` is not needed as column_default will handle it based on the column name.
    // function column_title($item) { return sprintf('<strong>%s</strong>', esc_html($item['title'])); }


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
        return [
            'bulk-delete' => __('Delete', 'aiohm-kb-assistant'),
            'make-public' => __('Make Selected Public', 'aiohm-kb-assistant'),
            'make-private' => __('Make Selected Private', 'aiohm-kb-assistant'),
        ];
    }

    function column_default($item, $column_name) {
        // $column_name will now always be a string (the column slug)
        switch ($column_name) {
            case 'content_type':
                $type = esc_html($item['content_type']);
                $display_type = $type;
                $type_class = '';

                // Enhance display for common types and assign classes
                if ($type === 'application/pdf') {
                    $display_type = 'PDF';
                    $type_class = 'type-pdf';
                } elseif ($type === 'application/json') {
                    $display_type = 'JSON';
                    $type_class = 'type-json';
                } elseif ($type === 'text/plain') {
                    $display_type = 'TXT';
                    $type_class = 'type-txt';
                } elseif ($type === 'text/csv') {
                    $display_type = 'CSV';
                    $type_class = 'type-csv';
                } elseif ($type === 'post') {
                    $display_type = 'Post';
                    $type_class = 'type-post';
                } elseif ($type === 'page') {
                    $display_type = 'Page';
                    $type_class = 'type-page';
                } elseif ($type === 'manual') {
                    $display_type = 'Manual';
                    $type_class = 'type-manual';
                } else {
                    if (strpos($type, '/') !== false) {
                        $main_type = explode('/', $type)[0];
                        $type_class = 'type-' . esc_attr($main_type);
                        $display_type = strtoupper($main_type);
                    } else {
                        $type_class = 'type-default';
                    }
                }
                
                return sprintf('<span class="aiohm-content-type-badge %s">%s</span>', $type_class, $display_type);

            case 'user_id':
                $visibility = $item['user_id'] == 0 ? 'Public' : 'Private';
                $visibility_class = $item['user_id'] == 0 ? 'visibility-public' : 'visibility-private';
                return sprintf('<span class="visibility-text %s">%s</span>', esc_attr($visibility_class), $visibility);

            case 'last_updated':
                return isset($item['created_at']) ? esc_html(date('Y-m-d H:i', strtotime($item['created_at']))) : 'N/A';
            case 'title': // Explicitly handle 'title' for its bold formatting
                return sprintf('<strong>%s</strong>', esc_html($item['title']));
            default:
                // For any other column not explicitly handled, just return the item's value
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    /**
     * Renders the custom filters in the table nav area.
     *
     * @param string $which The part of the table nav to render (top or bottom).
     */
    function extra_tablenav($which) {
        // Render filters at the top, on the same line as bulk actions
        if ($which === 'top') {
            ?>
            <div class="alignleft actions filters-block">
                <label for="filter-content-type" class="screen-reader-text">Filter by Content Type</label>
                <select name="content_type" id="filter-content-type">
                    <option value=""><?php _e('All Types', 'aiohm-kb-assistant'); ?></option>
                    <option value="post" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'post'); ?>><?php _e('Posts', 'aiohm-kb-assistant'); ?></option>
                    <option value="page" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'page'); ?>><?php _e('Pages', 'aiohm-kb-assistant'); ?></option>
                    <option value="application/pdf" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'application/pdf'); ?>><?php _e('PDFs', 'aiohm-kb-assistant'); ?></option>
                    <option value="text/plain" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'text/plain'); ?>><?php _e('TXT', 'aiohm-kb-assistant'); ?></option>
                    <option value="text/csv" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'text/csv'); ?>><?php _e('CSV', 'aiohm-kb-assistant'); ?></option>
                    <option value="application/json" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'application/json'); ?>><?php _e('JSON', 'aiohm-kb-assistant'); ?></option>
                    <option value="manual" <?php selected(isset($_GET['content_type']) ? $_GET['content_type'] : '', 'manual'); ?>><?php _e('Manual Entries', 'aiohm-kb-assistant'); ?></option>
                </select>

                <label for="filter-visibility" class="screen-reader-text">Filter by Visibility</label>
                <select name="visibility" id="filter-visibility">
                    <option value=""><?php _e('All Visibility', 'aiohm-kb-assistant'); ?></option>
                    <option value="public" <?php selected(isset($_GET['visibility']) ? $_GET['visibility'] : '', 'public'); ?>><?php _e('Public', 'aiohm-kb-assistant'); ?></option>
                    <option value="private" <?php selected(isset($_GET['visibility']) ? $_GET['visibility'] : '', 'private'); ?>><?php _e('Private', 'aiohm-kb-assistant'); ?></option>
                </select>

                <label for="filter-date-range" class="screen-reader-text">Filter by Date Range</label>
                <select name="date_range" id="filter-date-range">
                    <option value=""><?php _e('All Dates', 'aiohm-kb-assistant'); ?></option>
                    <option value="last_7_days" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'last_7_days'); ?>><?php _e('Last 7 Days', 'aiohm-kb-assistant'); ?></option>
                    <option value="last_30_days" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'last_30_days'); ?>><?php _e('Last 30 Days', 'aiohm-kb-assistant'); ?></option>
                    <option value="this_month" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'this_month'); ?>><?php _e('This Month', 'aiohm-kb-assistant'); ?></option>
                    <option value="this_year" <?php selected(isset($_GET['date_range']) ? $_GET['date_range'] : '', 'this_year'); ?>><?php _e('This Year', 'aiohm-kb-assistant'); ?></option>
                </select>

                <?php submit_button(__('Filter'), 'button', false, false, ['id' => 'post-query-submit']); ?>
            </div>
            <?php
        }
    }


    function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $where_clauses = ['1=1'];
        $query_args = [];

        // Removed search handling as per user request
        // if (isset($_GET['s']) && !empty($_GET['s'])) { ... }

        if (isset($_GET['content_type']) && !empty($_GET['content_type'])) {
            $content_type = sanitize_text_field($_GET['content_type']);
            $where_clauses[] = "content_type = %s";
            $query_args[] = $content_type;
        }

        if (isset($_GET['visibility']) && !empty($_GET['visibility'])) {
            $visibility = sanitize_text_field($_GET['visibility']);
            if ($visibility === 'public') {
                $where_clauses[] = "user_id = 0";
            } elseif ($visibility === 'private') {
                $where_clauses[] = "user_id = %d";
                $query_args[] = get_current_user_id();
            }
        }

        if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
            $date_range = sanitize_text_field($current_time);
            $current_time = current_time('mysql');
            switch ($date_range) {
                case 'last_7_days':
                    $where_clauses[] = "created_at >= DATE_SUB(%s, INTERVAL 7 DAY)";
                    $query_args[] = $current_time;
                    break;
                case 'last_30_days':
                    $where_clauses[] = "created_at >= DATE_SUB(%s, INTERVAL 30 DAY)";
                    $query_args[] = $current_time;
                    break;
                case 'this_month':
                    $where_clauses[] = "YEAR(created_at) = YEAR(%s) AND MONTH(created_at) = MONTH(%s)";
                    $query_args[] = $current_time;
                    $query_args[] = $current_time;
                    break;
                case 'this_year':
                    $where_clauses[] = "YEAR(created_at) = YEAR(%s)";
                    $query_args[] = $current_time;
                    break;
            }
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Determine sorting
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) ? sanitize_sql_orderby($_GET['order']) : 'DESC';

        // Validate orderby against sortable columns to prevent SQL injection
        $sortable_columns = $this->get_sortable_columns();
        if (!array_key_exists($orderby, $sortable_columns)) {
            $orderby = 'id'; // Fallback to default if invalid orderby is provided
            $order = 'DESC';
        } else {
            $orderby = $sortable_columns[$orderby][0]; // Use the actual database column name
        }


        // Get total items count (respecting filters)
        $total_items_sql_base = "SELECT COUNT(DISTINCT content_id) FROM {$this->rag_engine->get_table_name()}";
        $total_items_query_args = $query_args;

        if (!empty($where_clauses)) {
            $total_items_sql = $total_items_sql_base . " WHERE {$where_sql}";
        } else {
            $total_items_sql = $total_items_sql_base;
        }

        if (empty($total_items_query_args)) {
            $total_items = (int) $wpdb->get_var($total_items_sql);
        } else {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($total_items_sql, $total_items_query_args));
        }

        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);

        $offset = ($current_page - 1) * $per_page;

        $sql = "SELECT id, title, content_type, user_id, content_id, created_at, metadata, content
                 FROM {$this->rag_engine->get_table_name()}";
        if (!empty($where_clauses)) {
            $sql .= " WHERE {$where_sql}";
        }
        $sql .= " GROUP BY content_id ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        array_push($query_args, $per_page, $offset);

        $this->items = $wpdb->get_results($wpdb->prepare($sql, $query_args), ARRAY_A);
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
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';

        if ('delete' === $current_action && isset($_GET['content_id']) && wp_verify_nonce($nonce, 'aiohm_delete_entry_nonce')) {
            if ($this->rag_engine->delete_entry_by_content_id(sanitize_text_field($_GET['content_id']))) {
                // Admin notice handled by JS in admin-manage-kb.php for single actions
            } else {
                // Notifying error in JS
            }
        } 
        elseif ('bulk-delete' === $current_action && isset($_POST['entry_ids']) && wp_verify_nonce($nonce, 'bulk-' . $this->list_table->_args['plural'])) {
            $deleted_count = 0;
            foreach (array_map('sanitize_text_field', $_POST['entry_ids']) as $content_id) {
                if ($this->rag_engine->delete_entry_by_content_id($content_id)) {
                    $deleted_count++;
                }
            }
            if ($deleted_count > 0) {
                // Notifying success in JS
            } else {
                // Notifying error in JS
            }
        }
    }
}