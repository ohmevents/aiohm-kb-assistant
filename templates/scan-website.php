<?php
/**
 * Scan website content template.
 * This version implements the two-column layout and ensures all JavaScript is complete.
 */
if (!defined('ABSPATH')) exit;

// Get settings to check for the API key
$settings = AIOHM_KB_Assistant::get_settings();
$api_key_exists = !empty($settings['openai_api_key']);

// Get data for the page
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

    <div class="aiohm-scan-columns-wrapper">
        <div class="aiohm-scan-column">
            <div class="aiohm-scan-section">
                <h2><?php _e('Website Content (Posts & Pages)', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Scan your website to find all published posts and pages to add to your knowledge base.', 'aiohm-kb-assistant'); ?></p>
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
                <h2><?php _e('Uploaded Files (Documents)', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Scan your Media Library for readable files like .txt, .json, and .pdf to add their contents.', 'aiohm-kb-assistant'); ?></p>
                <button type="button" class="button button-primary" id="scan-uploads-btn" <?php disabled(!$api_key_exists); ?>><?php _e('Find Pending Uploads', 'aiohm-kb-assistant'); ?></button>
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
.aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid #dcdcde; border-radius: 4px; height: 100%; }
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
                renderItemsTable(response.data.items, '#pending-content-area', '#scan-results-container', 'items[]'); 
            } 
        })
        .always(() => { $btn.prop('disabled', false).text('Find Posts & Pages'); });
    });
    
    $('#add-selected-to-kb-btn').on('click', function() {
        if ($(this).is(':disabled')) return;
        const $addBtn = $(this);
        const selectedIds = $('#scan-results-container input:checkbox:checked').map(function() { return this.value; }).get();
        if (selectedIds.length === 0) { alert('Please select at least one item.'); return; }
        
        $addBtn.prop('disabled', true);
        const $progress = $('#website-scan-progress');
        $progress.show().html('Processing...');
        
        $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_add', item_ids: selectedIds, nonce: nonce })
        .done(response => {
            if (response.success) {
                alert(response.data.processed_items.length + ' item(s) processed.');
                $('#scan-website-btn').click(); // Refresh the list
            } else {
                alert('Error: ' + (response.data.message || 'An error occurred.'));
            }
        })
        .always(() => {
            $addBtn.prop('disabled', false);
            $progress.hide();
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
                renderItemsTable(response.data.items, '#pending-uploads-area', '#scan-uploads-container', 'upload_items[]');
            }
        })
        .always(function() {
            $btn.prop('disabled', false).text('Find Pending Uploads');
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
                $('#scan-uploads-btn').click();
            } else {
                alert('Error: ' + (response.data.message || 'An error occurred.'));
            }
        })
        .always(function() {
            $addBtn.prop('disabled', false).text('Add Selected Uploads to KB');
        });
    });

    // Generic function to render a table of items for either scanner
    function renderItemsTable(items, areaSelector, containerSelector, checkboxName) {
        const $area = $(areaSelector);
        const $container = $(containerSelector);
        $container.empty();

        if (items && items.length > 0) {
            let table = `<table class="wp-list-table widefat striped"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox"></td><th>Title</th><th>Type</th></tr></thead><tbody>`;
            items.forEach(function(item) {
                table += `
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="${checkboxName}" value="${item.id}"></th>
                        <td><a href="${item.link}" target="_blank">${item.title}</a></td>
                        <td>${item.type}</td>
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