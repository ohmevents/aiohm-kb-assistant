<?php
/**
 * Scan website content template - Branded Version with all functionality and styles restored.
 */
if (!defined('ABSPATH')) exit;

// The controller (settings-page.php) prepares these variables before including this template.
$api_key_exists = !empty(AIOHM_KB_Assistant::get_settings()['openai_api_key']);
$total_links = ($site_stats['posts']['total'] ?? 0) + ($site_stats['pages']['total'] ?? 0);
?>
<div class="wrap aiohm-scan-page" id="aiohm-scan-page">
    <h1><?php _e('Build Your Knowledge Base', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Scan your website\'s posts, pages, and media library to add content to your AI\'s knowledge base.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice is-dismissible" style="display:none; margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>

    <?php if (!$api_key_exists) : ?>
        <div class="notice notice-warning" style="padding: 15px; border-left-width: 4px;">
            <h3 style="margin-top: 0;"><?php _e('Action Required: Add Your API Key', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Content scanning is disabled because your OpenAI API key has not been configured. Please add your key to enable this feature.', 'aiohm-kb-assistant'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-primary"><?php _e('Go to Settings', 'aiohm-kb-assistant'); ?></a>
        </div>
    <?php endif; ?>

    <div class="aiohm-scan-section-wrapper" style="margin-bottom: 20px;">
        <div class="aiohm-scan-section">
            <h2><?php _e('Content Stats', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('An overview of all scannable content from your website and Media Library.', 'aiohm-kb-assistant'); ?></p>
            
            <div class="aiohm-stats-boxes">
                <div class="aiohm-stats-box">
                    <div class="stats-box-header">
                        <h4><?php _e('Website Content Breakdown', 'aiohm-kb-assistant'); ?></h4>
                    </div>
                    <div class="stats-box-content">
                        <div class="stat-item total-stat">
                            <strong><?php _e('Total Website Content:', 'aiohm-kb-assistant'); ?></strong>
                            <span class="stat-number"><?php echo esc_html($total_links); ?></span>
                            <span class="stat-label">(Posts + Pages)</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php _e('Posts:', 'aiohm-kb-assistant'); ?></strong>
                            <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['posts']['total'] ?? 0, $site_stats['posts']['indexed'] ?? 0, $site_stats['posts']['pending'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <strong><?php _e('Pages:', 'aiohm-kb-assistant'); ?></strong>
                            <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['pages']['total'] ?? 0, $site_stats['pages']['indexed'] ?? 0, $site_stats['pages']['pending'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="aiohm-stats-box">
                    <div class="stats-box-header">
                        <h4><?php _e('Media Library Breakdown', 'aiohm-kb-assistant'); ?></h4>
                    </div>
                    <div class="stats-box-content">
                        <div class="stat-item total-stat">
                            <strong><?php _e('Total Media Files:', 'aiohm-kb-assistant'); ?></strong>
                            <span class="stat-number"><?php echo esc_html($uploads_stats['total_files'] ?? 0); ?></span>
                            <span class="stat-label">(Indexed: <?php echo esc_html($uploads_stats['indexed_files'] ?? 0); ?>, Pending: <?php echo esc_html($uploads_stats['pending_files'] ?? 0); ?>)</span>
                        </div>
                    </div>
                    <?php if (!empty($uploads_stats['by_type'])) { foreach($uploads_stats['by_type'] as $type => $data) { $size_formatted = size_format($data['size'] ?? 0); echo '<div class="stat-item"><strong>' . esc_html(strtoupper($type)) . ' Files:</strong> <span>' . sprintf(__('%d total, %d indexed, %d pending (%s)', 'aiohm-kb-assistant'), $data['count'] ?? 0, $data['indexed'] ?? 0, $data['pending'] ?? 0, $size_formatted) . '</span></div>'; } } else { echo '<p>Supported files include .txt, .json, .csv, and .pdf from your Media Library.</p>'; } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="aiohm-scan-columns-wrapper">
        <div class="aiohm-scan-column">
            <div class="aiohm-scan-section">
                <h2><?php _e('Website Content Scanner', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Use the button to find or re-scan your posts and pages.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-website-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Re-Scan Posts & Pages', 'aiohm-kb-assistant'); ?></button>
                <div id="pending-content-area" style="margin-top: 20px;">
                    <h3><?php _e("Scan Results", 'aiohm-kb-assistant'); ?></h3>
                    <div id="scan-results-container"></div>
                    <button type="button" class="button button-primary" id="add-selected-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected to KB', 'aiohm-kb-assistant'); ?></button>
                    <div id="website-scan-progress" class="aiohm-scan-progress" style="display: none;"><div class="progress-info"><span class="progress-label">Processing...</span><span class="progress-percentage">0%</span></div><div class="progress-bar-wrapper"><div class="progress-bar-inner"></div></div></div>
                </div>
            </div>
        </div>
        <div class="aiohm-scan-column">
            <div class="aiohm-scan-section">
                <h2><?php _e('Upload Folder Scanner', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Scan your <strong>WordPress Media Library</strong> for readable files like .txt, .json, .csv, and .pdf.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-uploads-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Find Uploads', 'aiohm-kb-assistant'); ?></button>
                <div id="pending-uploads-area" style="margin-top: 20px;">
                    <h3><?php _e("Uploads Scan Results", 'aiohm-kb-assistant'); ?></h3>
                    <div id="scan-uploads-container"></div>
                    <button type="button" class="button button-primary" id="add-uploads-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected to KB', 'aiohm-kb-assistant'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* OHM Brand Identity */
    #aiohm-scan-page {
        --ohm-primary: #457d58; --ohm-dark: #272727; --ohm-light-accent: #cbddd1; --ohm-muted-accent: #7d9b76; --ohm-light-bg: #EBEBEB; --ohm-dark-accent: #1f5014; --ohm-font-primary: 'Montserrat', 'Montserrat Alternates', sans-serif; --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    #aiohm-scan-page h1, #aiohm-scan-page h2, #aiohm-scan-page h3, #aiohm-scan-page h4 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); }
    #aiohm-scan-page p, #aiohm-scan-page .stat-item, #aiohm-scan-page .wp-list-table { font-family: var(--ohm-font-secondary); color: var(--ohm-dark); }
    .wrap > .page-description { font-size: 1.1em; padding-bottom: 1em; border-bottom: 1px solid var(--ohm-light-bg); margin-bottom: 1em; color: var(--ohm-dark); font-family: var(--ohm-font-secondary); }
    #aiohm-scan-page .button-primary { background-color: var(--ohm-primary); border-color: var(--ohm-dark-accent); color: #fff; font-family: var(--ohm-font-primary); font-weight:bold; }
    #aiohm-scan-page .button-primary:hover, #aiohm-scan-page .notice-warning .button-primary:hover { background-color: var(--ohm-dark-accent); border-color: var(--ohm-dark-accent); }
    #aiohm-scan-page .button-primary:disabled { background-color: var(--ohm-muted-accent); border-color: var(--ohm-muted-accent); }
    #aiohm-scan-page .aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid var(--ohm-light-bg); border-radius: 4px; height: 100%; }
    #aiohm-scan-page .aiohm-stats-boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
    #aiohm-scan-page .aiohm-stats-box { background: #fff; border: 1px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    #aiohm-scan-page .stats-box-header { background: var(--ohm-primary); color: white; padding: 15px 20px; }
    #aiohm-scan-page .stats-box-header h4 { margin: 0; font-size: 16px; font-weight: 600; color: white; }
    #aiohm-scan-page .stats-box-content { padding: 20px; }
    #aiohm-scan-page .stat-item { margin-bottom: 12px; padding: 12px; background: #f8faf9; border-left: 3px solid var(--ohm-light-accent); border-radius: 4px; }
    #aiohm-scan-page .stat-item.total-stat { border-left-color: var(--ohm-primary); background: #f0f8f4; }
    #aiohm-scan-page .stat-item:last-child { margin-bottom: 0; }
    #aiohm-scan-page .stat-number { font-size: 24px; font-weight: bold; color: var(--ohm-primary); display: block; }
    #aiohm-scan-page .stat-label { font-size: 13px; color: var(--ohm-muted-accent); }
    #aiohm-scan-page .aiohm-scan-columns-wrapper { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    #aiohm-scan-page .progress-bar-inner { background-color: var(--ohm-primary); }
    #aiohm-scan-page .progress-bar-wrapper { background-color: var(--ohm-light-bg); }
    #aiohm-scan-page .status-knowledge-base { color: var(--ohm-primary); font-weight: bold; }
    #aiohm-scan-page .status-ready-to-add { color: #007cba; font-weight: bold; }
    #aiohm-scan-page .status-failed-to-add { color: #dc3545; font-weight: bold; background: #f8d7da; padding: 3px 8px; border-radius: 4px; border: 1px solid #f5c6cb; }
    #aiohm-scan-page .add-single-item-link { color: #007cba; }
    #aiohm-scan-page .add-single-item-link:hover { color: var(--ohm-dark-accent); }
    #aiohm-scan-page .aiohm-scan-progress { border: 1px solid var(--ohm-light-accent); border-radius: 6px; padding: 15px; background: #f8faf9; }
    #aiohm-scan-page .progress-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    #aiohm-scan-page .progress-label { font-family: var(--ohm-font-secondary); color: var(--ohm-dark); font-size: 14px; }
    #aiohm-scan-page .progress-percentage { font-family: var(--ohm-font-primary); color: var(--ohm-primary); font-weight: bold; font-size: 16px; }
    #aiohm-scan-page .progress-bar-wrapper { height: 12px; background-color: var(--ohm-light-bg); border-radius: 6px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
    #aiohm-scan-page .progress-bar-inner { height: 100%; background: linear-gradient(90deg, var(--ohm-primary) 0%, var(--ohm-dark-accent) 100%); border-radius: 6px; transition: width 0.3s ease; }
    .aiohm-content-type-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
    .type-post { background-color: #e7f5ff; color: #005a87; }
    .type-page { background-color: #f3e7ff; color: #6f42c1; }
    .type-application { background-color: #ffe7e7; color: #d63384; }
    .type-text { background-color: #fff8e7; color: #b8860b; }
    @media (max-width: 960px) { #aiohm-scan-page .aiohm-scan-columns-wrapper, #aiohm-scan-page .aiohm-stats-boxes { grid-template-columns: 1fr; } }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';
    let noticeTimer;
    
    // Enhanced admin notice function with accessibility features
    function showAdminNotice(message, type = 'success', persistent = false) {
        clearTimeout(noticeTimer);
        let $noticeDiv = $('#aiohm-admin-notice');
        
        // Create notice div if it doesn't exist
        if ($noticeDiv.length === 0) {
            $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>').insertAfter('h1');
            $noticeDiv = $('#aiohm-admin-notice');
        }
        
        // Clear existing classes and add new type
        $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type);
        
        // Set message content
        $noticeDiv.find('p').html(message);
        
        // Show notice with fade in effect
        $noticeDiv.fadeIn(300, function() {
            // Auto-focus for accessibility after fade in completes
            $noticeDiv.focus();
            
            // Announce to screen readers
            if (type === 'error') {
                $noticeDiv.attr('aria-live', 'assertive');
            } else {
                $noticeDiv.attr('aria-live', 'polite');
            }
        });
        
        // Handle dismiss button
        $noticeDiv.off('click.notice-dismiss').on('click.notice-dismiss', '.notice-dismiss', function() {
            $noticeDiv.fadeOut(300);
            // Return focus to the previously focused element or main content
            $('h1').focus();
        });
        
        // Auto-hide after timeout (unless persistent)
        if (!persistent) {
            noticeTimer = setTimeout(() => {
                if ($noticeDiv.is(':visible')) {
                    $noticeDiv.fadeOut(300, function() {
                        // Return focus to main content when auto-hiding
                        $('h1').focus();
                    });
                }
            }, 7000); // Increased to 7 seconds for better UX
        }
    }

    function renderItemsTable(items, containerSelector, checkboxName, isUploads = false) {
        const $container = $(containerSelector);
        const $addButton = isUploads ? $('#add-uploads-to-kb-btn') : $('#add-selected-to-kb-btn');
        let hasPendingItems = false;
        let tableHtml = `<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody>`;
        if (items && items.length > 0) {
            items.forEach(function(item) {
                let checkboxDisabled = (item.status === 'Knowledge Base' || item.status === 'Failed to Add') ? 'disabled' : '';
                if (!checkboxDisabled) { hasPendingItems = true; }
                
                let statusContent = item.status;
                let statusClass = item.status.toLowerCase().replace(/\s+/g, '-');
                
                if (item.status === 'Ready to Add') {
                    statusContent = `<a href="#" class="add-single-item-link" data-id="${item.id}" data-type="${isUploads ? 'upload' : 'website'}">${item.status}</a>`;
                } else if (item.status === 'Failed to Add') {
                    statusContent = `<span class="status-failed-to-add">${item.status}</span>`;
                    statusClass = 'failed-to-add';
                }
                
                let typeDisplay = isUploads ? (item.type.split('/')[1] || item.type).toUpperCase() : (item.type.charAt(0).toUpperCase() + item.type.slice(1));
                tableHtml += `<tr><th scope="row" class="check-column"><input type="checkbox" name="${checkboxName}" value="${item.id}" ${checkboxDisabled}></th><td><a href="${item.link}" target="_blank">${item.title}</a></td><td><span class="aiohm-content-type-badge type-${isUploads ? item.type.split('/')[0] : item.type}">${typeDisplay}</span></td><td><span class="status-${statusClass}">${statusContent}</span></td></tr>`;
            });
        } else {
            tableHtml += `<tr><td colspan="4" style="text-align: center;">No scannable items found.</td></tr>`;
        }
        tableHtml += `</tbody></table>`;
        $container.html(tableHtml);
        
        // **MODIFIED LINE**: Disable the button instead of hiding it.
        $addButton.prop('disabled', !hasPendingItems);

        $container.find('thead input:checkbox').prop('checked', false);
    }

    function handleBatchProcessing(buttonSelector, containerSelector, addScanType, findScanType, isUploads) {
        const $addBtn = $(buttonSelector);
        const selectedIds = $(`${containerSelector} input:checkbox:checked`).map(function() { return this.value; }).get();
        if (selectedIds.length === 0) { showAdminNotice('Please select at least one item.', 'warning'); return; }
        
        $addBtn.prop('disabled', true);
        const $progress = $('#website-scan-progress');
        const $progressBar = $progress.find('.progress-bar-inner');
        const $progressPercentage = $progress.find('.progress-percentage');
        const $progressLabel = $progress.find('.progress-label');
        
        $progress.show(); 
        $progressBar.css('width', '0%'); 
        $progressPercentage.text('0%');
        $progressLabel.text('Starting batch processing...');
        
        let processedCount = 0; 
        let successCount = 0;
        let errorCount = 0;
        const totalSelected = selectedIds.length; 
        const batchSize = 5;
        const errors = [];
        
        function processBatch(batch) {
            if (batch.length === 0) {
                // Show completion summary
                const successRate = Math.round((successCount / totalSelected) * 100);
                let summaryMessage;
                
                if (errorCount === 0) {
                    summaryMessage = `🎉 Perfect! All ${totalSelected} items successfully added to knowledge base.`;
                    showAdminNotice(summaryMessage, 'success');
                } else {
                    summaryMessage = `⚠️ Processing complete: ${successCount} successful, ${errorCount} failed out of ${totalSelected} total items.`;
                    showAdminNotice(summaryMessage, 'warning');
                    
                    // Show detailed error information
                    if (errors.length > 0) {
                        const errorDetails = errors.slice(0, 3).join('<br>'); // Show first 3 errors
                        const moreErrors = errors.length > 3 ? `<br><em>...and ${errors.length - 3} more errors</em>` : '';
                        showAdminNotice(`Detailed errors:<br>${errorDetails}${moreErrors}`, 'error', true);
                    }
                }
                
                // Refresh the table to show updated statuses (with delay for cache clearing)
                setTimeout(() => {
                    $.post(ajaxurl, { 
                        action: 'aiohm_progressive_scan', 
                        scan_type: findScanType, 
                        nonce: nonce,
                        cache_bust: Date.now() // Force fresh data
                    }).done(r => {
                        if (r.success) { renderItemsTable(r.data.items, containerSelector, isUploads ? 'upload_items[]' : 'items[]', isUploads); }
                    }).always(() => { 
                        $addBtn.prop('disabled', false); 
                        setTimeout(() => $progress.fadeOut(), 2000); // Keep progress visible for 2 seconds
                    });
                }, 1000); // 1 second delay to allow cache clearing
                
                return;
            }
            
            const currentBatch = batch.slice(0, batchSize); 
            const remainingBatch = batch.slice(batchSize);
            
            $progressLabel.text(`Processing batch ${Math.ceil((processedCount + 1) / batchSize)} of ${Math.ceil(totalSelected / batchSize)}...`);
            
            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: addScanType, item_ids: currentBatch, nonce: nonce })
            .done(r => {
                processedCount += currentBatch.length;
                let percentage = Math.round((processedCount / totalSelected) * 100);
                $progressBar.css('width', `${percentage}%`); 
                $progressPercentage.text(`${percentage}%`);
                
                if (r.success) {
                    successCount += currentBatch.length;
                } else {
                    // Handle partial success/failure responses
                    if (r.data && r.data.successes && r.data.errors) {
                        successCount += r.data.successes.length;
                        errorCount += r.data.errors.length;
                        // Add detailed error messages
                        r.data.errors.forEach(error => {
                            const errorMsg = error.error_message || error.error || 'Unknown error';
                            errors.push(`${error.title}: ${errorMsg}`);
                        });
                    } else {
                        errorCount += currentBatch.length;
                        const errorMsg = r.data?.message || 'Unknown error occurred';
                        errors.push(`Batch error: ${errorMsg}`);
                    }
                }
                
                processBatch(remainingBatch);
            })
            .fail((xhr, status, error) => { 
                processedCount += currentBatch.length;
                errorCount += currentBatch.length;
                errors.push(`Server error: ${error}`);
                
                let percentage = Math.round((processedCount / totalSelected) * 100);
                $progressBar.css('width', `${percentage}%`); 
                $progressPercentage.text(`${percentage}%`);
                
                processBatch(remainingBatch);
            });
        }
        
        processBatch(selectedIds);
    }
    
    function setupScanButton(buttonId, findType, containerSelector, checkboxName, isUploads) {
        $(buttonId).data('original-text', $(buttonId).text()).on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Scanning...');
            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: findType, nonce: nonce })
            .done(r => { if (r.success) { renderItemsTable(r.data.items, containerSelector, checkboxName, isUploads); showAdminNotice('Scan complete.', 'success'); } else { showAdminNotice(r.data.message || 'Scan failed.', 'error'); } })
            .fail(() => showAdminNotice('A server error occurred.', 'error'))
            .always(() => $btn.prop('disabled', false).text($btn.data('original-text')));
        });
    }

    setupScanButton('#scan-website-btn', 'website_find', '#scan-results-container', 'items[]', false);
    setupScanButton('#scan-uploads-btn', 'uploads_find', '#scan-uploads-container', 'upload_items[]', true);
    $('#add-selected-to-kb-btn').on('click', () => handleBatchProcessing('#add-selected-to-kb-btn', '#scan-results-container', 'website_add', 'website_find', false));
    $('#add-uploads-to-kb-btn').on('click', () => handleBatchProcessing('#add-uploads-to-kb-btn', '#scan-uploads-container', 'uploads_add', 'uploads_find', true));
    
    $(document).on('click', '.add-single-item-link', function(e) { 
        e.preventDefault(); 
        const $link = $(this); 
        const itemId = $link.data('id'); 
        const itemType = $link.data('type');
        const originalText = $link.text();
        
        $link.html('<span class="spinner is-active" style="float:none; margin: 0 5px;"></span>');
        
        const addScanType = itemType === 'website' ? 'website_add' : 'uploads_add';
        const findScanType = itemType === 'website' ? 'website_find' : 'uploads_find';
        const container = itemType === 'website' ? '#scan-results-container' : '#scan-uploads-container';
        const checkboxName = itemType === 'website' ? 'items[]' : 'upload_items[]';

        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: addScanType, item_ids: [itemId], nonce: nonce })
        .done(r => { 
            if (r.success) {
                let message = 'Item successfully added to knowledge base!';
                if (r.data && r.data.message) {
                    message = r.data.message;
                }
                
                showAdminNotice(`✅ ${message}`, 'success');
            } else {
                const errorMsg = r.data?.message || 'Unknown error occurred';
                showAdminNotice(`❌ Failed to add item to knowledge base: ${errorMsg}`, 'error');
                $link.html(`<span class="status-failed-to-add">Failed to Add</span>`);
                return;
            }
            
            // Refresh the table to show updated status (with delay for cache clearing)
            setTimeout(() => {
                $.post(ajaxurl, { 
                    action: 'aiohm_progressive_scan', 
                    scan_type: findScanType, 
                    nonce: nonce,
                    cache_bust: Date.now() // Force fresh data
                })
                .done(r => {
                    if (r.success) {
                        renderItemsTable(r.data.items, container, checkboxName, itemType === 'upload');
                    }
                });
            }, 800); // 800ms delay to allow cache clearing
        })
        .fail((xhr, status, error) => { 
            let errorMsg = `Server error occurred: ${error}`;
            
            // Try to get more details from the response
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMsg = xhr.responseJSON.data.message;
            }
            
            showAdminNotice(`❌ ${errorMsg}`, 'error');
            $link.html(`<span class="status-failed-to-add">Failed to Add</span>`);
        }); 
    });
    
    $(document).on('click', '.aiohm-scan-section thead input:checkbox', function(){ $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked); });

    // Initial table loads
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce }).done(r => r.success && renderItemsTable(r.data.items, '#scan-results-container', 'items[]', false));
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_find', nonce: nonce }).done(r => r.success && renderItemsTable(r.data.items, '#scan-uploads-container', 'upload_items[]', true));
});
</script>