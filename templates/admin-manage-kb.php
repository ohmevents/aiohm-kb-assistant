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
        let $noticeDiv = $('#aiohm-admin-notice');
        if ($noticeDiv.length === 0) {
            // Create the notice div if it doesn't exist
            $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;"><p></p></div>').insertAfter('h1.wp-heading-inline');
            $noticeDiv = $('#aiohm-admin-notice');
        }
        $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type);
        $noticeDiv.find('p').html(message);
        $noticeDiv.fadeIn();
        // Automatically hide the notice after 5 seconds, or on dismiss
        $noticeDiv.on('click', '.notice-dismiss', function() {
            $noticeDiv.fadeOut();
        });
        setTimeout(() => $noticeDiv.fadeOut(), 5000);
    }


    $('#export-kb-btn').on('click', function(){
        const $btn = $(this);
        const originalText = $btn.html(); // Store original button text/html
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Exporting...');

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
            $btn.prop('disabled', false).html(originalText); // Restore original button text
        });
    });

    $('#reset-kb-btn').on('click', function(){
        // Replaced native confirm with showAdminNotice for consistency
        showAdminNotice('Are you absolutely sure you want to delete all knowledge base data? This cannot be undone. <button id="confirm-reset-kb" class="button button-small" style="margin-left: 10px;">Confirm Reset</button>', 'warning');

        $('#confirm-reset-kb').on('click', function() {
            const $btn = $(this);
            const originalText = $('#reset-kb-btn').html(); // Store original button text/html
            $('#reset-kb-btn').prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Resetting...');
            $('#aiohm-admin-notice').fadeOut(); // Hide the confirmation notice

            $.post(ajaxurl, {
                action: 'aiohm_reset_kb',
                nonce: nonce
            }).done(function(response){
                if (response.success) {
                    showAdminNotice(response.data.message, 'success');
                    // Reload the page to reflect the reset data, as all entries are removed.
                    window.location.reload();
                } else {
                    showAdminNotice('Error: ' + response.data.message, 'error');
                }
            }).fail(function(){
                showAdminNotice('An unexpected server error occurred.', 'error');
            }).always(function(){
                $('#reset-kb-btn').prop('disabled', false).html(originalText); // Restore original button text
            });
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
        const originalBtnText = $btn.text(); // Store original button text

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
                $btn.text(originalBtnText); // Revert button text on error
            }
        }).fail(function(){
            showAdminNotice('An unexpected server error occurred.', 'error');
            $btn.text(originalBtnText); // Revert button text on failure
        }).always(function(){
            $btn.prop('disabled', false);
        });
    });

    // Handle single delete link
    // Delegated event listener for dynamically loaded content
    $(document).on('click', 'a.button-link-delete', function(e) {
        e.preventDefault();
        const $link = $(this);
        const contentId = $link.closest('tr').find('input[name="entry_ids[]"]').val(); // Get content_id from checkbox

        // Replaced native confirm with showAdminNotice for consistency
        showAdminNotice('Are you sure you want to delete this entry? <button id="confirm-delete-entry" class="button button-small" style="margin-left: 10px;">Confirm Delete</button>', 'warning');

        $('#confirm-delete-entry').on('click', function() {
            const $row = $link.closest('tr');
            const originalLinkText = $link.text();

            $link.prop('disabled', true).text('Deleting...');
            $('#aiohm-admin-notice').fadeOut(); // Hide the confirmation notice

            // Perform AJAX request for delete
            $.post(ajaxurl, {
                action: 'aiohm_delete_kb_entry', // This action is now handled in core-init.php
                nonce: nonce, // Use the main admin nonce
                content_id: contentId
            }).done(function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        showAdminNotice('Entry deleted successfully!', 'success');
                        // Optionally update pagination/total count here if needed without reload
                    });
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not delete entry.'), 'error');
                    $link.prop('disabled', false).text(originalLinkText); // Revert link text on error
                }
            }).fail(function() {
                showAdminNotice('An unexpected server error occurred during deletion.', 'error');
                $link.prop('disabled', false).text(originalLinkText); // Revert link text on failure
            });
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
        // Replaced native confirm with showAdminNotice for consistency
        showAdminNotice('Are you sure you want to restore? This will overwrite all current global knowledge base entries. <button id="confirm-restore-kb" class="button button-small" style="margin-left: 10px;">Confirm Restore</button>', 'warning');
        
        $('#confirm-restore-kb').on('click', function() {
            const $btn = $('#restore-kb-btn');
            const file = $('#restore-kb-file')[0].files[0];
            const reader = new FileReader();
            const originalText = $btn.html(); // Store original button text/html

            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Restoring...');
            $('#aiohm-admin-notice').fadeOut(); // Hide the confirmation notice

            reader.onload = function(e) {
                const jsonData = e.target.result;
                $.post(ajaxurl, {
                    action: 'aiohm_restore_kb',
                    nonce: nonce,
                    json_data: jsonData
                }).done(function(response){
                    if (response.success) {
                        showAdminNotice(response.data.message, 'success');
                        // Reload the page to reflect the restored data, which might involve many new/changed entries
                        window.location.reload();
                    } else {
                        showAdminNotice('Error: ' + (response.data.message || 'Could not restore.'), 'error');
                    }
                }).fail(function(){
                    showAdminNotice('An unexpected server error occurred during restore.', 'error');
                }).always(function(){
                    $btn.prop('disabled', false).html(originalText); // Restore original button text
                });
            };

            if (file) {
                reader.readAsText(file);
            } else {
                showAdminNotice('No file selected for restore.', 'error');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Bulk actions
    // Note: For bulk actions, a full page reload is typically acceptable
    // due to the potential for many changes impacting pagination and filtering.
    $('#doaction, #doaction2').on('click', function(e) {
        e.preventDefault(); // Prevent default form submission
        const action = $(this).siblings('select[name^="action"]').val();
        
        // Only proceed if a specific bulk action is chosen (not '-1')
        if (action === '-1') {
            showAdminNotice('Please select a bulk action from the dropdown.', 'warning');
            return false;
        }

        const selectedIds = $('input[name="entry_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            showAdminNotice('Please select at least one entry for bulk action.', 'warning');
            return false;
        }

        let confirmationMessage = '';
        let confirmBtnText = '';
        let ajaxAction = '';

        if (action === 'bulk-delete') {
            confirmationMessage = 'Are you sure you want to delete the selected entries? This cannot be undone.';
            confirmBtnText = 'Confirm Delete';
            ajaxAction = 'aiohm_bulk_delete_kb'; // Assuming this action exists
        } else if (action === 'make-public' || action === 'make-private') {
            confirmationMessage = 'Are you sure you want to ' + action.replace('-', ' ') + ' the selected entries?';
            confirmBtnText = 'Confirm ' + action.replace('-', ' ');
            ajaxAction = 'aiohm_bulk_toggle_kb_scope';
        } else {
            // Should not happen if select value is validated
            showAdminNotice('Invalid bulk action selected.', 'error');
            return false;
        }
        
        // Replaced native confirm with showAdminNotice for consistency
        showAdminNotice(`${confirmationMessage} <button id="confirm-bulk-action" class="button button-small" style="margin-left: 10px;">${confirmBtnText}</button>`, 'warning');

        $('#confirm-bulk-action').on('click', function() {
            const $btn = $(this);
            const originalBtnText = $('#doaction').val(); // Get text from top bulk action button
            $('#doaction, #doaction2').prop('disabled', true).val('Processing...'); // Disable both bulk action buttons
            $('#aiohm-admin-notice').fadeOut(); // Hide the confirmation notice

            $.post(ajaxurl, {
                action: ajaxAction,
                nonce: nonce,
                content_ids: selectedIds,
                new_scope: (action === 'make-public' || action === 'make-private') ? action.replace('make-', '') : undefined // Only send new_scope for toggle actions
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
                $('#doaction, #doaction2').prop('disabled', false).val(originalBtnText); // Re-enable and restore text
            });
        });
        return false; // Prevent default form submission initially
    });
});
</script>