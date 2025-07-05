<?php
/**
 * Scan website content template.
 * This version restores the user-approved single-column layout and ensures all JavaScript is complete and functional.
 */
if (!defined('ABSPATH')) exit;

// Get data for the page
$pending_items = get_transient('aiohm_pending_items_website_' . get_current_user_id()) ?: [];
$site_stats = $site_stats ?? ['posts' => ['total' => 0, 'indexed' => 0, 'pending' => 0], 'pages' => ['total' => 0, 'indexed' => 0, 'pending' => 0]];
?>
<div class="wrap" id="aiohm-scan-page">
    <h1><?php _e('Scan Content', 'aiohm-kb-assistant'); ?></h1>

    <div class="aiohm-scan-section-wrapper">
        <div class="aiohm-scan-section">
            <h2><?php _e('Website Content (Posts & Pages)', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('Scan your website to find all posts and pages. You can then select which items to add or re-add to the knowledge base.', 'aiohm-kb-assistant'); ?></p>
            
            <div class="aiohm-stats">
                <div id="total-links-found-wrapper" class="stat-item" style="display: none; background-color: #e7f5ff; border-left-color: #00a0d2;">
                    <strong><?php _e('Scan Result:', 'aiohm-kb-assistant'); ?></strong>
                    <span id="total-links-found"></span>
                </div>
                <div class="stat-item">
                    <strong><?php _e('Posts:', 'aiohm-kb-assistant'); ?></strong>
                    <span id="stats-posts"><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['posts']['total'], $site_stats['posts']['indexed'], $site_stats['posts']['pending']); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php _e('Pages:', 'aiohm-kb-assistant'); ?></strong>
                     <span id="stats-pages"><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['pages']['total'], $site_stats['pages']['indexed'], $site_stats['pages']['pending']); ?></span>
                </div>
            </div>
            
            <button type="button" class="button button-primary" id="scan-website-btn">
                <?php _e('Scan Website', 'aiohm-kb-assistant'); ?>
            </button>
            
            <div id="pending-content-area" style="<?php echo empty($pending_items) ? 'display: none;' : ''; ?> margin-top: 20px;">
                <h3><?php _e('Scan Results', 'aiohm-kb-assistant'); ?></h3>
                <div id="scan-results-container">
                     <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <td id="cb-select-all-1" class="manage-column column-cb check-column"><input type="checkbox"></td>
                                <th><?php _e('Title', 'aiohm-kb-assistant'); ?></th>
                                <th><?php _e('Type', 'aiohm-kb-assistant'); ?></th>
                                <th style="width: 150px;"><?php _e('Status', 'aiohm-kb-assistant'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="pending-content-list">
                            <!-- JS renders rows here -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="button button-primary" id="add-selected-to-kb-btn" style="margin-top: 15px;"><?php _e('Add / Re-index Selected', 'aiohm-kb-assistant'); ?></button>
            </div>
            
            <div id="website-scan-progress" class="aiohm-scan-progress" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
.aiohm-scan-section-wrapper { max-width: 800px; margin-top: 20px; }
.aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid #dcdcde; border-radius: 4px; }
.aiohm-stats { margin: 20px 0; }
.stat-item { margin: 8px 0; padding: 10px; background: #f8f9fa; border-left: 3px solid #007cba; }
.aiohm-scan-progress { margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 4px; }
.aiohm-scan-results-message { padding: 20px; text-align: center; background-color: #f8f9fa; border: 1px dashed #dcdcde; margin-top: 20px; }
#pending-content-list .status-ready-to-add { color: #007cba; font-weight: bold; }
#pending-content-list .status-in-knowledge-base { color: #28a745; }
#pending-content-list tr.status-in-knowledge-base { background-color: #f0fff0 !important; }
#pending-content-list .status-processing { font-style: italic; }
#pending-content-list .status-error { color: #dc3545; font-weight: bold; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';

    function renderItemsTable(items) {
        const $area = $('#pending-content-area');
        const $container = $('#scan-results-container');
        const $totalLinksWrapper = $('#total-links-found-wrapper');
        const $totalLinks = $('#total-links-found');
        const $addButton = $('#add-selected-to-kb-btn');
        
        $container.empty();
        
        $totalLinks.text(`${items.length} items found.`);
        $totalLinksWrapper.show();

        if (items && items.length > 0) {
            const table = `<table class="wp-list-table widefat striped"><thead><tr><td id="cb-select-all-1" class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th><th style="width: 150px;">Status</th></tr></thead><tbody id="pending-content-list"></tbody></table>`;
            $container.html(table);
            const $newList = $('#pending-content-list');
            items.forEach(item => {
                const rowClass = item.status === 'In Knowledge Base' ? 'status-in-knowledge-base' : 'status-ready-to-add';
                const row = `
                    <tr id="item-${item.id}" class="${rowClass}">
                        <th scope="row" class="check-column"><input type="checkbox" name="items[]" value="${item.id}"></th>
                        <td><a href="${item.link}" target="_blank">${item.title || '(no title)'}</a></td>
                        <td>${item.type}</td>
                        <td class="status">${item.status}</td>
                    </tr>`;
                $newList.append(row);
            });
            $addButton.show();
        } else {
            $container.html('<div class="aiohm-scan-results-message"><p>No content was found on your website.</p></div>');
            $addButton.hide();
        }
        $area.show();
    }

    const savedItems = <?php echo json_encode($pending_items); ?>;
    if (savedItems && savedItems.length > 0) {
        renderItemsTable(savedItems);
    }

    $('#scan-website-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning...');
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce })
        .done(response => { if (response.success) { renderItemsTable(response.data.items); } })
        .always(() => { $btn.prop('disabled', false).text('Scan Website'); });
    });
    
    $('#add-selected-to-kb-btn').on('click', function() {
        const $addBtn = $(this);
        const selectedIds = $('#pending-content-list input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) { alert('Please select at least one item.'); return; }
        
        $addBtn.prop('disabled', true);
        const $progress = $('#website-scan-progress');
        $progress.show().html('<div class="progress-header"><h4>Processing...</h4><span class="percentage">0%</span></div><div style="background:#e9ecef;border-radius:4px;padding:2px;"><div class="bar" style="width:0;height:20px;background-color:#007cba;border-radius:2px;"></div></div><div class="status" style="margin-top:5px;"></div>');
        let processedCount = 0;

        function processBatch(batch) {
            const currentBatchIds = batch.slice(0, 5);
            const remainingBatchIds = batch.slice(5);
            currentBatchIds.forEach(id => { $(`#item-${id}`).find('.status').text('Processing...'); });

            $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_add', item_ids: currentBatchIds, nonce: nonce })
            .done(response => {
                if (response.success) {
                    processedCount += currentBatchIds.length;
                    let percentage = Math.round((processedCount / selectedIds.length) * 100);
                    $progress.find('.bar').css('width', `${percentage}%`);
                    $progress.find('.percentage').text(`${percentage}%`);
                    $progress.find('.status').text(`${processedCount} / ${selectedIds.length} items processed.`);
                    
                    response.data.processed_items.forEach(item => {
                        const $row = $(`#item-${item.id}`);
                        const newStatus = item.status === 'success' ? 'In Knowledge Base' : 'Error';
                        $row.find('.status').text(newStatus).removeClass('status-ready-to-add status-processing').addClass(item.status === 'success' ? 'status-in-knowledge-base' : 'status-error');
                        if (item.status === 'success') {
                            $row.addClass('status-in-knowledge-base');
                            $row.find('input:checkbox').prop('checked', false);
                        }
                    });

                    if (remainingBatchIds.length > 0) {
                        processBatch(remainingBatchIds);
                    } else {
                        $progress.find('.status').text('Completed! Updating stats...');
                        $addBtn.prop('disabled', false);
                        
                        const stats = response.data.new_stats;
                        if (stats) {
                            $('#stats-posts').text(`${stats.posts.total} total, ${stats.posts.indexed} indexed, ${stats.posts.pending} pending`);
                            $('#stats-pages').text(`${stats.pages.total} total, ${stats.pages.indexed} indexed, ${stats.pages.pending} pending`);
                        }
                        setTimeout(() => { $progress.fadeOut(); }, 2000);
                    }
                }
            });
        }
        processBatch(selectedIds);
    });

    $(document).on('click', '#cb-select-all-1', function(){
        $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked);
    });
});
</script>