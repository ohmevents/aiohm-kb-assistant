<?php
/**
 * Admin settings page and Q&A generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add this to your settings_page() method in the sidebar
echo '<div class="aiohm-widget">
    <h3>' . __('ARMember Integration', 'aiohm-kb-assistant') . '</h3>
    <p>' . __('Sync user membership data with knowledge base access levels.', 'aiohm-kb-assistant') . '</p>
    <button type="button" class="button" id="sync-armember-users">
        ' . __('Sync All Users', 'aiohm-kb-assistant') . '
    </button>
    <div id="armember-sync-results"></div>
</div>';

// Add JavaScript for sync functionality
echo '<script>
jQuery(document).ready(function($) {
    $("#sync-armember-users").click(function() {
        var $btn = $(this);
        var $results = $("#armember-sync-results");
        
        $btn.prop("disabled", true).text("' . __('Syncing...', 'aiohm-kb-assistant') . '");
        $results.html("<div class=\"notice notice-info\"><p>' . __('Syncing ARMember users...', 'aiohm-kb-assistant') . '</p></div>");
        
        $.post(ajaxurl, {
            action: "aiohm_sync_armember_users",
            nonce: "' . wp_create_nonce('aiohm_admin_nonce') . '"
        }, function(response) {
            if (response.success) {
                $results.html("<div class=\"notice notice-success\"><p>" + response.data.message + "</p></div>");
            } else {
                $results.html("<div class=\"notice notice-error\"><p>" + response.data + "</p></div>");
            }
        }).fail(function() {
            $results.html("<div class=\"notice notice-error\"><p>' . __('Sync failed', 'aiohm-kb-assistant') . '</p></div>");
        }).always(function() {
            $btn.prop("disabled", false).text("' . __('Sync All Users', 'aiohm-kb-assistant') . '");
        });
    });
});
</script>';


class AIOHM_KB_Settings_Page {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('wp_ajax_aiohm_generate_qa', array(__CLASS__, 'generate_qa_ajax'));
        add_action('wp_ajax_aiohm_test_api', array(__CLASS__, 'test_api_ajax'));
        add_action('wp_ajax_aiohm_export_data', array(__CLASS__, 'export_data_ajax'));
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('AIOHM Settings', 'aiohm-kb-assistant'),
            __('AIOHM Settings', 'aiohm-kb-assistant'),
            'manage_options',
            'aiohm-settings',
            array(__CLASS__, 'settings_page'),
            'dashicons-admin-generic',
            30
        );
        
        add_submenu_page(
            'aiohm-settings',
            __('Scan Website', 'aiohm-kb-assistant'),
            __('Scan Website', 'aiohm-kb-assistant'),
            'manage_options',
            'aiohm-scan-website',
            array(__CLASS__, 'scan_website_page')
        );
        
        add_submenu_page(
            'aiohm-settings',
            __('Knowledge Base', 'aiohm-kb-assistant'),
            __('Knowledge Base', 'aiohm-kb-assistant'),
            'manage_options',
            'aiohm-knowledge-base',
            array(__CLASS__, 'knowledge_base_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('aiohm_kb_settings', 'aiohm_kb_settings', array(__CLASS__, 'sanitize_settings'));
        
        // API Settings Section
        add_settings_section(
            'aiohm_api_section',
            __('API Configuration', 'aiohm-kb-assistant'),
            array(__CLASS__, 'api_section_callback'),
            'aiohm_kb_settings'
        );
        
        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'aiohm-kb-assistant'),
            array(__CLASS__, 'openai_api_key_callback'),
            'aiohm_kb_settings',
            'aiohm_api_section'
        );
        
        add_settings_field(
            'claude_api_key',
            __('Claude API Key', 'aiohm-kb-assistant'),
            array(__CLASS__, 'claude_api_key_callback'),
            'aiohm_kb_settings',
            'aiohm_api_section'
        );
        
        add_settings_field(
            'default_model',
            __('Default Model', 'aiohm-kb-assistant'),
            array(__CLASS__, 'default_model_callback'),
            'aiohm_kb_settings',
            'aiohm_api_section'
        );
        
        // Chat Settings Section
        add_settings_section(
            'aiohm_chat_section',
            __('Chat Configuration', 'aiohm-kb-assistant'),
            array(__CLASS__, 'chat_section_callback'),
            'aiohm_kb_settings'
        );
        
        add_settings_field(
            'chat_enabled',
            __('Enable Chat', 'aiohm-kb-assistant'),
            array(__CLASS__, 'chat_enabled_callback'),
            'aiohm_kb_settings',
            'aiohm_chat_section'
        );
        
        add_settings_field(
            'max_tokens',
            __('Max Tokens', 'aiohm-kb-assistant'),
            array(__CLASS__, 'max_tokens_callback'),
            'aiohm_kb_settings',
            'aiohm_chat_section'
        );
        
        add_settings_field(
            'temperature',
            __('Temperature', 'aiohm-kb-assistant'),
            array(__CLASS__, 'temperature_callback'),
            'aiohm_kb_settings',
            'aiohm_chat_section'
        );
        
        // Processing Settings Section
        add_settings_section(
            'aiohm_processing_section',
            __('Processing Configuration', 'aiohm-kb-assistant'),
            array(__CLASS__, 'processing_section_callback'),
            'aiohm_kb_settings'
        );
        
        add_settings_field(
            'chunk_size',
            __('Chunk Size', 'aiohm-kb-assistant'),
            array(__CLASS__, 'chunk_size_callback'),
            'aiohm_kb_settings',
            'aiohm_processing_section'
        );
        
        add_settings_field(
            'chunk_overlap',
            __('Chunk Overlap', 'aiohm-kb-assistant'),
            array(__CLASS__, 'chunk_overlap_callback'),
            'aiohm_kb_settings',
            'aiohm_processing_section'
        );
    }
    
    /**
     * Main settings page
     */
    public static function settings_page() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('AIOHM Knowledge Assistant Settings', 'aiohm-kb-assistant'); ?></h1>
            
            <div class="aiohm-admin-container">
                <div class="aiohm-main-content">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('aiohm_kb_settings');
                        do_settings_sections('aiohm_kb_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="aiohm-sidebar">
                    <div class="aiohm-widget">
                        <h3><?php _e('API Status', 'aiohm-kb-assistant'); ?></h3>
                        <div id="aiohm-api-status">
                            <button type="button" class="button" id="test-apis-btn">
                                <?php _e('Test API Connections', 'aiohm-kb-assistant'); ?>
                            </button>
                            <div id="api-test-results"></div>
                        </div>
                    </div>
                    
                    <div class="aiohm-widget">
                        <h3><?php _e('Q&A Dataset Generator', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Generate a Q&A dataset from your knowledge base for training external assistants.', 'aiohm-kb-assistant'); ?></p>
                        <button type="button" class="button button-primary" id="generate-qa-btn">
                            <?php _e('Generate Q&A Dataset', 'aiohm-kb-assistant'); ?>
                        </button>
                        <div id="qa-generation-results"></div>
                    </div>
                    
                    <div class="aiohm-widget">
                        <h3><?php _e('Export Data', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Export your knowledge base for backup or migration.', 'aiohm-kb-assistant'); ?></p>
                        <button type="button" class="button" id="export-data-btn">
                            <?php _e('Export Knowledge Base', 'aiohm-kb-assistant'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Test API connections
            $('#test-apis-btn').click(function() {
                var $btn = $(this);
                var $results = $('#api-test-results');
                
                $btn.prop('disabled', true).text('<?php _e('Testing...', 'aiohm-kb-assistant'); ?>');
                $results.html('<div class="notice notice-info"><p><?php _e('Testing API connections...', 'aiohm-kb-assistant'); ?></p></div>');
                
                $.post(ajaxurl, {
                    action: 'aiohm_test_api',
                    nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p><?php _e('API Test Results:', 'aiohm-kb-assistant'); ?></p>';
                        $.each(response.data, function(model, result) {
                            html += '<p><strong>' + model + ':</strong> ' + (result.valid ? '✓ Working' : '✗ Failed') + '</p>';
                        });
                        html += '</div>';
                        $results.html(html);
                    } else {
                        $results.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                }).fail(function() {
                    $results.html('<div class="notice notice-error"><p><?php _e('Failed to test APIs', 'aiohm-kb-assistant'); ?></p></div>');
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Test API Connections', 'aiohm-kb-assistant'); ?>');
                });
            });
            
            // Generate Q&A dataset
            $('#generate-qa-btn').click(function() {
                var $btn = $(this);
                var $results = $('#qa-generation-results');
                
                $btn.prop('disabled', true).text('<?php _e('Generating...', 'aiohm-kb-assistant'); ?>');
                $results.html('<div class="notice notice-info"><p><?php _e('Generating Q&A dataset...', 'aiohm-kb-assistant'); ?></p></div>');
                
                $.post(ajaxurl, {
                    action: 'aiohm_generate_qa',
                    nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p><p><strong>Generated ' + response.data.count + ' Q&A pairs</strong></p></div>');
                    } else {
                        $results.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                }).fail(function() {
                    $results.html('<div class="notice notice-error"><p><?php _e('Failed to generate Q&A dataset', 'aiohm-kb-assistant'); ?></p></div>');
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Generate Q&A Dataset', 'aiohm-kb-assistant'); ?>');
                });
            });
            
            // Export data
            $('#export-data-btn').click(function() {
                var $btn = $(this);
                
                $btn.prop('disabled', true).text('<?php _e('Exporting...', 'aiohm-kb-assistant'); ?>');
                
                $.post(ajaxurl, {
                    action: 'aiohm_export_data',
                    nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Create download link
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'aiohm-knowledge-base-export.json';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('<?php _e('Export failed', 'aiohm-kb-assistant'); ?>: ' + response.data);
                    }
                }).fail(function() {
                    alert('<?php _e('Export failed', 'aiohm-kb-assistant'); ?>');
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Export Knowledge Base', 'aiohm-kb-assistant'); ?>');
                });
            });
        });
        </script>
        
        <style>
        .aiohm-admin-container {
            display: flex;
            gap: 20px;
        }
        .aiohm-main-content {
            flex: 2;
        }
        .aiohm-sidebar {
            flex: 1;
        }
        .aiohm-widget {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 15px;
            margin-bottom: 20px;
        }
        .aiohm-widget h3 {
            margin-top: 0;
        }
        #api-test-results, #qa-generation-results {
            margin-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Scan website page
     */
    public static function scan_website_page() {
        $site_crawler = new AIOHM_KB_Site_Crawler();
        $uploads_crawler = new AIOHM_KB_Uploads_Crawler();
        
        $site_stats = $site_crawler->get_scan_stats();
        $uploads_stats = $uploads_crawler->get_scan_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?></h1>
            
            <div class="aiohm-scan-container">
                <div class="aiohm-scan-section">
                    <h2><?php _e('Website Content', 'aiohm-kb-assistant'); ?></h2>
                    <div class="aiohm-stats">
                        <div class="stat-item">
                            <strong><?php _e('Posts:', 'aiohm-kb-assistant'); ?></strong>
                            <?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), 
                                $site_stats['posts']['total'], 
                                $site_stats['posts']['indexed'], 
                                $site_stats['posts']['pending']); ?>
                        </div>
                        <div class="stat-item">
                            <strong><?php _e('Pages:', 'aiohm-kb-assistant'); ?></strong>
                            <?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), 
                                $site_stats['pages']['total'], 
                                $site_stats['pages']['indexed'], 
                                $site_stats['pages']['pending']); ?>
                        </div>
                        <div class="stat-item">
                            <strong><?php _e('Menus:', 'aiohm-kb-assistant'); ?></strong>
                            <?php printf(__('%d total', 'aiohm-kb-assistant'), $site_stats['menus']['total']); ?>
                        </div>
                    </div>
                    
                    <button type="button" class="button button-primary" id="scan-website-btn" data-scan-type="website">
                        <?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>
                    </button>
                    
                    <div id="website-scan-progress" class="aiohm-scan-progress" style="display: none;">
                        <div class="aiohm-progress-header">
                            <h4><?php _e('Scan Progress', 'aiohm-kb-assistant'); ?></h4>
                            <span class="aiohm-progress-percentage">0%</span>
                        </div>
                        <div class="aiohm-progress-bar-container">
                            <div class="aiohm-progress-bar"></div>
                        </div>
                        <div class="aiohm-progress-details">
                            <div class="aiohm-progress-status">
                                <?php _e('Scanning:', 'aiohm-kb-assistant'); ?> <span class="aiohm-scanning-item"></span>
                            </div>
                            <div class="aiohm-progress-time">
                                <?php _e('Estimated time remaining:', 'aiohm-kb-assistant'); ?> <span class="aiohm-time-remaining"></span>
                            </div>
                            <div class="aiohm-progress-speed">
                                <?php _e('Processing speed:', 'aiohm-kb-assistant'); ?> <span class="aiohm-items-per-minute"></span> <?php _e('items/minute', 'aiohm-kb-assistant'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="website-scan-results"></div>
                </div>
                
                <div class="aiohm-scan-section">
                    <h2><?php _e('Upload Folder', 'aiohm-kb-assistant'); ?></h2>
                    <div class="aiohm-stats">
                        <div class="stat-item">
                            <strong><?php _e('Total Files:', 'aiohm-kb-assistant'); ?></strong>
                            <?php echo $uploads_stats['total_files']; ?>
                        </div>
                        <?php foreach ($uploads_stats['by_type'] as $type => $data): ?>
                        <div class="stat-item">
                            <strong><?php echo strtoupper($type); ?>:</strong>
                            <?php printf(__('%d files (%s)', 'aiohm-kb-assistant'), 
                                $data['count'], 
                                size_format($data['size'])); ?>
                        </div>
                        <?php endforeach; ?>
                        <div class="stat-item">
                            <strong><?php _e('Total Size:', 'aiohm-kb-assistant'); ?></strong>
                            <?php echo size_format($uploads_stats['total_size']); ?>
                        </div>
                    </div>
                    
                    <button type="button" class="button button-primary" id="scan-uploads-btn" data-scan-type="uploads">
                        <?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>
                    </button>
                    
                    <div id="uploads-scan-progress" class="aiohm-scan-progress" style="display: none;">
                        <div class="aiohm-progress-header">
                            <h4><?php _e('Scan Progress', 'aiohm-kb-assistant'); ?></h4>
                            <span class="aiohm-progress-percentage">0%</span>
                        </div>
                        <div class="aiohm-progress-bar-container">
                            <div class="aiohm-progress-bar"></div>
                        </div>
                        <div class="aiohm-progress-details">
                            <div class="aiohm-progress-status">
                                <?php _e('Scanning:', 'aiohm-kb-assistant'); ?> <span class="aiohm-scanning-item"></span>
                            </div>
                            <div class="aiohm-progress-time">
                                <?php _e('Estimated time remaining:', 'aiohm-kb-assistant'); ?> <span class="aiohm-time-remaining"></span>
                            </div>
                            <div class="aiohm-progress-speed">
                                <?php _e('Processing speed:', 'aiohm-kb-assistant'); ?> <span class="aiohm-items-per-minute"></span> <?php _e('items/minute', 'aiohm-kb-assistant'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="uploads-scan-results"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Progressive scanning function
            function startProgressiveScan(scanType) {
                const batchSize = 5;
                let currentOffset = 0;
                let isComplete = false;
                let progressId = '#' + scanType + '-scan-progress';
                let resultsId = '#' + scanType + '-scan-results';
                let buttonId = '#scan-' + scanType + '-btn';
                
                // Show progress bar
                $(progressId).show();
                $(resultsId).html('');
                $(buttonId).prop('disabled', true).text('<?php _e('Scanning...', 'aiohm-kb-assistant'); ?>');
                
                // Function to process a batch
                function processBatch() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aiohm_progressive_scan',
                            scan_type: scanType,
                            batch_size: batchSize,
                            current_offset: currentOffset,
                            nonce: '<?php echo wp_create_nonce('aiohm_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update progress
                                const progress = response.data.progress;
                                const percentage = progress.percentage;
                                
                                // Update UI
                                $(progressId + ' .aiohm-progress-percentage').text(percentage + '%');
                                $(progressId + ' .aiohm-progress-bar').css('width', percentage + '%');
                                $(progressId + ' .aiohm-scanning-item').text(progress.currently_scanning);
                                $(progressId + ' .aiohm-time-remaining').text(progress.estimated_time_remaining);
                                $(progressId + ' .aiohm-items-per-minute').text(progress.items_per_minute);
                                
                                // Update current offset and check if complete
                                currentOffset = progress.current_offset;
                                isComplete = progress.is_complete;
                                
                                // If not complete, process next batch
                                if (!isComplete) {
                                    processBatch();
                                } else {
                                    // Show completion message
                                    let html = '<div class="notice notice-success"><p><?php _e('Scan completed successfully!', 'aiohm-kb-assistant'); ?></p>';
                                    html += '<p><strong>Processed ' + currentOffset + ' items</strong></p>';
                                    if (scanType === 'website') {
                                        html += '<p>The website content has been indexed and is now available for the AI assistant.</p>';
                                    } else {
                                        html += '<p>The upload folder files have been indexed and are now available for the AI assistant.</p>';
                                    }
                                    html += '</div>';
                                    
                                    $(resultsId).html(html);
                                    $(buttonId).prop('disabled', false).text(scanType === 'website' ? '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                                    
                                    // Hide progress after a delay
                                    setTimeout(function() {
                                        $(progressId).hide();
                                        
                                        // Refresh page to update stats
                                        window.location.reload();
                                    }, 3000);
                                }
                            } else {
                                // Show error
                                $(resultsId).html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                                $(buttonId).prop('disabled', false).text(scanType === 'website' ? '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                                $(progressId).hide();
                            }
                        },
                        error: function() {
                            $(resultsId).html('<div class="notice notice-error"><p><?php _e('Scan failed. Please try again.', 'aiohm-kb-assistant'); ?></p></div>');
                            $(buttonId).prop('disabled', false).text(scanType === 'website' ? '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                            $(progressId).hide();
                        }
                    });
                }
                
                // Start the first batch
                processBatch();
            }
            
            // Scan website button click
            $('#scan-website-btn').click(function() {
                startProgressiveScan('website');
            });
            
            // Scan uploads button click
            $('#scan-uploads-btn').click(function() {
                startProgressiveScan('uploads');
            });
        });
        </script>
        
        <style>
        .aiohm-scan-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .aiohm-scan-section {
            flex: 1;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        .aiohm-stats {
            margin: 15px 0;
        }
        .stat-item {
            margin: 8px 0;
            padding: 8px;
            background: #f8f9fa;
            border-left: 3px solid #007cba;
        }
        .aiohm-scan-progress {
            margin: 20px 0;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .aiohm-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .aiohm-progress-header h4 {
            margin: 0;
        }
        .aiohm-progress-percentage {
            font-weight: bold;
            font-size: 16px;
        }
        .aiohm-progress-bar-container {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .aiohm-progress-bar {
            height: 100%;
            background-color: #007cba;
            width: 0%;
            transition: width 0.3s ease;
        }
        .aiohm-progress-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            font-size: 13px;
        }
        #website-scan-results, #uploads-scan-results {
            margin-top: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Knowledge base management page
     */
    public static function knowledge_base_page() {
        // This will be handled by the AIOHM_KB_Manager class
        $manager = new AIOHM_KB_Manager();
        $manager->display_knowledge_base_page();
    }
    
    /**
     * Handle progressive scan AJAX
     */
    public static function handle_progressive_scan_ajax() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $scan_type = sanitize_text_field($_POST['scan_type']);
        $batch_size = intval($_POST['batch_size'] ?? 5);
        $current_offset = intval($_POST['current_offset'] ?? 0);
        
        try {
            if ($scan_type === 'website') {
                $crawler = new AIOHM_KB_Site_Crawler();
                $results = $crawler->scan_website_with_progress($batch_size, $current_offset);
            } elseif ($scan_type === 'uploads') {
                $crawler = new AIOHM_KB_Uploads_Crawler();
                $results = $crawler->scan_uploads_with_progress($batch_size, $current_offset);
            } else {
                throw new Exception('Invalid scan type');
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Scan Error: ' . $e->getMessage(), 'error');
            wp_send_json_error('Scan failed: ' . $e->getMessage());
        }
    }
// Settings field callbacks
    public static function api_section_callback() {
        echo '<p>' . __('Configure your AI API keys for chat functionality.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function openai_api_key_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="password" id="openai_api_key" name="aiohm_kb_settings[openai_api_key]" value="' . esc_attr($settings['openai_api_key']) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your OpenAI API key for GPT models.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function claude_api_key_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="password" id="claude_api_key" name="aiohm_kb_settings[claude_api_key]" value="' . esc_attr($settings['claude_api_key']) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your Anthropic Claude API key.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function default_model_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        $models = array(
            'openai' => 'OpenAI GPT',
            'claude' => 'Anthropic Claude'
        );
        
        echo '<select id="default_model" name="aiohm_kb_settings[default_model]">';
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($settings['default_model'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Default AI model to use for chat responses.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function chat_section_callback() {
        echo '<p>' . __('Configure chat behavior and response settings.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function chat_enabled_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="checkbox" id="chat_enabled" name="aiohm_kb_settings[chat_enabled]" value="1"' . checked($settings['chat_enabled'], true, false) . ' />';
        echo '<label for="chat_enabled">' . __('Enable chat functionality on frontend', 'aiohm-kb-assistant') . '</label>';
    }
    
    public static function max_tokens_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="number" id="max_tokens" name="aiohm_kb_settings[max_tokens]" value="' . esc_attr($settings['max_tokens']) . '" min="50" max="2000" />';
        echo '<p class="description">' . __('Maximum tokens for AI responses (50-2000).', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function temperature_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="number" id="temperature" name="aiohm_kb_settings[temperature]" value="' . esc_attr($settings['temperature']) . '" min="0" max="2" step="0.1" />';
        echo '<p class="description">' . __('Response creativity (0.0 = focused, 2.0 = creative).', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function processing_section_callback() {
        echo '<p>' . __('Configure how content is processed and chunked for the knowledge base.', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function chunk_size_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="number" id="chunk_size" name="aiohm_kb_settings[chunk_size]" value="' . esc_attr($settings['chunk_size']) . '" min="500" max="3000" />';
        echo '<p class="description">' . __('Size of content chunks for processing (500-3000 characters).', 'aiohm-kb-assistant') . '</p>';
    }
    
    public static function chunk_overlap_callback() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        echo '<input type="number" id="chunk_overlap" name="aiohm_kb_settings[chunk_overlap]" value="' . esc_attr($settings['chunk_overlap']) . '" min="0" max="500" />';
        echo '<p class="description">' . __('Overlap between chunks to maintain context (0-500 characters).', 'aiohm-kb-assistant') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public static function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        $sanitized['claude_api_key'] = sanitize_text_field($input['claude_api_key']);
        $sanitized['default_model'] = in_array($input['default_model'], array('openai', 'claude')) ? $input['default_model'] : 'openai';
        $sanitized['chat_enabled'] = isset($input['chat_enabled']) ? true : false;
        $sanitized['auto_scan'] = isset($input['auto_scan']) ? true : false;
        $sanitized['max_tokens'] = intval($input['max_tokens']);
        $sanitized['temperature'] = floatval($input['temperature']);
        $sanitized['chunk_size'] = intval($input['chunk_size']);
        $sanitized['chunk_overlap'] = intval($input['chunk_overlap']);
        
        // Validate ranges
        $sanitized['max_tokens'] = max(50, min(2000, $sanitized['max_tokens']));
        $sanitized['temperature'] = max(0.0, min(2.0, $sanitized['temperature']));
        $sanitized['chunk_size'] = max(500, min(3000, $sanitized['chunk_size']));
        $sanitized['chunk_overlap'] = max(0, min(500, $sanitized['chunk_overlap']));
        
        return $sanitized;
    }
    
    /**
     * Generate Q&A dataset AJAX handler
     */
    public static function generate_qa_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $qa_dataset = $rag_engine->generate_qa_dataset();
            
            wp_send_json_success(array(
                'message' => 'Q&A dataset generated successfully',
                'count' => count($qa_dataset)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate Q&A dataset: ' . $e->getMessage());
        }
    }
    
    /**
     * Test API AJAX handler
     */
    public static function test_api_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        try {
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $validation = $ai_client->validate_api_keys();
            
            wp_send_json_success($validation);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to test APIs: ' . $e->getMessage());
        }
    }
    
    /**
     * Export data AJAX handler
     */
    public static function export_data_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $export_data = $rag_engine->export_knowledge_base();
            
            wp_send_json_success($export_data);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to export data: ' . $e->getMessage());
        }
    }
}