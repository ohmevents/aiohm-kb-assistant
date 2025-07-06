<?php
/**
 * Template for the Manage Knowledge Base admin page.
 * This is the final version with all UI improvements and working scripts.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Manage Knowledge Base', 'aiohm-kb-assistant'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg(['page' => 'aiohm-scan-content'], admin_url('admin.php'))); ?>" class="page-title-action"><?php _e('Add New Content', 'aiohm-kb-assistant'); ?></a>

    <hr class="wp-header-end">
    
    <div class="intro" style="margin: 10px 0;">
        <?php
        $intro_text = "This page is your command center for managing everything your AI has learned. The table below shows every content entry and its visibility status:
        <ul>
            <li>&#127758; <strong>Public</strong> entries are part of the global knowledge base and are used by the AI to answer questions from any website visitor.</li>
            <li>&#128274; <strong>Private</strong> entries are only accessible to you when using the 'Brand Assistant' chat. This is perfect for personal notes or brand strategy.</li>
        </ul>
        Use the buttons in the 'Actions' column to switch an entry between Public and Private, view the original source, or permanently delete it.";
        echo wp_kses_post($intro_text);
        ?>
    </div>

    <?php
    if (isset($list_table)) {
        $list_table->display();
    } else {
        echo '<p>No knowledge base entries found.</p>';
    }
    ?>

    <div id="aiohm-kb-actions" style="margin-top: 20px;">
        <form method="post" action="options.php">
            <?php settings_fields('aiohm_kb_settings'); ?>
            <div class="aiohm-settings-section">
                <h2><?php _e('Knowledge Base Actions', 'aiohm-kb-assistant'); ?></h2>
                <div class="actions-wrapper">
                    
                    <div class="action-item">
                        <h3><?php _e('Backup / Restore', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php _e('Save a complete backup of your global KB, or restore it from a previously saved JSON file.', 'aiohm-kb-assistant'); ?></p>
                        <button type="button" class="button button-secondary" id="export-kb-btn"><span class="dashicons dashicons-download"></span> <?php _e('Backup Knowledge Base', 'aiohm-kb-assistant'); ?></button>
                        <hr style="margin: 15px 0;">
                        <input type="file" id="restore-kb-file" accept=".json" style="display: none;">
                        <label for="restore-kb-file" class="button button-secondary"><span class="dashicons dashicons-upload"></span> <?php _e('Choose JSON File...', 'aiohm-kb-assistant'); ?></label>
                        <button type="button" class="button button-primary" id="restore-kb-btn" disabled><?php _e('Restore', 'aiohm-kb-assistant'); ?></button>
                        <span id="restore-file-name" style="margin-left: 10px; font-style: italic;"></span>
                    </div>

                    <div class="action-item">
                        <h3><?php _e('Automatic Scanning', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php _e('Automatically scan your site to keep the knowledge base updated.', 'aiohm-kb-assistant'); ?></p>
                        <select id="scan_schedule" name="aiohm_kb_settings[scan_schedule]">
                            <option value="none" <?php selected($settings['scan_schedule'], 'none'); ?>><?php _e('None (Manual Only)', 'aiohm-kb-assistant'); ?></option>
                            <option value="daily" <?php selected($settings['scan_schedule'], 'daily'); ?>><?php _e('Once a day', 'aiohm-kb-assistant'); ?></option>
                            <option value="weekly" <?php selected($settings['scan_schedule'], 'weekly'); ?>><?php _e('Once a week', 'aiohm-kb-assistant'); ?></option>
                        </select>
                    </div>

                    <div class="action-item reset-action">
                        <h3><?php _e('Reset Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description" style="color: #dc3545;"><strong><?php _e('Warning: This will permanently delete all entries. It cannot be undone.', 'aiohm-kb-assistant'); ?></strong></p>
                        <button type="button" class="button button-danger" id="reset-kb-btn"><span class="dashicons dashicons-trash"></span> <?php _e('Reset Entire KB', 'aiohm-kb-assistant'); ?></button>
                    </div>
                </div>
                <?php submit_button('Save Schedule Setting'); ?>
            </div>
        </form>
    </div>
</div>

<style>
.aiohm-settings-section { background: #fff; padding: 1px 20px 20px; border: 1px solid #dcdcde; }
.actions-wrapper { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
.action-item { padding: 20px; background: #f8f9fa; border-radius: 4px; }
.action-item h3 { margin-top: 0; }
.action-item .spinner { vertical-align: middle; }
.action-item.reset-action { border-left: 4px solid #dc3545; }
.intro ul { list-style: none; margin-left: 0; padding-left: 0; }
.intro ul li { margin-bottom: 0.5em; }
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';

    $('#export-kb-btn').on('click', function(){
        const $btn = $(this);
        $btn.prop('disabled', true).after('<span class="spinner is-active" style="vertical-align: middle;"></span>');
        
        $.post(ajaxurl, {
            action: 'aiohm_export_kb',
            nonce: nonce
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
            $btn.prop('disabled', false).next('.spinner').remove();
        });
    });

    $('#reset-kb-btn').on('click', function(){
        if (!confirm('<?php _e('Are you absolutely sure you want to delete all knowledge base data? This cannot be undone.', 'aiohm-kb-assistant'); ?>')) return;
        
        const $btn = $(this);
        $btn.prop('disabled', true).after('<span class="spinner is-active" style="vertical-align: middle;"></span>');

        $.post(ajaxurl, {
            action: 'aiohm_reset_kb',
            nonce: nonce 
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
            $btn.prop('disabled', false).next('.spinner').remove();
        });
    });

    $('.scope-toggle-btn').on('click', function(e){
        e.preventDefault();
        const $btn = $(this);
        const contentId = $btn.data('content-id');
        const newScope = $btn.data('new-scope');
        const $row = $btn.closest('tr');
        const $visibilityCell = $row.find('.column-user_id .visibility-text');
        
        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'aiohm_toggle_kb_scope',
            nonce: nonce,
            content_id: contentId,
            new_scope: newScope
        }).done(function(response){
            if (response.success) {
                $visibilityCell.text(response.data.new_visibility_text);
                const oppositeScope = newScope === 'private' ? 'public' : 'private';
                const newButtonText = newScope === 'private' ? 'Make Public' : 'Make Private';
                $btn.data('new-scope', oppositeScope).text(newButtonText);
            } else {
                alert('Error: ' + (response.data.message || 'Could not update scope.'));
                $btn.text(newScope === 'private' ? 'Make Private' : 'Make Public');
            }
        }).fail(function(){
            alert('An unexpected server error occurred.');
        }).always(function(){
            $btn.prop('disabled', false);
        });
    });

    $('#restore-kb-file').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type === 'application/json') {
            $('#restore-file-name').text(file.name);
            $('#restore-kb-btn').prop('disabled', false);
        } else {
            $('#restore-file-name').text('');
            $('#restore-kb-btn').prop('disabled', true);
            if (file) {
                alert('Please select a valid .json file.');
            }
        }
    });

    $('#restore-kb-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to restore? This will overwrite all current global knowledge base entries.', 'aiohm-kb-assistant'); ?>')) return;

        const $btn = $(this);
        const file = $('#restore-kb-file')[0].files[0];
        const reader = new FileReader();

        $btn.prop('disabled', true).after('<span class="spinner is-active" style="vertical-align: middle;"></span>');
        
        reader.onload = function(e) {
            const jsonData = e.target.result;
            $.post(ajaxurl, {
                action: 'aiohm_restore_kb',
                nonce: nonce,
                json_data: jsonData
            }).done(function(response){
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Could not restore.'));
                }
            }).fail(function(){
                alert('An unexpected server error occurred during restore.');
            }).always(function(){
                $btn.prop('disabled', false).next('.spinner').remove();
            });
        };
        
        reader.readAsText(file);
    });
});
</script>