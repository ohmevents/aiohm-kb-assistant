<?php
/**
 * Template for the Manage Knowledge Base admin page.
 * This is the final version with all UI improvements and working scripts.
 */
if (!defined('ABSPATH')) exit;

// Include the header for consistent branding
include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/header.php';
?>

<div class="wrap aiohm-manage-kb-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('Manage Knowledge Base', 'aiohm-kb-assistant'); ?></h1>
    <button type="button" id="add-content-btn" class="page-title-action"><?php esc_html_e('Add New Content', 'aiohm-kb-assistant'); ?></button>
    <a href="<?php echo esc_url(add_query_arg(['page' => 'aiohm-scan-content'], admin_url('admin.php'))); ?>" class="page-title-action" style="margin-left: 10px;"><?php esc_html_e('Scan Website', 'aiohm-kb-assistant'); ?></a>
    <p class="page-description"><?php esc_html_e('View, organize, and manage all your knowledge base entries in one place.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice is-dismissible" style="display:none; margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>

    <hr class="wp-header-end">

    <div class="aiohm-knowledge-intro">
        <div class="knowledge-section public-section">
            <div class="section-header">
                <h3><span class="section-icon">🌍</span> <?php esc_html_e('Public Knowledge (Mirror Mode)', 'aiohm-kb-assistant'); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-mirror-mode')); ?>" class="button button-secondary section-link">
                    <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Configure Mirror Mode', 'aiohm-kb-assistant'); ?>
                </a>
            </div>
            <p><?php 
                // translators: %s is the word "Public" that will be bolded
                printf(esc_html__('%s entries are part of the global knowledge base. They are used by your AI assistant to answer questions from any website visitor.', 'aiohm-kb-assistant'), '<strong>' . esc_html__('Public', 'aiohm-kb-assistant') . '</strong>'); ?></p>
            <p><?php esc_html_e('This is perfect for general support, FAQs, and public information about your brand.', 'aiohm-kb-assistant'); ?></p>
        </div>
        <div class="knowledge-section private-section">
            <div class="section-header">
                <h3><span class="section-icon">🔒</span> <?php esc_html_e('Private Knowledge (Muse Mode)', 'aiohm-kb-assistant'); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-muse-mode')); ?>" class="button button-secondary section-link">
                    <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Configure Muse Mode', 'aiohm-kb-assistant'); ?>
                </a>
            </div>
            <p><?php 
                // translators: %s is the word "Private" that will be bolded
                printf(esc_html__('%s entries are only accessible to you when using the Brand Assistant chat (Muse Mode).', 'aiohm-kb-assistant'), '<strong>' . esc_html__('Private', 'aiohm-kb-assistant') . '</strong>'); ?></p>
            <p><?php esc_html_e('Use this for personal notes, strategic insights, or confidential brand guidelines that only you should access.', 'aiohm-kb-assistant'); ?></p>
        </div>
    </div>

    <form id="kb-filter-form" method="get">
        <!-- Admin filter form - page parameter safe for admin interface -->
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin form page parameter, read-only
        $page_param = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
        ?>
        <input type="hidden" name="page" value="<?php echo esc_attr($page_param); ?>" />
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
                <h2><?php esc_html_e('Knowledge Base Actions', 'aiohm-kb-assistant'); ?></h2>
                <div class="actions-grid-wrapper">

                    <div class="action-box">
                        <h3><?php esc_html_e('Export Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php esc_html_e('Create a complete JSON backup of your public knowledge base entries.', 'aiohm-kb-assistant'); ?></p>
                        <button type="button" class="button button-primary button-hero" id="export-kb-btn"><span class="dashicons dashicons-download"></span> <?php esc_html_e('Export KB', 'aiohm-kb-assistant'); ?></button>
                    </div>

                    <div class="action-box">
                        <h3><?php esc_html_e('Restore Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description"><?php esc_html_e('Overwrite all existing public knowledge base entries from a previously saved JSON file.', 'aiohm-kb-assistant'); ?></p>
                        <div class="restore-controls">
                            <div class="file-input-group">
                                <input type="file" id="restore-kb-file" accept=".json" style="display: none;">
                                <label for="restore-kb-file" class="button button-secondary"><span class="dashicons dashicons-upload"></span> <?php esc_html_e('Choose File...', 'aiohm-kb-assistant'); ?></label>
                                <span id="restore-file-name" class="file-name-display"></span>
                            </div>
                            <button type="button" class="button button-primary button-hero" id="restore-kb-btn" disabled><?php esc_html_e('Restore KB', 'aiohm-kb-assistant'); ?></button>
                        </div>
                    </div>

                    <div class="action-box reset-action">
                        <h3><?php esc_html_e('Reset Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
                        <p class="description" style="color: #dc3545;"><strong><?php esc_html_e('Warning: This will permanently delete ALL knowledge base entries (public & private). This cannot be undone.', 'aiohm-kb-assistant'); ?></strong></p>
                        <button type="button" class="button button-danger button-hero" id="reset-kb-btn"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Reset Entire KB', 'aiohm-kb-assistant'); ?></button>
                    </div>
                </div>
            </div>
            <?php // The submit button for 'Save Schedule Setting' is removed as it's no longer relevant here. ?>
        </form>
    </div>
</div>

<style>
/* === AIOHM Manage KB Page Styles === */
/* OHM Brand Colors:
   Light Grey: #EBEBEB
   Dark Green: #1f5014  
   Dark Grey: #272727
   Light Green: #457d58
*/

/* Page branding */
.aiohm-manage-kb-page {
    font-family: 'PT Sans', sans-serif;
}

.aiohm-manage-kb-page h1 {
    font-family: 'Montserrat', sans-serif;
    color: #1f5014;
    font-weight: bold;
}

.page-description {
    color: #666;
    font-size: 16px;
    margin: 10px 0 20px 0;
}

/* Knowledge Base Introduction Section */
.aiohm-knowledge-intro {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.knowledge-section {
    flex: 1;
    min-width: 300px;
    padding: 20px;
    border-radius: 8px;
    border: 2px solid;
    position: relative;
}

.knowledge-section.public-section {
    background: linear-gradient(135deg, #f8fbf9 0%, #f0f8f4 100%);
    border-color: #457d58;
}

.knowledge-section.private-section {
    background: linear-gradient(135deg, #f9f9f9 0%, #EBEBEB 100%);
    border-color: #272727;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.section-header h3 {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    font-size: 18px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-icon {
    font-size: 20px;
}

.section-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    font-size: 14px;
    background: #457d58 !important;
    color: white !important;
    border: 1px solid #457d58 !important;
    padding: 6px 12px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.section-link:hover {
    background: #1f5014 !important;
    border-color: #1f5014 !important;
    color: white !important;
    text-decoration: none;
}

/* Settings Section */
.aiohm-settings-section { 
    background: #fff; 
    padding: 1px 20px 20px; 
    border: 1px solid #dcdcde; 
    border-radius: 8px;
    margin-top: 20px;
}

.aiohm-settings-section h2 {
    font-family: 'Montserrat', sans-serif;
    color: #1f5014;
    border-bottom: 2px solid #457d58;
    padding-bottom: 10px;
}

/* Actions Grid */
.actions-grid-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.action-box {
    background: #fff;
    border: 2px solid #EBEBEB;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.action-box:hover {
    border-color: #457d58;
    box-shadow: 0 4px 12px rgba(69, 125, 88, 0.15);
}

.action-box h3 {
    margin-top: 0;
    font-size: 1.2em;
    margin-bottom: 10px;
    font-family: 'Montserrat', sans-serif;
    color: #1f5014;
}

.action-box p.description {
    font-size: 0.9em;
    color: #272727;
    margin-bottom: 15px;
    flex-grow: 1;
    line-height: 1.5;
}

.action-box .button-hero {
    font-size: 1.1em;
    padding: 12px 20px;
    height: auto;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    border-radius: 6px;
    font-weight: bold;
    transition: all 0.2s ease;
    background: #457d58 !important;
    border-color: #457d58 !important;
    color: white !important;
}

.action-box .button-hero:hover {
    background: #1f5014 !important;
    border-color: #1f5014 !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(31, 80, 20, 0.3);
}

.action-box.reset-action {
    border-left: 4px solid #272727;
}

.action-box.reset-action:hover {
    border-color: #272727;
    box-shadow: 0 4px 12px rgba(39, 39, 39, 0.15);
}

.action-box.reset-action .button-hero {
    background: #272727 !important;
    border-color: #272727 !important;
}

.action-box.reset-action .button-hero:hover {
    background: #1a1a1a !important;
    border-color: #1a1a1a !important;
}

/* Restore Controls */
.restore-controls {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: auto;
}

.file-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.file-input-group label.button-secondary {
    background: #EBEBEB !important;
    border-color: #EBEBEB !important;
    color: #272727 !important;
}

.file-input-group label.button-secondary:hover {
    background: #272727 !important;
    border-color: #272727 !important;
    color: white !important;
}

.file-name-display {
    font-style: italic;
    color: #272727;
    font-size: 14px;
    min-height: 20px;
}

/* Table Enhancements */
.wp-list-table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.wp-list-table th {
    background: #EBEBEB;
    color: #272727;
    font-family: 'Montserrat', sans-serif;
    font-weight: bold;
    position: relative;
    padding: 12px 10px;
    border-bottom: 1px solid #272727;
}

.wp-list-table th.sortable a,
.wp-list-table th.sorted a {
    color: #272727;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* WordPress default sortable column enhancements */
.wp-list-table th.sortable a::after,
.wp-list-table th.sorted a::after {
    content: "↕";
    font-size: 12px;
    margin-left: 8px;
    opacity: 0.6;
    font-weight: normal;
}

.wp-list-table th.sorted.asc a::after {
    content: "↑";
    opacity: 1;
    color: #457d58;
}

.wp-list-table th.sorted.desc a::after {
    content: "↓";
    opacity: 1;
    color: #457d58;
}

.wp-list-table th.sortable:hover a::after {
    opacity: 1;
}

/* Visibility badges */
.visibility-text {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.visibility-public {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.visibility-private {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Content type badges */
.aiohm-content-type-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block;
}

.type-post { background: #e3f2fd; color: #1976d2; }
.type-page { background: #f3e5f5; color: #7b1fa2; }
.type-pdf { background: #ffebee; color: #d32f2f; }
.type-txt { background: #e8f5e8; color: #388e3c; }
.type-csv { background: #fff3e0; color: #f57c00; }
.type-json { background: #e0f2f1; color: #00695c; }
.type-manual { background: #e1f5fe; color: #0277bd; }
.type-brand-soul { background: #f3e5f5; color: #8e24aa; }
.type-brand-core { background: #e8f5e8; color: #1f5014; }
.type-github { background: #f0f0f0; color: #272727; }
.type-contact { background: #fff8e1; color: #457d58; }
.type-note { background: #e8f4fd; color: #1565c0; }
.type-chat { background: #e8f5e8; color: #2e7d32; }
.type-default { background: #f5f5f5; color: #616161; }

/* Filter block styling */
.tablenav .actions select,
.tablenav .actions input[type="submit"] {
    margin-right: 10px;
    vertical-align: top;
    border-radius: 4px;
}

.tablenav .actions input[type="submit"] {
    background: #457d58 !important;
    border-color: #457d58 !important;
    color: white !important;
}

.tablenav .actions input[type="submit"]:hover {
    background: #1f5014 !important;
    border-color: #1f5014 !important;
}

.tablenav .alignleft.actions.filters-block {
    float: left;
    display: inline-block;
    vertical-align: top;
    margin-top: 0;
}

/* Admin notices enhancement */
#aiohm-admin-notice {
    border-radius: 6px;
    border-left-width: 4px;
}

#aiohm-admin-notice.notice-success {
    border-left-color: #457d58;
    background: #f8fbf9;
}

#aiohm-admin-notice.notice-error {
    border-left-color: #272727;
    background: #f9f9f9;
}

#aiohm-admin-notice.notice-warning {
    border-left-color: #EBEBEB;
    background: #fafafa;
}

/* Confirmation dialog buttons styling */
#aiohm-admin-notice .button {
    margin: 0 5px;
    vertical-align: baseline;
}

#aiohm-admin-notice .button-small {
    padding: 2px 8px;
    font-size: 12px;
    line-height: 1.5;
}

#aiohm-admin-notice .button:first-of-type {
    background: #457d58;
    border-color: #457d58;
    color: white;
}

#aiohm-admin-notice .button:first-of-type:hover {
    background: #1f5014;
    border-color: #1f5014;
}

#aiohm-admin-notice .button-secondary {
    margin-left: 10px;
}

/* Action links styling */
.scope-toggle-btn,
.view-brand-soul-btn,
.view-content-btn,
.view-pdf-btn,
.view-link-btn {
    text-decoration: none;
    color: #457d58;
}

.scope-toggle-btn:hover,
.view-brand-soul-btn:hover,
.view-content-btn:hover,
.view-pdf-btn:hover,
.view-link-btn:hover {
    color: #1f5014;
    text-decoration: underline;
}

.button-link-delete {
    color: #272727;
}

.button-link-delete:hover {
    color: #1a1a1a;
    text-decoration: underline;
}

/* Modal Styles */
.aiohm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aiohm-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    cursor: pointer;
}

.aiohm-modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    max-width: 90%;
    max-height: 90%;
    width: 600px;
    z-index: 10001;
    display: flex;
    flex-direction: column;
}

.aiohm-modal-header {
    padding: 20px;
    border-bottom: 1px solid #EBEBEB;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #1f5014 0%, #457d58 100%);
    color: white;
    border-radius: 8px 8px 0 0;
}

.aiohm-modal-header h2 {
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    font-size: 20px;
}

.aiohm-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.aiohm-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.aiohm-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex-grow: 1;
}

.brand-soul-loading {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}

.brand-soul-content {
    line-height: 1.6;
    font-family: 'PT Sans', sans-serif;
}

.brand-soul-content pre {
    background: #EBEBEB;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #457d58;
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: #272727;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .aiohm-knowledge-intro {
        flex-direction: column;
    }
    
    .knowledge-section {
        min-width: 100%;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .actions-grid-wrapper {
        grid-template-columns: 1fr;
    }
    
    .file-input-group {
        flex-direction: column;
        align-items: flex-start;
    }

    .aiohm-modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .aiohm-modal-header {
        padding: 15px;
    }
    
    .aiohm-modal-body {
        padding: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo esc_js(wp_create_nonce("aiohm_admin_nonce")); ?>';

    // Function to display admin notices - moved to bottom for consolidation


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
        // Use persistent admin notice for important confirmations
        showAdminNotice('Are you absolutely sure you want to delete all knowledge base data? This cannot be undone. <button id="confirm-reset-kb" class="button button-small" style="margin-left: 10px;">Confirm Reset</button> <button id="cancel-reset-kb" class="button button-secondary button-small" style="margin-left: 5px;">Cancel</button>', 'warning', true);

        // Handle confirm button
        $(document).off('click.reset-confirm').on('click.reset-confirm', '#confirm-reset-kb', function() {
            const $btn = $(this);
            const originalText = $('#reset-kb-btn').html(); // Store original button text/html
            $('#reset-kb-btn').prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Resetting...');
            $('#aiohm-admin-notice').fadeOut(300); // Hide the confirmation notice

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

        // Handle cancel button
        $(document).off('click.reset-cancel').on('click.reset-cancel', '#cancel-reset-kb', function() {
            $('#aiohm-admin-notice').fadeOut(300, function() {
                $('#reset-kb-btn').focus(); // Return focus to the original button
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

        // Use persistent admin notice for important confirmations
        showAdminNotice('Are you sure you want to delete this entry? <button id="confirm-delete-entry" class="button button-small" style="margin-left: 10px;">Confirm Delete</button> <button id="cancel-delete-entry" class="button button-secondary button-small" style="margin-left: 5px;">Cancel</button>', 'warning', true);

        // Handle confirm button
        $(document).off('click.delete-confirm').on('click.delete-confirm', '#confirm-delete-entry', function() {
            const $row = $link.closest('tr');
            const originalLinkText = $link.text();

            $link.prop('disabled', true).text('Deleting...');
            $('#aiohm-admin-notice').fadeOut(300); // Hide the confirmation notice

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

        // Handle cancel button
        $(document).off('click.delete-cancel').on('click.delete-cancel', '#cancel-delete-entry', function() {
            $('#aiohm-admin-notice').fadeOut(300, function() {
                $link.focus(); // Return focus to the original delete link
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
        // Use persistent admin notice for important confirmations
        showAdminNotice('Are you sure you want to restore? This will overwrite all current global knowledge base entries. <button id="confirm-restore-kb" class="button button-small" style="margin-left: 10px;">Confirm Restore</button> <button id="cancel-restore-kb" class="button button-secondary button-small" style="margin-left: 5px;">Cancel</button>', 'warning', true);
        
        // Handle confirm button
        $(document).off('click.restore-confirm').on('click.restore-confirm', '#confirm-restore-kb', function() {
            const $btn = $('#restore-kb-btn');
            const file = $('#restore-kb-file')[0].files[0];
            const reader = new FileReader();
            const originalText = $btn.html(); // Store original button text/html

            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Restoring...');
            $('#aiohm-admin-notice').fadeOut(300); // Hide the confirmation notice

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

        // Handle cancel button
        $(document).off('click.restore-cancel').on('click.restore-cancel', '#cancel-restore-kb', function() {
            $('#aiohm-admin-notice').fadeOut(300, function() {
                $('#restore-kb-btn').focus(); // Return focus to the original button
            });
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
        
        // Use persistent admin notice for important confirmations
        showAdminNotice(`${confirmationMessage} <button id="confirm-bulk-action" class="button button-small" style="margin-left: 10px;">${confirmBtnText}</button> <button id="cancel-bulk-action" class="button button-secondary button-small" style="margin-left: 5px;">Cancel</button>`, 'warning', true);

        // Handle confirm button
        $(document).off('click.bulk-confirm').on('click.bulk-confirm', '#confirm-bulk-action', function() {
            const $btn = $(this);
            const originalBtnText = $('#doaction').val(); // Get text from top bulk action button
            $('#doaction, #doaction2').prop('disabled', true).val('Processing...'); // Disable both bulk action buttons
            $('#aiohm-admin-notice').fadeOut(300); // Hide the confirmation notice

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

        // Handle cancel button
        $(document).off('click.bulk-cancel').on('click.bulk-cancel', '#cancel-bulk-action', function() {
            $('#aiohm-admin-notice').fadeOut(300, function() {
                $('#doaction').focus(); // Return focus to the bulk action button
            });
        });

        return false; // Prevent default form submission initially
    });

    // Handle View Content button (for Brand Soul, Brand Core, GitHub, Contact, etc.)
    $(document).on('click', '.view-content-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const contentId = $btn.data('content-id');
        const contentType = $btn.data('content-type');
        
        // Show modal with content
        showContentModal(contentId, contentType);
    });

    // Backward compatibility for old Brand Soul button
    $(document).on('click', '.view-brand-soul-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const contentId = $btn.data('content-id');
        
        // Show modal with Brand Soul content
        showContentModal(contentId, 'brand-soul');
    });

    // Function to show content in a modal
    function showContentModal(contentId, contentType) {
        // Determine modal title based on content type
        const modalTitles = {
            'brand-soul': 'Brand Soul Content',
            'brand_soul': 'Brand Soul Content',
            'brand-core': 'Brand Core Content',
            'brand_core': 'Brand Core Content',
            'github': 'GitHub Content',
            'repository': 'Repository Content',
            'contact': 'Contact Information',
            'contact_type': 'Contact Information'
        };
        const modalTitle = modalTitles[contentType] || 'Content Details';

        // Create modal if it doesn't exist
        if ($('#content-modal').length === 0) {
            $('body').append(`
                <div id="content-modal" class="aiohm-modal" style="display: none;">
                    <div class="aiohm-modal-backdrop"></div>
                    <div class="aiohm-modal-content">
                        <div class="aiohm-modal-header">
                            <h2 class="modal-title">${modalTitle}</h2>
                            <button class="aiohm-modal-close" type="button">&times;</button>
                        </div>
                        <div class="aiohm-modal-body">
                            <div class="content-loading">Loading...</div>
                            <div class="content-display" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        const $modal = $('#content-modal');
        const $loading = $modal.find('.content-loading');
        const $content = $modal.find('.content-display');
        const $title = $modal.find('.modal-title');
        
        // Update modal title
        $title.text(modalTitle);
        
        // Show modal and reset state
        $modal.show();
        $loading.show();
        $content.hide().empty();
        
        // Fetch content
        $.post(ajaxurl, {
            action: 'aiohm_get_content_for_view',
            nonce: nonce,
            content_id: contentId,
            content_type: contentType
        }).done(function(response) {
            if (response.success && response.data) {
                $content.html('<pre style="white-space: pre-wrap; font-family: inherit;">' + response.data.content + '</pre>');
                $loading.hide();
                $content.show();
            } else {
                $content.html('<p>Error loading content.</p>');
                $loading.hide();
                $content.show();
            }
        }).fail(function() {
            $content.html('<p>Failed to load content.</p>');
            $loading.hide();
            $content.show();
        });
    }

    // Handle modal close
    $(document).on('click', '.aiohm-modal-close, .aiohm-modal-backdrop', function() {
        $('#content-modal').hide();
        // Backward compatibility
        $('#brand-soul-modal').hide();
    });

    // Close modal with ESC key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            if ($('#content-modal').is(':visible')) {
                $('#content-modal').hide();
            }
            // Backward compatibility
            if ($('#brand-soul-modal').is(':visible')) {
                $('#brand-soul-modal').hide();
            }
        }
    });

    // Enhanced admin notice function with accessibility features
    function showAdminNotice(message, type = 'success', persistent = false) {
        let $noticeDiv = $('#aiohm-admin-notice');
        
        // Create notice div if it doesn't exist
        if ($noticeDiv.length === 0) {
            $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>').insertAfter('h1.wp-heading-inline');
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
            $('h1.wp-heading-inline').focus();
        });
        
        // Auto-hide after timeout (unless persistent)
        if (!persistent) {
            setTimeout(() => {
                if ($noticeDiv.is(':visible')) {
                    $noticeDiv.fadeOut(300, function() {
                        // Return focus to main content when auto-hiding
                        $('h1.wp-heading-inline').focus();
                    });
                }
            }, 7000); // Increased to 7 seconds for better UX
        }
    }

    // File Upload Modal functionality
    $('#add-content-btn').on('click', function() {
        showFileUploadModal();
    });

    function showFileUploadModal() {
        // Remove any existing modal
        $('#file-upload-modal').remove();
        
        // Create upload modal
        $('body').append(`
            <div id="file-upload-modal" class="aiohm-modal" style="display: flex;">
                <div class="aiohm-modal-backdrop"></div>
                <div class="aiohm-modal-content" style="max-width: 600px;">
                    <div class="aiohm-modal-header">
                        <h2>Upload Files to Knowledge Base</h2>
                        <button type="button" class="aiohm-modal-close">&times;</button>
                    </div>
                    <div class="aiohm-modal-body">
                        <div id="upload-section">
                            <p>Upload documents directly to your knowledge base. Supported formats: .txt, .json, .csv, .pdf, .doc, .docx, .md</p>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="kb-scope" style="font-weight: bold; margin-bottom: 10px; display: block;">Knowledge Base Scope:</label>
                                <select id="kb-scope" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="public">Public (Mirror Mode - visible to all visitors)</option>
                                    <option value="private">Private (Muse Mode - visible only to you)</option>
                                </select>
                            </div>
                            
                            <input type="file" id="file-input" multiple accept=".txt,.json,.csv,.pdf,.doc,.docx,.md" style="display: none;">
                            <div id="drop-zone" style="border: 2px dashed #457d58; padding: 40px; text-align: center; border-radius: 8px; background: #f8fbf9; margin-bottom: 20px; cursor: pointer;">
                                <p style="margin: 0; color: #457d58; font-size: 16px;"><strong>Drop files here or click to browse</strong></p>
                                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">Maximum file size: 10MB per file</p>
                            </div>
                            
                            <div id="file-list" style="margin-bottom: 20px;"></div>
                            
                            <div style="text-align: right;">
                                <button type="button" id="cancel-upload" class="button button-secondary" style="margin-right: 10px;">Cancel</button>
                                <button type="button" id="start-upload" class="button button-primary" disabled>Upload to Knowledge Base</button>
                            </div>
                        </div>
                        
                        <div id="upload-progress" style="display: none;">
                            <h3>Processing Files...</h3>
                            <div id="progress-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    // Handle file upload modal interactions
    $(document).on('click', '#drop-zone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.click();
        }
    });

    $(document).on('change', '#file-input', function() {
        handleFileSelection(this.files);
    });

    $(document).on('dragover', '#drop-zone', function(e) {
        e.preventDefault();
        $(this).css('background', '#f0f8f4');
    });

    $(document).on('dragleave', '#drop-zone', function(e) {
        e.preventDefault();
        $(this).css('background', '#f8fbf9');
    });

    $(document).on('drop', '#drop-zone', function(e) {
        e.preventDefault();
        $(this).css('background', '#f8fbf9');
        handleFileSelection(e.originalEvent.dataTransfer.files);
    });

    $(document).on('click', '#cancel-upload', function() {
        $('#file-upload-modal').remove();
    });

    $(document).on('click', '#start-upload', function() {
        startFileUpload();
    });

    function handleFileSelection(files) {
        const fileList = $('#file-list');
        const startBtn = $('#start-upload');
        
        fileList.empty();
        
        if (files.length === 0) {
            startBtn.prop('disabled', true);
            return;
        }

        const allowedTypes = ['txt', 'json', 'csv', 'pdf', 'doc', 'docx', 'md'];
        const maxSize = 10 * 1024 * 1024; // 10MB
        let validFiles = [];

        Array.from(files).forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            const isValidType = allowedTypes.includes(ext);
            const isValidSize = file.size <= maxSize;
            
            const status = isValidType && isValidSize ? 'valid' : 'invalid';
            const statusText = !isValidType ? 'Unsupported file type' : !isValidSize ? 'File too large (max 10MB)' : 'Ready to upload';
            const statusColor = status === 'valid' ? '#457d58' : '#dc3545';
            
            fileList.append(`
                <div class="file-item" data-valid="${status === 'valid'}">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;">
                        <span style="font-weight: bold;">${file.name}</span>
                        <span style="color: ${statusColor}; font-size: 12px;">${statusText}</span>
                    </div>
                </div>
            `);
            
            if (status === 'valid') {
                validFiles.push(file);
            }
        });

        startBtn.prop('disabled', validFiles.length === 0);
        window.selectedFiles = validFiles;
    }

    function startFileUpload() {
        const scope = $('#kb-scope').val();
        const files = window.selectedFiles || [];
        
        if (files.length === 0) {
            showAdminNotice('No valid files selected.', 'error');
            return;
        }

        // Show progress section
        $('#upload-section').hide();
        $('#upload-progress').show();
        
        const progressList = $('#progress-list');
        progressList.empty();

        // Add progress items for each file
        files.forEach((file, index) => {
            progressList.append(`
                <div id="progress-${index}" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>${file.name}</span>
                        <span class="status">Preparing...</span>
                    </div>
                    <div style="width: 100%; background: #f0f0f0; border-radius: 4px; margin-top: 5px;">
                        <div class="progress-bar" style="width: 0%; background: #457d58; height: 20px; border-radius: 4px; transition: width 0.5s;"></div>
                    </div>
                    <div class="progress-stages" style="font-size: 12px; color: #666; margin-top: 5px;">
                        <span class="stage-upload">📤 Upload</span> → 
                        <span class="stage-process">⚙️ Process</span> → 
                        <span class="stage-index">📚 Index</span>
                    </div>
                </div>
            `);
        });

        // Upload files one by one
        uploadFilesSequentially(files, scope, 0);
    }

    function uploadFilesSequentially(files, scope, index) {
        if (index >= files.length) {
            // All files uploaded
            setTimeout(() => {
                $('#file-upload-modal').remove();
                showAdminNotice(`Successfully uploaded ${files.length} file(s) to the knowledge base!`, 'success');
                location.reload(); // Refresh the page to show new entries
            }, 1000);
            return;
        }

        const file = files[index];
        const formData = new FormData();
        formData.append('action', 'aiohm_kb_file_upload');
        formData.append('nonce', nonce); // Use the existing nonce
        formData.append('scope', scope);
        formData.append('files', file);

        const progressItem = $(`#progress-${index}`);
        let uploadComplete = false;
        
        // Estimate processing time based on file size (rough estimate)
        const fileSizeMB = file.size / (1024 * 1024);
        const estimatedProcessingTime = Math.max(2000, fileSizeMB * 1000); // At least 2 seconds, +1 second per MB
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: Math.max(30000, fileSizeMB * 10000), // At least 30 seconds, +10 seconds per MB
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const uploadPercent = (e.loaded / e.total) * 30; // Upload is only 30% of total process
                        progressItem.find('.progress-bar').css('width', uploadPercent + '%');
                        progressItem.find('.status').text('Uploading... ' + Math.round(uploadPercent) + '%');
                        progressItem.find('.stage-upload').css('font-weight', 'bold').css('color', '#457d58');
                    }
                });
                xhr.upload.addEventListener('load', function() {
                    uploadComplete = true;
                    progressItem.find('.progress-bar').css('width', '30%');
                    progressItem.find('.status').text('Processing file...');
                    progressItem.find('.stage-upload').css('color', '#28a745');
                    progressItem.find('.stage-process').css('font-weight', 'bold').css('color', '#457d58');
                    
                    // Simulate processing progress
                    let processProgress = 30;
                    const progressInterval = setInterval(() => {
                        processProgress += 5;
                        if (processProgress <= 85) {
                            progressItem.find('.progress-bar').css('width', processProgress + '%');
                            progressItem.find('.status').text('Processing file... ' + Math.round(processProgress) + '%');
                        }
                    }, estimatedProcessingTime / 15); // Spread over estimated time
                    
                    // Clear interval when response arrives
                    progressItem.data('progressInterval', progressInterval);
                });
                return xhr;
            },
            success: function(response) {
                // Clear progress interval
                const progressInterval = progressItem.data('progressInterval');
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                
                if (response.success) {
                    // Final indexing stage
                    progressItem.find('.status').text('Indexing in knowledge base...');
                    progressItem.find('.stage-process').css('color', '#28a745');
                    progressItem.find('.stage-index').css('font-weight', 'bold').css('color', '#457d58');
                    progressItem.find('.progress-bar').css('width', '90%');
                    
                    // Complete after short delay
                    setTimeout(() => {
                        progressItem.find('.status').text('✓ Successfully added to knowledge base').css('color', '#457d58');
                        progressItem.find('.progress-bar').css('width', '100%');
                        progressItem.find('.stage-index').css('color', '#28a745');
                        
                        // Upload next file
                        setTimeout(() => uploadFilesSequentially(files, scope, index + 1), 500);
                    }, 1000);
                } else {
                    progressItem.find('.status').text('✗ Failed: ' + (response.data.message || 'Unknown error')).css('color', '#dc3545');
                    progressItem.find('.progress-bar').css('background', '#dc3545').css('width', '100%');
                    progressItem.find('.progress-stages').hide();
                    
                    // Upload next file
                    setTimeout(() => uploadFilesSequentially(files, scope, index + 1), 500);
                }
            },
            error: function(xhr, status, error) {
                // Clear progress interval
                const progressInterval = progressItem.data('progressInterval');
                if (progressInterval) {
                    clearInterval(progressInterval);
                }
                
                progressItem.find('.status').text('✗ Upload failed: ' + status).css('color', '#dc3545');
                progressItem.find('.progress-bar').css('background', '#dc3545').css('width', '100%');
                progressItem.find('.progress-stages').hide();
                
                // Upload next file anyway
                setTimeout(() => uploadFilesSequentially(files, scope, index + 1), 500);
            }
        });
    }

    // Close modal on backdrop click or close button
    $(document).on('click', '.aiohm-modal-backdrop, .aiohm-modal-close', function() {
        $('#file-upload-modal').remove();
    });
});
</script>

<?php
// Include the footer for consistent branding
include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/footer.php';
?>