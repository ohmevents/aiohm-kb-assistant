<?php
/**
 * Knowledge Base Manager - Visual management interface for embedded knowledge entries
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Manager {
    
    private $rag_engine;
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }
    
    public static function init() {
        $instance = new self();
        add_action('wp_ajax_aiohm_kb_delete_entry', array($instance, 'delete_entry_ajax'));
        add_action('wp_ajax_aiohm_kb_add_entry', array($instance, 'add_entry_ajax'));
        add_action('wp_ajax_aiohm_kb_search_entries', array($instance, 'search_entries_ajax'));
    }
    
    /**
     * Display knowledge base management page
     */
    public function display_knowledge_base_page() {
        $entries = $this->rag_engine->get_all_entries();
        $stats = $this->rag_engine->get_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Knowledge Base Manager', 'aiohm-kb-assistant'); ?></h1>
            
            <div class="aiohm-kb-header">
                <div class="aiohm-kb-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['total_entries']; ?></div>
                        <div class="stat-label"><?php _e('Total Entries', 'aiohm-kb-assistant'); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['total_chunks']; ?></div>
                        <div class="stat-label"><?php _e('Total Chunks', 'aiohm-kb-assistant'); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo size_format($stats['total_content_length']); ?></div>
                        <div class="stat-label"><?php _e('Content Size', 'aiohm-kb-assistant'); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['average_chunks_per_entry'], 1); ?></div>
                        <div class="stat-label"><?php _e('Avg Chunks/Entry', 'aiohm-kb-assistant'); ?></div>
                    </div>
                </div>
                
                <div class="aiohm-kb-actions">
                    <button type="button" class="button button-primary" id="add-entry-btn">
                        <?php _e('Add New Entry', 'aiohm-kb-assistant'); ?>
                    </button>
                    <button type="button" class="button" id="refresh-entries-btn">
                        <?php _e('Refresh', 'aiohm-kb-assistant'); ?>
                    </button>
                </div>
            </div>
            
            <div class="aiohm-kb-filters">
                <div class="filter-group">
                    <label for="content-type-filter"><?php _e('Filter by Type:', 'aiohm-kb-assistant'); ?></label>
                    <select id="content-type-filter">
                        <option value=""><?php _e('All Types', 'aiohm-kb-assistant'); ?></option>
                        <?php foreach ($stats['by_type'] as $type => $type_stats): ?>
                        <option value="<?php echo esc_attr($type); ?>">
                            <?php echo esc_html(ucfirst($type)); ?> (<?php echo $type_stats['count']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search-entries"><?php _e('Search:', 'aiohm-kb-assistant'); ?></label>
                    <input type="text" id="search-entries" placeholder="<?php _e('Search entries...', 'aiohm-kb-assistant'); ?>" />
                </div>
            </div>
            
            <div class="aiohm-kb-entries-container">
                <?php if (empty($entries)): ?>
                <div class="aiohm-empty-state">
                    <div class="empty-icon">📚</div>
                    <h3><?php _e('No Knowledge Base Entries', 'aiohm-kb-assistant'); ?></h3>
                    <p><?php _e('Start by scanning your website content or uploading files to build your knowledge base.', 'aiohm-kb-assistant'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=aiohm-scan-website'); ?>" class="button button-primary">
                        <?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="aiohm-entries-table-container">
                    <table class="wp-list-table widefat fixed striped" id="aiohm-entries-table">
                        <thead>
                            <tr>
                                <th class="column-title"><?php _e('Title', 'aiohm-kb-assistant'); ?></th>
                                <th class="column-type"><?php _e('Type', 'aiohm-kb-assistant'); ?></th>
                                <th class="column-chunks"><?php _e('Chunks', 'aiohm-kb-assistant'); ?></th>
                                <th class="column-size"><?php _e('Size', 'aiohm-kb-assistant'); ?></th>
                                <th class="column-date"><?php _e('Updated', 'aiohm-kb-assistant'); ?></th>
                                <th class="column-actions"><?php _e('Actions', 'aiohm-kb-assistant'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry_id => $entry): ?>
                            <tr data-entry-id="<?php echo esc_attr($entry_id); ?>" data-content-type="<?php echo esc_attr($entry['content_type']); ?>">
                                <td class="column-title">
                                    <strong><?php echo esc_html($entry['title']); ?></strong>
                                    <?php if (!empty($entry['metadata']['url'])): ?>
                                    <div class="entry-url">
                                        <a href="<?php echo esc_url($entry['metadata']['url']); ?>" target="_blank" rel="noopener">
                                            <?php echo esc_html($entry['metadata']['url']); ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-type">
                                    <span class="content-type-badge content-type-<?php echo esc_attr($entry['content_type']); ?>">
                                        <?php echo esc_html(ucfirst($entry['content_type'])); ?>
                                    </span>
                                </td>
                                <td class="column-chunks"><?php echo count($entry['chunks']); ?></td>
                                <td class="column-size"><?php echo size_format(strlen($entry['original_content'])); ?></td>
                                <td class="column-date"><?php echo esc_html(mysql2date('M j, Y g:i A', $entry['updated_at'])); ?></td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small view-entry-btn" data-entry-id="<?php echo esc_attr($entry_id); ?>">
                                        <?php _e('View', 'aiohm-kb-assistant'); ?>
                                    </button>
                                    <button type="button" class="button button-small delete-entry-btn" data-entry-id="<?php echo esc_attr($entry_id); ?>">
                                        <?php _e('Delete', 'aiohm-kb-assistant'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add Entry Modal -->
        <div id="aiohm-add-entry-modal" class="aiohm-modal" style="display: none;">
            <div class="aiohm-modal-content">
                <div class="aiohm-modal-header">
                    <h2><?php _e('Add New Knowledge Entry', 'aiohm-kb-assistant'); ?></h2>
                    <button type="button" class="aiohm-modal-close">&times;</button>
                </div>
                <div class="aiohm-modal-body">
                    <form id="aiohm-add-entry-form">
                        <div class="form-group">
                            <label for="entry-title"><?php _e('Title', 'aiohm-kb-assistant'); ?></label>
                            <input type="text" id="entry-title" name="title" required class="regular-text" />
                        </div>
                        
                        <div class="form-group">
                            <label for="entry-type"><?php _e('Content Type', 'aiohm-kb-assistant'); ?></label>
                            <select id="entry-type" name="content_type" required>
                                <option value="manual"><?php _e('Manual Entry', 'aiohm-kb-assistant'); ?></option>
                                <option value="faq"><?php _e('FAQ', 'aiohm-kb-assistant'); ?></option>
                                <option value="documentation"><?php _e('Documentation', 'aiohm-kb-assistant'); ?></option>
                                <option value="other"><?php _e('Other', 'aiohm-kb-assistant'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="entry-content"><?php _e('Content', 'aiohm-kb-assistant'); ?></label>
                            <textarea id="entry-content" name="content" required rows="10" class="large-text"></textarea>
                            <p class="description"><?php _e('Enter the knowledge content that will be used to answer questions.', 'aiohm-kb-assistant'); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="entry-tags"><?php _e('Tags (optional)', 'aiohm-kb-assistant'); ?></label>
                            <input type="text" id="entry-tags" name="tags" class="regular-text" placeholder="<?php _e('Comma-separated tags', 'aiohm-kb-assistant'); ?>" />
                        </div>
                    </form>
                </div>
                <div class="aiohm-modal-footer">
                    <button type="button" class="button" id="cancel-add-entry"><?php _e('Cancel', 'aiohm-kb-assistant'); ?></button>
                    <button type="button" class="button button-primary" id="save-entry"><?php _e('Add Entry', 'aiohm-kb-assistant'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- View Entry Modal -->
        <div id="aiohm-view-entry-modal" class="aiohm-modal" style="display: none;">
            <div class="aiohm-modal-content aiohm-modal-large">
                <div class="aiohm-modal-header">
                    <h2 id="view-entry-title"><?php _e('View Entry', 'aiohm-kb-assistant'); ?></h2>
                    <button type="button" class="aiohm-modal-close">&times;</button>
                </div>
                <div class="aiohm-modal-body">
                    <div id="view-entry-content"></div>
                </div>
            </div>
        </div>
        
        <style>
        .aiohm-kb-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
        }
        
        .aiohm-kb-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            min-width: 80px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007cba;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .aiohm-kb-filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #ccd0d4;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 600;
            margin: 0;
        }
        
        .content-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .content-type-post {
            background: #e7f3ff;
            color: #0073aa;
        }
        
        .content-type-page {
            background: #f0f6fc;
            color: #0969da;
        }
        
        .content-type-menu {
            background: #fff8e7;
            color: #b8860b;
        }
        
        .content-type-pdf {
            background: #ffe7e7;
            color: #d63384;
        }
        
        .content-type-image {
            background: #e7ffe7;
            color: #198754;
        }
        
        .content-type-manual {
            background: #f3e7ff;
            color: #6f42c1;
        }
        
        .entry-url {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .aiohm-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .aiohm-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .aiohm-modal-content {
            background-color: #fff;
            margin: 5% auto;
            border: 1px solid #ccd0d4;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        
        .aiohm-modal-large {
            max-width: 900px;
        }
        
        .aiohm-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ccd0d4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .aiohm-modal-header h2 {
            margin: 0;
        }
        
        .aiohm-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .aiohm-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .aiohm-modal-footer {
            padding: 20px;
            border-top: 1px solid #ccd0d4;
            text-align: right;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        #search-entries {
            width: 250px;
        }
        
        .column-actions {
            width: 120px;
        }
        
        .button-small {
            padding: 2px 8px;
            font-size: 11px;
            line-height: 1.4;
            height: auto;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var currentEntries = <?php echo json_encode($entries); ?>;
            
            // Filter functionality
            $('#content-type-filter, #search-entries').on('change keyup', function() {
                filterEntries();
            });
            
            function filterEntries() {
                var typeFilter = $('#content-type-filter').val();
                var searchFilter = $('#search-entries').val().toLowerCase();
                
                $('#aiohm-entries-table tbody tr').each(function() {
                    var $row = $(this);
                    var entryType = $row.data('content-type');
                    var entryTitle = $row.find('.column-title').text().toLowerCase();
                    
                    var showRow = true;
                    
                    if (typeFilter && entryType !== typeFilter) {
                        showRow = false;
                    }
                    
                    if (searchFilter && entryTitle.indexOf(searchFilter) === -1) {
                        showRow = false;
                    }
                    
                    $row.toggle(showRow);
                });
            }
            
            // Add entry modal
            $('#add-entry-btn').click(function() {
                $('#aiohm-add-entry-modal').show();
            });
            
            $('#cancel-add-entry, .aiohm-modal-close').click(function() {
                $('.aiohm-modal').hide();
                $('#aiohm-add-entry-form')[0].reset();
            });
            
            // Save new entry
            $('#save-entry').click(function() {
                var $btn = $(this);
                var formData = {
                    action: 'aiohm_kb_add_entry',
                    nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>',
                    title: $('#entry-title').val(),
                    content_type: $('#entry-type').val(),
                    content: $('#entry-content').val(),
                    tags: $('#entry-tags').val()
                };
                
                if (!formData.title || !formData.content) {
                    alert('<?php _e('Please fill in all required fields.', 'aiohm-kb-assistant'); ?>');
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php _e('Adding...', 'aiohm-kb-assistant'); ?>');
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        $('#aiohm-add-entry-modal').hide();
                        $('#aiohm-add-entry-form')[0].reset();
                        location.reload(); // Refresh to show new entry
                    } else {
                        alert('<?php _e('Error adding entry:', 'aiohm-kb-assistant'); ?> ' + response.data);
                    }
                }).fail(function() {
                    alert('<?php _e('Failed to add entry', 'aiohm-kb-assistant'); ?>');
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Add Entry', 'aiohm-kb-assistant'); ?>');
                });
            });
            
            // View entry
            $('.view-entry-btn').click(function() {
                var entryId = $(this).data('entry-id');
                var entry = currentEntries[entryId];
                
                if (entry) {
                    $('#view-entry-title').text(entry.title);
                    
                    var content = '<div class="entry-details">';
                    content += '<p><strong><?php _e('Type:', 'aiohm-kb-assistant'); ?></strong> ' + entry.content_type + '</p>';
                    content += '<p><strong><?php _e('Chunks:', 'aiohm-kb-assistant'); ?></strong> ' + entry.chunks.length + '</p>';
                    content += '<p><strong><?php _e('Created:', 'aiohm-kb-assistant'); ?></strong> ' + entry.created_at + '</p>';
                    content += '<p><strong><?php _e('Updated:', 'aiohm-kb-assistant'); ?></strong> ' + entry.updated_at + '</p>';
                    content += '</div>';
                    
                    content += '<h4><?php _e('Content:', 'aiohm-kb-assistant'); ?></h4>';
                    content += '<div class="entry-content-preview">' + entry.original_content.substring(0, 1000);
                    if (entry.original_content.length > 1000) {
                        content += '...';
                    }
                    content += '</div>';
                    
                    if (entry.metadata) {
                        content += '<h4><?php _e('Metadata:', 'aiohm-kb-assistant'); ?></h4>';
                        content += '<pre>' + JSON.stringify(entry.metadata, null, 2) + '</pre>';
                    }
                    
                    $('#view-entry-content').html(content);
                    $('#aiohm-view-entry-modal').show();
                }
            });
            
            // Delete entry
            $('.delete-entry-btn').click(function() {
                if (!confirm('<?php _e('Are you sure you want to delete this entry?', 'aiohm-kb-assistant'); ?>')) {
                    return;
                }
                
                var entryId = $(this).data('entry-id');
                var $btn = $(this);
                var $row = $btn.closest('tr');
                
                $btn.prop('disabled', true).text('<?php _e('Deleting...', 'aiohm-kb-assistant'); ?>');
                
                $.post(ajaxurl, {
                    action: 'aiohm_kb_delete_entry',
                    nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>',
                    entry_id: entryId
                }, function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('<?php _e('Error deleting entry:', 'aiohm-kb-assistant'); ?> ' + response.data);
                        $btn.prop('disabled', false).text('<?php _e('Delete', 'aiohm-kb-assistant'); ?>');
                    }
                }).fail(function() {
                    alert('<?php _e('Failed to delete entry', 'aiohm-kb-assistant'); ?>');
                    $btn.prop('disabled', false).text('<?php _e('Delete', 'aiohm-kb-assistant'); ?>');
                });
            });
            
            // Refresh entries
            $('#refresh-entries-btn').click(function() {
                location.reload();
            });
            
            // Close modal when clicking outside
            $(window).click(function(event) {
                if ($(event.target).hasClass('aiohm-modal')) {
                    $('.aiohm-modal').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Delete entry AJAX handler
     */
    public function delete_entry_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $entry_id = sanitize_text_field($_POST['entry_id']);
        
        try {
            $result = $this->rag_engine->delete_entry($entry_id);
            
            if ($result) {
                wp_send_json_success('Entry deleted successfully');
            } else {
                wp_send_json_error('Entry not found');
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Error deleting entry: ' . $e->getMessage(), 'error');
            wp_send_json_error('Failed to delete entry: ' . $e->getMessage());
        }
    }
    
    /**
     * Add entry AJAX handler
     */
    public function add_entry_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $content = sanitize_textarea_field($_POST['content']);
        $tags = sanitize_text_field($_POST['tags']);
        
        if (empty($title) || empty($content)) {
            wp_send_json_error('Title and content are required');
        }
        
        try {
            $metadata = array(
                'source' => 'manual',
                'added_by' => get_current_user_id(),
                'tags' => !empty($tags) ? array_map('trim', explode(',', $tags)) : array()
            );
            
            $entry_id = $this->rag_engine->add_entry($content, $content_type, $title, $metadata);
            
            wp_send_json_success(array(
                'message' => 'Entry added successfully',
                'entry_id' => $entry_id
            ));
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Error adding entry: ' . $e->getMessage(), 'error');
            wp_send_json_error('Failed to add entry: ' . $e->getMessage());
        }
    }
    
    /**
     * Search entries AJAX handler
     */
    public function search_entries_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $max_results = intval($_POST['max_results']) ?: 10;
        
        try {
            $results = $this->rag_engine->find_relevant_context($query, $max_results);
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Error searching entries: ' . $e->getMessage(), 'error');
            wp_send_json_error('Search failed: ' . $e->getMessage());
        }
    }
}
