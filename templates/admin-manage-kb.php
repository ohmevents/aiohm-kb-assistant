<?php
/**
 * Template for the Manage Knowledge Base admin page.
 * This version implements the new full-width layout.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Manage Knowledge Base', 'aiohm-kb-assistant'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg(['page' => 'aiohm-scan-content'], admin_url('admin.php'))); ?>" class="page-title-action"><?php _e('Add from Website Scan', 'aiohm-kb-assistant'); ?></a>

    <hr class="wp-header-end">

    <form method="post">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php
        // The list_table variable is passed from the manager class and displayed here.
        if (isset($list_table)) {
            $list_table->display();
        } else {
            echo '<p>No knowledge base entries found.</p>';
        }
        ?>
    </form>

    <div id="aiohm-kb-actions" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px;">
        <h2><?php _e('Knowledge Base Actions', 'aiohm-kb-assistant'); ?></h2>
        <p><?php _e('Use the buttons below to export or reset your knowledge base.', 'aiohm-kb-assistant'); ?></p>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="button button-secondary" id="export-kb-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Global Knowledge Base (JSON)', 'aiohm-kb-assistant'); ?>
            </button>
            <span class="spinner" id="export-spinner" style="float: none; vertical-align: middle;"></span>

            <button type="button" class="button button-danger" id="reset-kb-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Reset Entire Knowledge Base', 'aiohm-kb-assistant'); ?>
            </button>
            <span class="spinner" id="reset-spinner" style="float: none; vertical-align: middle;"></span>
        </div>
        <p class="description" style="margin-top: 10px;">
            <strong><?php _e('Warning:', 'aiohm-kb-assistant'); ?></strong> <?php _e('Resetting is a destructive action that cannot be undone.', 'aiohm-kb-assistant'); ?>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Logic for the Export button
    $('#export-kb-btn').on('click', function(){
        const $btn = $(this);
        const $spinner = $('#export-spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $.post(ajaxurl, {
            action: 'aiohm_export_kb',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>'
        }).done(function(response){
            if (response.success) {
                const data = response.data.data;
                const filename = response.data.filename;
                const blob = new Blob([data], {type: 'application/json'});
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                alert('Error: ' + (response.data.message || 'Could not export.'));
            }
        }).fail(function(){
            alert('An unexpected server error occurred during export.');
        }).always(function(){
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });

    // Logic for the Reset button
    $('#reset-kb-btn').on('click', function(){
        if (!confirm('<?php _e('Are you absolutely sure you want to delete all knowledge base data? This cannot be undone.', 'aiohm-kb-assistant'); ?>')) {
            return;
        }
        const $btn = $(this);
        const $spinner = $('#reset-spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $.post(ajaxurl, {
            action: 'aiohm_reset_kb',
            nonce: '<?php echo wp_create_nonce("aiohm_reset_kb_nonce"); ?>'
        }).done(function(response){
            if (response.success) {
                alert(response.data.message);
                window.location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function(){
            alert('An unexpected server error occurred.');
        }).always(function(){
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
});
</script>