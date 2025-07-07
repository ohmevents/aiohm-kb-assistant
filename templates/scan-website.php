<?php
/**
 * Scan website content template - Branded Version.
 */
if (!defined('ABSPATH')) exit;

// The controller (settings-page.php) prepares these variables
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
                        echo '<p>Supported files include .txt, .json, .csv, and .pdf from your Media Library.</p>';
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
                    <div id="scan-results-container"></div>
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
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-muted-accent: #7d9b76;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', 'Montserrat Alternates', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }

    /* General Typography & Colors */
    #aiohm-scan-page h1,
    #aiohm-scan-page h2,
    #aiohm-scan-page h3,
    #aiohm-scan-page h4 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
    }
    #aiohm-scan-page p,
    #aiohm-scan-page .stat-item,
    #aiohm-scan-page .wp-list-table {
        font-family: var(--ohm-font-secondary);
        color: var(--ohm-dark);
    }
    #aiohm-scan-page .notice-warning h3 {
        color: #8a6d3b; /* Keeping warning text readable */
    }

    /* Buttons */
    #aiohm-scan-page .button-primary {
        background-color: var(--ohm-primary);
        border-color: var(--ohm-dark-accent);
        color: #fff;
        font-family: var(--ohm-font-primary);
    }
    #aiohm-scan-page .button-primary:hover {
        background-color: var(--ohm-dark-accent);
        border-color: var(--ohm-dark-accent);
    }
    #aiohm-scan-page .button-primary:disabled {
        background-color: var(--ohm-muted-accent);
        border-color: var(--ohm-muted-accent);
    }
    
    /* Page Structure & Sections */
    #aiohm-scan-page .aiohm-scan-section { background: #fff; padding: 25px; border: 1px solid var(--ohm-light-bg); border-radius: 4px; height: 100%; }
    #aiohm-scan-page .aiohm-scan-columns-wrapper { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 960px) { #aiohm-scan-page .aiohm-scan-columns-wrapper { grid-template-columns: 1fr; } }
    
    /* Stats Section */
    #aiohm-scan-page .aiohm-stats-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
    #aiohm-scan-page .stat-item { margin-bottom: 8px; padding: 10px; border-left: 3px solid var(--ohm-light-accent); }
    #aiohm-scan-page .stat-item.total-stat { border-left-color: var(--ohm-primary); font-size: 14px; }
    
    /* Progress Bar */
    #aiohm-scan-page .progress-bar-inner { background-color: var(--ohm-primary); }
    #aiohm-scan-page .progress-bar-wrapper { background-color: var(--ohm-light-bg); }

    /* Table Badges & Links */
    #aiohm-scan-page .status-knowledge-base { color: var(--ohm-primary); font-weight: bold; }
    #aiohm-scan-page .status-ready-to-add { color: var(--ohm-muted-accent); }
    #aiohm-scan-page .add-single-item-link { cursor: pointer; text-decoration: underline; color: var(--ohm-primary); }
    #aiohm-scan-page .add-single-item-link:hover { color: var(--ohm-dark-accent); }
    #aiohm-scan-page .aiohm-content-type-badge { background-color: var(--ohm-light-bg); color: var(--ohm-dark); }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';
    let noticeTimer;

    function showAdminNotice(message, type = 'success') { /* ... existing function ... */ }
    function renderItemsTable(items, containerSelector, checkboxName, showStatusColumn, isUploads = false) { /* ... existing function ... */ }

    // --- All existing JavaScript functions from previous turns remain the same ---
    // Initial loads for both tables
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'website_find', nonce: nonce }).done(response => { if (response.success) { renderItemsTable(response.data.items, '#scan-results-container', 'items[]', true); } });
    $.post(ajaxurl, { action: 'aiohm_progressive_scan', scan_type: 'uploads_find', nonce: nonce }).done(response => { if (response.success) { renderItemsTable(response.data.items, '#scan-uploads-container', 'upload_items[]', true, true); } });
    
    // Button click handlers...
    $('#scan-website-btn').on('click', function() { /* ... */ });
    $('#add-selected-to-kb-btn').on('click', function() { /* ... */ });
    $('#scan-uploads-btn').on('click', function() { /* ... */ });
    $('#add-uploads-to-kb-btn').on('click', function() { /* ... */ });
    $('#scan-results-container, #scan-uploads-container').on('click', '.add-single-item-link', function(e) { /* ... */ });
    $(document).on('click', '.aiohm-scan-section thead input:checkbox', function(){ $(this).closest('table').find('tbody input:checkbox:not(:disabled)').prop('checked', this.checked); });
});
</script>