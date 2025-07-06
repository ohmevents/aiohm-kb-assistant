<?php
/**
 * Scan website content template.
 * This is the complete and final version with all features, styles, and scripts.
 */
if (!defined('ABSPATH')) exit;

// The controller (settings-page.php) now prepares all these variables before including this template.
$api_key_exists = !empty(AIOHM_KB_Assistant::get_settings()['openai_api_key']);
$total_links = ($site_stats['posts']['total'] ?? 0) + ($site_stats['pages']['total'] ?? 0);
?>
<div class="wrap" id="aiohm-scan-page">
    <h1><?php _e('Build Your Knowledge Base', 'aiohm-kb-assistant'); ?></h1>

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
                    <?php
                    if (!empty($uploads_stats['by_type'])) {
                        foreach($uploads_stats['by_type'] as $type => $data) {
                            $size_formatted = size_format($data['size'] ?? 0);
                            $count_formatted = number_format_i18n($data['count'] ?? 0);
                            echo '<div class="stat-item"><strong>' . esc_html(strtoupper($type)) . ' Files:</strong> <span>' . sprintf(__('%d total, %d indexed, %d pending (%s)', 'aiohm-kb-assistant'), $data['count'] ?? 0, $data['indexed'] ?? 0, $data['pending'] ?? 0, $size_formatted) . '</span></div>';
                        }
                    } else {
                        echo '<p>No supported files found in the Media Library.</p>';
                    }
                    ?>
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
                    <div id="scan-results-container">
                        <?php
                        if (!empty($pending_website_items)) {
                            echo '<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                            foreach ($pending_website_items as $item) {
                                $status_class = strtolower(str_replace(' ', '-', $item['status']));
                                $status_content = $item['status'] === 'Ready to Add' ? sprintf('<a href="#" class="add-single-item-link" data-id="%d">%s</a>', $item['id'], $item['status']) : $item['status'];
                                echo sprintf(
                                    '<tr><th scope="row" class="check-column"><input type="checkbox" name="items[]" value="%d"></th><td><a href="%s" target="_blank">%s</a></td><td><span class="type-%s">%s</span></td><td><span class="status-%s">%s</span></td></tr>',
                                    $item['id'], esc_url($item['link']), esc_html($item['title']), esc_attr($item['type']), esc_html($item['type']), esc_attr($status_class), $status_content
                                );
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p style="padding: 15px; background-color: #f8f9fa;">Click the button above to scan for content.</p>';
                        }
                        ?>
                    </div>
                    <button type="button" class="button button-primary" id="add-selected-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected to KB', 'aiohm-kb-assistant'); ?></button>
                    <div id="website-scan-progress" class="aiohm-scan-progress" style="display: none;">
                        <div class="progress-info"><span class="progress-label">Processing...</span><span class="progress-percentage">0%</span></div>
                        <div class="progress-bar-wrapper"><div class="progress-bar-inner"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="aiohm-scan-column">
            <div class="aiohm-scan-section">
                <h2><?php _e('Upload Folder Scanner', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Scan your <strong>WordPress Media Library</strong> for readable files like .txt, .json, .csv, and .pdf. For PDFs, the Title, Caption, and Description fields will be used as the content.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-uploads-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Find Uploads', 'aiohm-kb-assistant'); ?></button>
                <div id="pending-uploads-area" style="margin-top: 20px;">
                    <h3><?php _e("Uploads Scan Results", 'aiohm-kb-assistant'); ?></h3>
                    <div id="scan-uploads-container">
                        <?php
                        // Now using $all_upload_items for rendering the table, which will include status
                        if (!empty($all_upload_items)) {
                            echo '<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                            foreach ($all_upload_items as $item) {
                                $status_class = strtolower(str_replace(' ', '-', $item['status']));
                                $status_content = $item['status'] === 'Ready to Add' ? sprintf('<a href="#" class="add-single-item-link" data-id="%d" data-type="upload">%s</a>', $item['id'], $item['status']) : $item['status'];
                                echo sprintf(
                                    '<tr><th scope="row" class="check-column"><input type="checkbox" name="upload_items[]" value="%d" %s></th><td><a href="%s" target="_blank">%s</a></td><td><span class="type-%s">%s</span></td><td><span class="status-%s">%s</span></td></tr>',
                                    $item['id'],
                                    ($item['status'] === 'Knowledge Base' ? 'disabled' : ''), // Disable checkbox for indexed items
                                    esc_url($item['link']),
                                    esc_html($item['title']),
                                    esc_attr(explode('/', $item['type'])[0]), // Use main MIME type (e.g., 'image', 'application') for styling
                                    esc_html(ucwords(explode('/', $item['type'])[1])), // Use sub-type for display (e.g., 'pdf', 'json')
                                    esc_attr($status_class),
                                    $status_content
                                );
                            }
                            echo '</tbody></table>';
                        } else {
                             echo '<p style="padding: 15px; background-color: #f8f9fa;">Click the button above to scan for supported files.</p>';
                        }
                        ?>
                    </div>
                    <button type="button" class="button button-primary" id="add-uploads-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected Uploads to KB', 'aiohm-kb-assistant'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.aiohm-scan-columns-wrapper { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 960px) { .aiohm-scan-columns-wrapper { grid-template-columns: 1fr; } }
.aiohm-scan-section-wrapper { max-width: 100%; }
.aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid #dcdcde; border-radius: 4px; height: 100%; }
.aiohm-stats-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
.stat-group h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.stat-item { margin-bottom: 8px; padding: 10px; background: #f8f9fa; border-left: 3px solid #007cba; }
.stat-item.total-stat { background-color: #f0f5fa; border-left-color: #334155; margin-bottom: 15px; font-size: 14px; border-bottom: 1px solid #e0e5e9; padding-bottom: 10px; }
.aiohm-scan-progress { margin-top: 15px; }
.progress-info { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; color: #555; }
.progress-bar-wrapper { background-color: #e9ecef; border-radius: 4px; height: 12px; overflow: hidden; }
.progress-bar-inner { background-color: #007cba; width: 0%; height: 100%; transition: width 0.3s ease-in-out; }
.type-post, .type-page { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.type-post { background-color: #e7f5ff; color: #005a87; }
.type-page { background-color: #f3e7ff; color: #6f42c1; }
/* Styles for common file types, derive from mime-type primary part */
.type-image { background-color: #e7ffe7; color: #198754; }
.type-application { background-color: #ffe7e7; color: #d63384; } /* For PDFs, JSON, etc. */
.type-text { background-color: #fff8e7; color: #b8860b; } /* For TXT, CSV */

.status-knowledge-base { color: #28a745; font-weight: bold; }
.status-ready-to-add { color: #007cba; }
.add-single-item-link { cursor: pointer; text-decoration: underline; }
.add-single-item-link:hover { color: #005a87; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';
    let noticeTimer;

    function showAdminNotice(message, type = 'success') {
        clearTimeout(noticeTimer);
        const $notice = $('#aiohm-admin-notice');
        $notice.removeClass('notice-success notice-error').addClass('notice-' + type);
        $notice.find('p').html(message);
        $notice.fadeIn();
        noticeTimer = setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }

    function renderItemsTable(items, containerSelector, checkboxName, showStatusColumn, isUploads = false) {
        const $container = $(containerSelector);
        $container.empty();
        if (items && items.length > 0) {
            let statusHeader = showStatusColumn ? '<th>Status</th>' : '';
            let table = `<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th>${statusHeader}</tr></thead><tbody>`;
            items.forEach(function(item) {
                let statusCell = '';
                let checkboxDisabled = '';
                if (showStatusColumn) {
                    let statusClass = (item.status || '').toLowerCase().replace(/\s+/g, '-');
                    let statusContent = item.status;
                    if (item.status === 'Ready to Add') {
                        statusContent = `<a href="#" class="add-single-item-link" data-id="${item.id}" data-type="${isUploads ? 'upload' : 'website'}">${item.status}</a>`;
                    } else if (item.status === 'Knowledge Base') {
                        checkboxDisabled = 'disabled';
                    }
                    statusCell = `<td><span class="status-${statusClass}">${statusContent}</span></td>`;
                }
                
                let typeDisplay = isUploads ? item.type.split('/')[1].charAt(0).toUpperCase() + item.type.split('/')[1].slice(1) : item.type;
                let typeClass = isUploads ? item.type.split('/')[0] : item.type; // Use main MIME type part for class

                table += `
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="${checkboxName}" value="${item.id}" ${checkboxDisabled}></th>
                        <td><a href="${item.link}" target="_blank">${item.title}</a></td>
                        <td><span class="type-${typeClass}">${typeDisplay}</span></td>
                        ${statusCell}
                    </tr>`;
            });
            table += `</tbody></table>`;
            $container.html(table);
        } else {
            // Updated message to be more generic for 'no supported files found'
            $container.html('<p style="padding: 15px; background-color: #f8f9fa;">No supported files found.</p>');
        }
    }

    $('#scan-website-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning...');
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce })
        .done(response => { 
            if (response.success) { 
                renderItemsTable(response.data.items, '#scan-results-container', 'items[]', true); 
            } 
        })
        .always(() => { $btn.prop('disabled', false).text('Re-Scan Posts & Pages'); });
    });
    
    $('#add-selected-to-kb-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $addBtn = $(this);
        const selectedIds = $('#scan-results-container input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) {
            showAdminNotice('Please select at least one item.', 'error');
            return;
        }
        $addBtn.prop('disabled', true);
        const $progress = $('#website-scan-progress');
        const $progressBar = $progress.find('.progress-bar-inner');
        const $progressPercentage = $progress.find('.progress-percentage');
        $progress.show(); $progressBar.css('width', '0%'); $progressPercentage.text('0%');
        let processedCount = 0; const batchSize = 5;

        function processBatch(batch) {
            if (batch.length === 0) {
                showAdminNotice('All selected items processed successfully.', 'success');
                $addBtn.prop('disabled', false);
                setTimeout(() => { 
                    $progress.fadeOut(); 
                    location.reload(); // Reload page to show updated stats
                }, 1000);
                return;
            }
            const currentBatch = batch.slice(0, batchSize);
            const remainingBatch = batch.slice(batchSize);

            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_add', item_ids: currentBatch, nonce: nonce })
            .done(response => {
                if (response.success) {
                    processedCount += currentBatch.length;
                    let percentage = Math.round((processedCount / selectedIds.length) * 100);
                    $progressBar.css('width', percentage + '%'); $progressPercentage.text(percentage + '%');
                    renderItemsTable(response.data.all_items, '#scan-results-container', 'items[]', true);
                    processBatch(remainingBatch);
                } else {
                    showAdminNotice(response.data.message, 'error');
                    $addBtn.prop('disabled', false);
                }
            }).fail(() => {
                showAdminNotice('An unexpected server error occurred.', 'error');
                $addBtn.prop('disabled', false);
            });
        }
        processBatch(selectedIds);
    });

    $('#scan-uploads-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning Uploads...');
        
        // This AJAX call should return ALL supported uploads, not just pending ones
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_find', nonce: nonce })
        .done(function(response) {
            if (response.success) { 
                // Pass true for showStatusColumn and isUploads
                renderItemsTable(response.data.items, '#scan-uploads-container', 'upload_items[]', true, true);
            }
        })
        .always(function() { $btn.prop('disabled', false).text('Find Uploads'); }); // Changed button text back
    });

    $('#add-uploads-to-kb-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $addBtn = $(this);
        const selectedIds = $('#scan-uploads-container input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) {
            showAdminNotice('Please select at least one file.', 'error');
            return;
        }
        $addBtn.prop('disabled', true).text('Adding...');
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_add', item_ids: selectedIds, nonce: nonce })
        .done(function(response) {
            if (response.success) {
                showAdminNotice(response.data.processed_items.length + ' file(s) processed.', 'success');
                // Pass true for showStatusColumn and isUploads
                renderItemsTable(response.data.items, '#scan-uploads-container', 'upload_items[]', true, true);
            } else {
                showAdminNotice(response.data.message, 'error');
            }
        })
        .always(function() {
            $addBtn.prop('disabled', false).text('Add Selected Uploads to KB');
            location.reload(); // Reload page to show updated stats
        });
    });

    $('#scan-results-container, #scan-uploads-container').on('click', '.add-single-item-link', function(e) { // Combined handler for both tables
        e.preventDefault();
        const $link = $(this);
        const itemId = $link.data('id');
        const itemType = $link.data('type'); // 'website' or 'upload'
        if (!itemId) return;
        $link.replaceWith('<span class="spinner is-active" style="float:none;"></span>');

        const scanType = itemType === 'website' ? 'website_add' : 'uploads_add';
        const containerSelector = itemType === 'website' ? '#scan-results-container' : '#scan-uploads-container';
        const checkboxName = itemType === 'website' ? 'items[]' : 'upload_items[]';
        const isUploads = itemType === 'upload';

        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: scanType, item_ids: [itemId], nonce: nonce })
        .done(response => {
            if (response.success) {
                showAdminNotice('1 item processed successfully.', 'success');
                // Use the correct `all_items` or `items` key based on scanType
                const updatedItems = response.data.all_items || response.data.items; 
                renderItemsTable(updatedItems, containerSelector, checkboxName, true, isUploads);
            } else {
                showAdminNotice(response.data.message, 'error');
                $link.closest('td').find('.spinner').replaceWith(`<a href="#" class="add-single-item-link" data-id="${itemId}" data-type="${itemType}">Ready to Add</a>`);
            }
        })
        .fail(() => {
            showAdminNotice('An unexpected server error occurred.', 'error');
            $link.closest('td').find('.spinner').replaceWith(`<a href="#" class="add-single-item-link" data-id="${itemId}" data-type="${itemType}">Ready to Add</a>`);
        });
    });
    
    $(document).on('click', '.aiohm-scan-section thead input:checkbox', function(){
        $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked);
    });
});
</script>