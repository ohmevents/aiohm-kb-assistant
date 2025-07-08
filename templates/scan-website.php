<?php
/**
 * Scan website content template - Branded Version with all functionality and styles restored.
 */
if (!defined('ABSPATH')) exit;

// The controller (settings-page.php) prepares these variables before including this template.
$api_key_exists = !empty(AIOHM_KB_Assistant::get_settings()['openai_api_key']);
$total_links = ($site_stats['posts']['total'] ?? 0) + ($site_stats['pages']['total'] ?? 0);
?>
<div class="wrap" id="aiohm-scan-page">
    <h1><?php _e('Build Your Knowledge Base', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Scan your website\'s posts, pages, and media library to add content to your AI\'s knowledge base.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

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
            <div class="aiohm-stats-split">
                <div class="stat-group">
                    <h4><?php _e('Website Content Breakdown', 'aiohm-kb-assistant'); ?></h4>
                    <div class="stat-item total-stat">
                        <strong><?php _e('Total Website Content:', 'aiohm-kb-assistant'); ?></strong>
                        <span><?php echo esc_html($total_links); ?> (Posts + Pages)</span>
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
                <div class="stat-group">
                    <h4><?php _e('Media Library Breakdown', 'aiohm-kb-assistant'); ?></h4>
                    <div class="stat-item total-stat">
                        <strong><?php _e('Total Media Files:', 'aiohm-kb-assistant'); ?></strong>
                        <span><?php echo esc_html($uploads_stats['total_files'] ?? 0); ?> (<?php _e('Indexed:', 'aiohm-kb-assistant'); ?> <?php echo esc_html($uploads_stats['indexed_files'] ?? 0); ?>, <?php _e('Pending:', 'aiohm-kb-assistant'); ?> <?php echo esc_html($uploads_stats['pending_files'] ?? 0); ?>)</span>
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
    #aiohm-scan-page .aiohm-stats-split { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 15px; }
    #aiohm-scan-page .stat-group h4 { margin-top: 0; border-bottom: 1px solid var(--ohm-light-bg); padding-bottom: 10px; }
    #aiohm-scan-page .stat-item { margin-bottom: 8px; padding: 10px; background: #fdfdfd; border-left: 3px solid var(--ohm-light-accent); }
    #aiohm-scan-page .stat-item.total-stat { border-left-color: var(--ohm-primary); }
    #aiohm-scan-page .aiohm-scan-columns-wrapper { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    #aiohm-scan-page .progress-bar-inner { background-color: var(--ohm-primary); }
    #aiohm-scan-page .progress-bar-wrapper { background-color: var(--ohm-light-bg); }
    #aiohm-scan-page .status-knowledge-base { color: var(--ohm-primary); font-weight: bold; }
    #aiohm-scan-page .status-ready-to-add { color: #007cba; font-weight: bold; }
    #aiohm-scan-page .add-single-item-link { color: #007cba; }
    #aiohm-scan-page .add-single-item-link:hover { color: var(--ohm-dark-accent); }
    .aiohm-content-type-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
    .type-post { background-color: #e7f5ff; color: #005a87; }
    .type-page { background-color: #f3e7ff; color: #6f42c1; }
    .type-application { background-color: #ffe7e7; color: #d63384; }
    .type-text { background-color: #fff8e7; color: #b8860b; }
    @media (max-width: 960px) { #aiohm-scan-page .aiohm-scan-columns-wrapper, #aiohm-scan-page .aiohm-stats-split { grid-template-columns: 1fr; } }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';
    let noticeTimer;
    
    function showAdminNotice(message, type = 'success') {
        clearTimeout(noticeTimer);
        const $notice = $('#aiohm-admin-notice');
        $notice.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type).addClass('is-dismissible');
        $notice.find('p').html(message);
        $notice.fadeIn();
        noticeTimer = setTimeout(() => $notice.fadeOut(), 5000);
    }

    function renderItemsTable(items, containerSelector, checkboxName, isUploads = false) {
        const $container = $(containerSelector);
        const $addButton = isUploads ? $('#add-uploads-to-kb-btn') : $('#add-selected-to-kb-btn');
        let hasPendingItems = false;
        let tableHtml = `<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody>`;
        if (items && items.length > 0) {
            items.forEach(function(item) {
                let checkboxDisabled = (item.status === 'Knowledge Base') ? 'disabled' : '';
                if (!checkboxDisabled) { hasPendingItems = true; }
                let statusContent = item.status === 'Ready to Add' ? `<a href="#" class="add-single-item-link" data-id="${item.id}" data-type="${isUploads ? 'upload' : 'website'}">${item.status}</a>` : item.status;
                let typeDisplay = isUploads ? (item.type.split('/')[1] || item.type).toUpperCase() : (item.type.charAt(0).toUpperCase() + item.type.slice(1));
                tableHtml += `<tr><th scope="row" class="check-column"><input type="checkbox" name="${checkboxName}" value="${item.id}" ${checkboxDisabled}></th><td><a href="${item.link}" target="_blank">${item.title}</a></td><td><span class="aiohm-content-type-badge type-${isUploads ? item.type.split('/')[0] : item.type}">${typeDisplay}</span></td><td><span class="status-${item.status.toLowerCase().replace(/\s+/g, '-')}">${statusContent}</span></td></tr>`;
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
        $progress.show(); $progressBar.css('width', '0%'); $progressPercentage.text('0%');
        let processedCount = 0; const totalSelected = selectedIds.length; const batchSize = 5;
        function processBatch(batch) {
            if (batch.length === 0) {
                $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: findScanType, nonce: nonce }).done(r => {
                    if (r.success) { renderItemsTable(r.data.items, containerSelector, isUploads ? 'upload_items[]' : 'items[]', isUploads); }
                }).always(() => { $addBtn.prop('disabled', false); $progress.fadeOut(); });
                showAdminNotice('All selected items processed.', 'success');
                return;
            }
            const currentBatch = batch.slice(0, batchSize); const remainingBatch = batch.slice(batchSize);
            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: addScanType, item_ids: currentBatch, nonce: nonce }).done(r => {
                if (r.success) {
                    processedCount += currentBatch.length;
                    let percentage = Math.round((processedCount / totalSelected) * 100);
                    $progressBar.css('width', `${percentage}%`); $progressPercentage.text(`${percentage}%`);
                    processBatch(remainingBatch);
                } else { showAdminNotice(r.data.message || 'An error occurred.', 'error'); $addBtn.prop('disabled', false); $progress.fadeOut(); }
            }).fail(() => { showAdminNotice('A server error occurred.', 'error'); $addBtn.prop('disabled', false); $progress.fadeOut(); });
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
        const $link = $(this); const itemId = $link.data('id'); const itemType = $link.data('type');
        $link.html('<span class="spinner is-active" style="float:none; margin: 0 5px;"></span>');
        const addScanType = itemType === 'website' ? 'website_add' : 'uploads_add';
        const findScanType = itemType === 'website' ? 'website_find' : 'uploads_find';
        const container = itemType === 'website' ? '#scan-results-container' : '#scan-uploads-container';
        const checkboxName = itemType === 'website' ? 'items[]' : 'upload_items[]';

        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: addScanType, item_ids: [itemId], nonce: nonce })
        .done(() => { 
            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: findScanType, nonce: nonce })
            .done(r => r.success && renderItemsTable(r.data.items, container, checkboxName, itemType === 'upload'));
        }); 
    });
    
    $(document).on('click', '.aiohm-scan-section thead input:checkbox', function(){ $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked); });

    // Initial table loads
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce }).done(r => r.success && renderItemsTable(r.data.items, '#scan-results-container', 'items[]', false));
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_find', nonce: nonce }).done(r => r.success && renderItemsTable(r.data.items, '#scan-uploads-container', 'upload_items[]', true));
});
</script>