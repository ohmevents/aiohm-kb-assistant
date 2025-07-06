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

    <div class="intro" style="margin: 10px 0; display: flex; flex-wrap: wrap; gap: 20px;">
        <div style="flex: 1; min-width: 280px; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e0e0e0;">
            <h3>&#127758; <?php _e('Public Knowledge (Mirror Mode)', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e("<strong>Public</strong> entries are part of the global knowledge base. They are used by your AI assistant to answer questions from any website visitor.", 'aiohm-kb-assistant'); ?></p>
            <p><?php _e("This is perfect for general support, FAQs, and public information about your brand.", 'aiohm-kb-assistant'); ?></p>
        </div>
        <div style="flex: 1; min-width: 280px; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e0e0e0;">
            <h3>&#128274; <?php _e('Private Knowledge (Muse Mode)', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e("<strong>Private</strong> entries are only accessible to you when using the 'Brand Assistant' chat (Muse Mode).", 'aiohm-kb-assistant'); ?></p>
            <p><?php _e("Use this for personal notes, strategic insights, or confidential brand guidelines that only you should access.", 'aiohm-kb-assistant'); ?></p>
        </div>
    </div>

    <form id="kb-filter-form" method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php
        // The display() method of WP_List_Table will render bulk actions and filters
        // via its built-in functionality and the extra_tablenav() method.
        if (isset($list_table)) {
            $list_table->display();
        } else {
            echo '<p>No knowledge base entries found.</p>';
        }
        ?>
    </form>

    <div id="aiohm-kb-actions" style="margin-top: 20px;">
        <form method="post" action="options.php">
            <?php settings_fields('aiohm_kb_settings'); ?>
            <div class="aiohm-settings-section">
                <h2><?php _e('Knowledge Base Actions', 'aiohm-kb-assistant'); ?></h2>
                <div class="actions-grid-wrapper">

                    <div class="action-box">
                        <h3><?php _e('Backup Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php _e('Create a complete JSON backup of your public knowledge base entries.', 'aiohm-kb-assistant'); ?></p>
                        <button type="button" class="button button-primary button-hero" id="export-kb-btn"><span class="dashicons dashicons-download"></span> <?php _e('Backup KB', 'aiohm-kb-assistant'); ?></button>
                    </div>

                    <div class="action-box">
                        <h3><?php _e('Restore Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php _e('Overwrite all existing public knowledge base entries from a previously saved JSON file.', 'aiohm-kb-assistant'); ?></p>
                        <div class="restore-controls">
                            <input type="file" id="restore-kb-file" accept=".json" style="display: none;">
                            <label for="restore-kb-file" class="button button-secondary"><span class="dashicons dashicons-upload"></span> <?php _e('Choose File...', 'aiohm-kb-assistant'); ?></label>
                            <span id="restore-file-name" style="margin-left: 10px; font-style: italic; vertical-align: middle;"></span>
                            <button type="button" class="button button-primary button-hero" id="restore-kb-btn" disabled><?php _e('Restore KB', 'aiohm-kb-assistant'); ?></button>
                        </div>
                    </div>

                    <div class="action-box reset-action">
                        <h3><?php _e('Reset Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description" style="color: #dc3545;"><strong><?php _e('Warning: This will permanently delete ALL knowledge base entries (public & private). This cannot be undone.', 'aiohm-kb-assistant'); ?></strong></p>
                        <button type="button" class="button button-danger button-hero" id="reset-kb-btn"><span class="dashicons dashicons-trash"></span> <?php _e('Reset Entire KB', 'aiohm-kb-assistant'); ?></button>
                    </div>
                </div>
            </div>
            <?php // The submit button for 'Save Schedule Setting' is removed as it's no longer relevant here. ?>
        </form>
    </div>
</div>

<style>
/* Existing styles */
.aiohm-settings-section { background: #fff; padding: 1px 20px 20px; border: 1px solid #dcdcde; }
.action-item { padding: 20px; background: #f8f9fa; border-radius: 4px; } /* Kept for reference but replaced by .action-box */
.action-item h3 { margin-top: 0; } /* Kept for reference */
.action-item .spinner { vertical-align: middle; } /* Kept for reference */
.action-item.reset-action { border-left: 4px solid #dc3545; } /* Kept for reference, apply to .action-box */

/* New styles for the 3-column grid layout */
.actions-grid-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive 3-column grid */
    gap: 20px;
    margin-top: 20px;
}

.action-box {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 20px;
    display: flex; /* Use flex for vertical alignment within box */
    flex-direction: column;
    justify-content: space-between; /* Pushes content to top, button to bottom */
    align-items: flex-start; /* Aligns content to the left */
}

.action-box h3 {
    margin-top: 0;
    font-size: 1.1em;
    margin-bottom: 10px;
}

.action-box p.description {
    font-size: 0.9em;
    color: #555;
    margin-bottom: 15px;
    flex-grow: 1; /* Allows description to take available space */
}

.action-box .button-hero {
    font-size: 1.1em;
    padding: 10px 20px;
    height: auto; /* Override default button height */
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: auto; /* Pushes button to bottom if flex-direction is column */
}

.action-box.reset-action {
    border-left: 4px solid #dc3545; /* Red border for reset action */
}

.restore-controls {
    width: 100%;
    display: flex;
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    gap: 10px;
    align-items: center;
    margin-top: auto; /* Push to bottom */
}

.restore-controls label.button-secondary {
    margin-bottom: 0; /* Remove default margin for labels */
}

/* Styles for filters to align with bulk actions (no search input now) */
.tablenav .actions select,
.tablenav .actions input[type="submit"] {
    margin-right: 10px;
    vertical-align: top;
}
.tablenav .alignleft.actions.filters-block {
    float: left;
    display: inline-block;
    vertical-align: top;
    margin-top: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';

    // Function to display admin notices
    function showAdminNotice(message, type = 'success') {
        const $noticeDiv = $('#aiohm-admin-notice');
        if ($noticeDiv.length === 0) {
            // Create the notice div if it doesn't exist
            $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;"><p></p></div>').insertAfter('h1');
            $noticeDiv = $('#aiohm-admin-notice');
        }
        $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type);
        $noticeDiv.find('p').html(message);
        $noticeDiv.fadeIn();
        // Automatically hide the notice after 5 seconds
        setTimeout(() => $noticeDiv.fadeOut(), 5000);
    }


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
                showAdminNotice('Knowledge base exported successfully!', 'success');
            } else {
                showAdminNotice('Error: ' + (response.data.message || 'Could not export.'), 'error');
            }
        }).fail(function(){
            showAdminNotice('An unexpected server error occurred during export.', 'error');
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
                showAdminNotice(response.data.message, 'success');
                window.location.reload();
            } else {
                showAdminNotice('Error: ' + response.data.message, 'error');
            }
        }).fail(function(){
            showAdminNotice('An unexpected server error occurred.', 'error');
        }).always(function(){
            $btn.prop('disabled', false).next('.spinner').remove();
        });
    });

    // Handle single scope toggle (Make Public/Private)
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
                $visibilityCell.removeClass('visibility-public visibility-private').addClass('visibility-' + response.data.new_visibility_text.toLowerCase());

                const oppositeScope = newScope === 'private' ? 'public' : 'private';
                const newButtonText = newScope === 'private' ? 'Make Public' : 'Make Private';
                $btn.data('new-scope', oppositeScope).text(newButtonText);
                showAdminNotice('Entry scope updated to ' + response.data.new_visibility_text + '.', 'success');
            } else {
                showAdminNotice('Error: ' + (response.data.message || 'Could not update scope.'), 'error');
                $btn.text(newScope === 'private' ? 'Make Private' : 'Make Public'); // Revert button text
            }
        }).fail(function(){
            showAdminNotice('An unexpected server error occurred.', 'error');
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
                showAdminNotice('Please select a valid .json file.', 'warning');
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
                    showAdminNotice(response.data.message, 'success');
                    window.location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not restore.'), 'error');
                }
            }).fail(function(){
                showAdminNotice('An unexpected server error occurred during restore.', 'error');
            }).always(function(){
                $btn.prop('disabled', false).next('.spinner').remove();
            });
        };

        reader.readAsText(file);
    });

    // Bulk actions
    $('#doaction, #doaction2').on('click', function() {
        const action = $(this).siblings('select[name^="action"]').val();
        if (action === 'make-public' || action === 'make-private') {
            const selectedIds = $('input[name="entry_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                showAdminNotice('Please select at least one entry for bulk action.', 'warning');
                return false;
            }

            if (!confirm('Are you sure you want to ' + action.replace('-', ' ') + ' the selected entries?')) {
                return false;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);
            const originalBtnText = $btn.val();
            $btn.val('Processing...');

            $.post(ajaxurl, {
                action: 'aiohm_bulk_toggle_kb_scope',
                nonce: nonce,
                content_ids: selectedIds,
                new_scope: action === 'make-public' ? 'public' : 'private'
            }).done(function(response) {
                if (response.success) {
                    showAdminNotice(response.data.message, 'success');
                    window.location.reload(); // Reload to reflect changes
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Bulk action failed.'), 'error');
                }
            }).fail(function() {
                showAdminNotice('An unexpected server error occurred during bulk action.', 'error');
            }).always(function() {
                $btn.prop('disabled', false).val(originalBtnText);
            });
            return false; // Prevent default form submission
        }
    });

});
</script>