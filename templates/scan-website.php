<?php
/**
 * Scan website content template.
 * This is the complete and final version with all features, styles, and scripts.
 */
if (!defined('ABSPATH')) exit;

// Get settings and all necessary stats for the page
$settings = AIOHM_KB_Assistant::get_settings();
$api_key_exists = !empty($settings['openai_api_key']);

$site_crawler = new AIOHM_KB_Site_Crawler();
$uploads_crawler = new AIOHM_KB_Uploads_Crawler();
$site_stats = $site_crawler->get_scan_stats();
$uploads_stats = $uploads_crawler->get_stats();

$pending_items = get_transient('aiohm_pending_items_website_' . get_current_user_id()) ?: [];
?>
<div class="wrap" id="aiohm-scan-page">
    <h1><?php _e('Build Your Knowledge Base', 'aiohm-kb-assistant'); ?></h1>

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
            <p><?php _e('An overview of all scannable content.', 'aiohm-kb-assistant'); ?></p>
            <div class="aiohm-stats-split">
                <div class="stat-group">
                    <h4><?php _e('Website Content', 'aiohm-kb-assistant'); ?></h4>
                    <div class="stat-item">
                        <strong><?php _e('Posts:', 'aiohm-kb-assistant'); ?></strong>
                        <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['posts']['total'], $site_stats['posts']['indexed'], $site_stats['posts']['pending']); ?></span>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('Pages:', 'aiohm-kb-assistant'); ?></strong>
                         <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['pages']['total'], $site_stats['pages']['indexed'], $site_stats['pages']['pending']); ?></span>
                    </div>
                </div>
                <div class="stat-group">
                    <h4><?php _e('Uploaded Files', 'aiohm-kb-assistant'); ?></h4>
                    <?php
                    if (!empty($uploads_stats['by_type'])) {
                        foreach($uploads_stats['by_type'] as $type => $data) {
                            $size_formatted = size_format($data['size'] ?? 0);
                            $count_formatted = number_format_i18n($data['count'] ?? 0);
                            echo '<div class="stat-item"><strong>' . esc_html(strtoupper($type)) . ' Files:</strong> <span>' . sprintf('%s files (%s)', $count_formatted, $size_formatted) . '</span></div>';
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
                <p><?php _e('Scan your website for all published posts and pages.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-website-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Find Posts & Pages', 'aiohm-kb-assistant'); ?></button>
                <div id="pending-content-area" style="display: none; margin-top: 20px;">
                    <h3><?php _e("Scan Results", 'aiohm-kb-assistant'); ?></h3>
                    <div id="scan-results-container"></div>
                    <button type="button" class="button button-primary" id="add-selected-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected to KB', 'aiohm-kb-assistant'); ?></button>
                    <div id="website-scan-progress" class="aiohm-scan-progress" style="display: none;"></div>
                </div>
            </div>
        </div>

        <div class="aiohm-scan-column">
            <div class="aiohm-scan-section">
                <h2><?php _e('Uploaded Files Scanner', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Scan for <strong>.txt, .json, .csv,</strong> and <strong>.pdf</strong> files. For PDFs, the Title, Caption, and Description fields will be used as the content.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-uploads-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Find Uploads', 'aiohm-kb-assistant'); ?></button>
                <div id="pending-uploads-area" style="display: none; margin-top: 20px;">
                    <h3><?php _e("Uploads Scan Results", 'aiohm-kb-assistant'); ?></h3>
                    <div id="scan-uploads-container"></div>
                    <button type="button" class="button button-primary" id="add-uploads-to-kb-btn" style="margin-top: 15px;" <?php disabled(!$api_key_exists); ?>><?php _e('Add Selected Uploads to KB', 'aiohm-kb-assistant'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.aiohm-scan-columns-wrapper {
    margin-top: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 960px) {
    .aiohm-scan-columns-wrapper {
        grid-template-columns: 1fr;
    }
}
.aiohm-scan-section-wrapper { max-width: 100%; }
.aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid #dcdcde; border-radius: 4px; height: 100%; }
.aiohm-stats-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
.stat-group h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.stat-item { margin-bottom: 8px; padding: 10px; background: #f8f9fa; border-left: 3px solid #007cba; }
.aiohm-scan-progress { margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 4px; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';

    // --- Website Content Scanner ---
    $('#scan-website-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning...');
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce })
        .done(response => { 
            if (response.success) { 
                renderItemsTable(response.data.items, '#pending-content-area', '#scan-results-container', 'items[]', true); 
            } 
        })
        .always(() => { $btn.prop('disabled', false).text('Find Posts & Pages'); });
    });
    
    $('#add-selected-to-kb-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $addBtn = $(this);
        const selectedIds = $('#scan-results-container input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) { alert('Please select at least one item.'); return; }
        
        $addBtn.prop('disabled', true).text('Processing...');
        
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_add', item_ids: selectedIds, nonce: nonce })
        .done(response => {
            if (response.success) {
                alert(response.data.processed_items.length + ' item(s) processed.');
                renderItemsTable(response.data.all_items, '#pending-content-area', '#scan-results-container', 'items[]', true);
            } else {
                alert('Error: ' + (response.data.message || 'An error occurred.'));
            }
        })
        .always(() => {
            $addBtn.prop('disabled', false).text('Add Selected to KB');
        });
    });

    // --- Uploaded Files Scanner ---
    $('#scan-uploads-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('Scanning Uploads...');
        
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_find', nonce: nonce })
        .done(function(response) {
            if (response.success) {
                renderItemsTable(response.data.items, '#pending-uploads-area', '#scan-uploads-container', 'upload_items[]', false);
            }
        })
        .always(function() {
            $btn.prop('disabled', false).text('Find Uploads');
        });
    });

    $('#add-uploads-to-kb-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $addBtn = $(this);
        const selectedIds = $('#scan-uploads-container input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) { alert('Please select at least one file.'); return; }

        $addBtn.prop('disabled', true).text('Adding...');

        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_add', item_ids: selectedIds, nonce: nonce })
        .done(function(response) {
            if (response.success) {
                alert(response.data.processed_items.length + ' file(s) processed and added to the knowledge base!');
                renderItemsTable(response.data.items, '#pending-uploads-area', '#scan-uploads-container', 'upload_items[]', false);
            } else {
                alert('Error: ' + (response.data.message || 'An error occurred.'));
            }
        })
        .always(function() {
            $addBtn.prop('disabled', false).text('Add Selected Uploads to KB');
        });
    });

    // Generic function to render a table of items for either scanner
    function renderItemsTable(items, areaSelector, containerSelector, checkboxName, showStatusColumn) {
        const $area = $(areaSelector);
        const $container = $(containerSelector);
        $container.empty();

        if (items && items.length > 0) {
            let statusHeader = showStatusColumn ? '<th>Status</th>' : '';
            let table = `<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th>${statusHeader}</tr></thead><tbody>`;
            items.forEach(function(item) {
                let statusCell = showStatusColumn ? `<td>${item.status}</td>` : '';
                table += `
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="${checkboxName}" value="${item.id}"></th>
                        <td><a href="${item.link}" target="_blank">${item.title}</a></td>
                        <td>${item.type}</td>
                        ${statusCell}
                    </tr>`;
            });
            table += `</tbody></table>`;
            $container.html(table);
        } else {
            $container.html('<p style="padding: 15px; background-color: #f8f9fa;">No new items found.</p>');
        }
        $area.show();
    }
    
    // Checkbox helper for both tables
    $(document).on('click', '.aiohm-scan-section thead input:checkbox', function(){
        $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked);
    });
});
</script>