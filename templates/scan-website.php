<?php
/**
 * Scan website content template for AIOHM Knowledge Assistant
 *
 * @package AIOHM_KB_Assistant
 * @author OHM Events <info@ohmevents.com>
 * @version 1.0.0
 * @created 2025-07-02 12:28:43
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?></h1>
    
    <div class="aiohm-scan-container">
        <!-- Website Content Section -->
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
                        <?php _e('Estimated time remaining:', 'aiohm-kb-assistant'); ?> 
                        <span class="aiohm-time-remaining"></span>
                    </div>
                    <div class="aiohm-progress-speed">
                        <?php _e('Processing speed:', 'aiohm-kb-assistant'); ?> 
                        <span class="aiohm-items-per-minute"></span> 
                        <?php _e('items/minute', 'aiohm-kb-assistant'); ?>
                    </div>
                </div>
            </div>
            
            <div id="website-scan-results"></div>
        </div>
        
        <!-- Upload Folder Section -->
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
                        <?php _e('Scanning:', 'aiohm-kb-assistant'); ?> 
                        <span class="aiohm-scanning-item"></span>
                    </div>
                    <div class="aiohm-progress-time">
                        <?php _e('Estimated time remaining:', 'aiohm-kb-assistant'); ?> 
                        <span class="aiohm-time-remaining"></span>
                    </div>
                    <div class="aiohm-progress-speed">
                        <?php _e('Processing speed:', 'aiohm-kb-assistant'); ?> 
                        <span class="aiohm-items-per-minute"></span> 
                        <?php _e('items/minute', 'aiohm-kb-assistant'); ?>
                    </div>
                </div>
            </div>
            
            <div id="uploads-scan-results"></div>
        </div>
    </div>
    <?php
// Add this right before the closing </div> in scan-website.php
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Scan page initialized'); // Debug log
    
    // Progressive scanning function
    function startProgressiveScan(scanType) {
        console.log('Starting scan:', scanType); // Debug log
        
        const batchSize = 5;
        let currentOffset = 0;
        let progressId = '#' + scanType + '-scan-progress';
        let resultsId = '#' + scanType + '-scan-results';
        let buttonId = '#scan-' + scanType + '-btn';
        
        // Show progress bar and disable button
        $(progressId).show();
        $(resultsId).html('');
        $(buttonId).prop('disabled', true).text('<?php _e('Scanning...', 'aiohm-kb-assistant'); ?>');
        
        // Function to process a batch
        function processBatch() {
            console.log('Processing batch:', currentOffset); // Debug log
            
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
                    console.log('Batch response:', response); // Debug log
                    
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
                        
                        if (!progress.is_complete) {
                            processBatch();
                        } else {
                            // Show completion message
                            let html = '<div class="notice notice-success"><p><?php _e('Scan completed successfully!', 'aiohm-kb-assistant'); ?></p>';
                            html += '<p><strong>' + currentOffset + ' items processed</strong></p>';
                            html += '<p>' + (scanType === 'website' ? 
                                '<?php _e('Website content has been indexed.', 'aiohm-kb-assistant'); ?>' : 
                                '<?php _e('Upload folder has been indexed.', 'aiohm-kb-assistant'); ?>') + '</p>';
                            html += '</div>';
                            
                            $(resultsId).html(html);
                            $(buttonId).prop('disabled', false).text(scanType === 'website' ? 
                                '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : 
                                '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                            
                            // Hide progress after delay
                            setTimeout(function() {
                                $(progressId).hide();
                                window.location.reload(); // Refresh to update stats
                            }, 3000);
                        }
                    } else {
                        console.error('Scan error:', response.data); // Debug log
                        $(resultsId).html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        $(buttonId).prop('disabled', false).text(scanType === 'website' ? 
                            '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : 
                            '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                        $(progressId).hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error); // Debug log
                    $(resultsId).html('<div class="notice notice-error"><p><?php _e('Scan failed. Please try again.', 'aiohm-kb-assistant'); ?></p></div>');
                    $(buttonId).prop('disabled', false).text(scanType === 'website' ? 
                        '<?php _e('Scan Website Content', 'aiohm-kb-assistant'); ?>' : 
                        '<?php _e('Scan Upload Folder', 'aiohm-kb-assistant'); ?>');
                    $(progressId).hide();
                }
            });
        }
        
        // Start the first batch
        processBatch();
    }
    
    // Bind click events
    $('#scan-website-btn').on('click', function() {
        console.log('Website scan button clicked'); // Debug log
        startProgressiveScan('website');
    });
    
    $('#scan-uploads-btn').on('click', function() {
        console.log('Uploads scan button clicked'); // Debug log
        startProgressiveScan('uploads');
    });
});
</script>
</div>

<style>
/* These styles complement the main aiohm-chat.css file */
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
.aiohm-progress-bar-container {
    height: 20px;
    background-color: #e9ecef;
    border-radius: 4px;
    margin: 15px 0;
    overflow: hidden;
}
.aiohm-progress-bar {
    height: 100%;
    background-color: #007cba;
    width: 0%;
    transition: width 0.3s ease;
}
.aiohm-progress-details {
    font-size: 13px;
    line-height: 1.5;
}
</style>