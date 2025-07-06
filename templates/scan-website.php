<?php
/**
 * Scan website content template.
 * This version removes the faulty PHP block, fixing the fatal error.
 */
if (!defined('ABSPATH')) exit;

// The data-loading PHP block has been removed from here.
// Variables like $site_stats and $uploads_stats are now passed in from settings-page.php
$api_key_exists = !empty(AIOHM_KB_Assistant::get_settings()['openai_api_key']);
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
                        <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['posts']['total'] ?? 0, $site_stats['posts']['indexed'] ?? 0, $site_stats['posts']['pending'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('Pages:', 'aiohm-kb-assistant'); ?></strong>
                         <span><?php printf(__('%d total, %d indexed, %d pending', 'aiohm-kb-assistant'), $site_stats['pages']['total'] ?? 0, $site_stats['pages']['indexed'] ?? 0, $site_stats['pages']['pending'] ?? 0); ?></span>
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
/* ... same styles as before ... */
</style>

<script type="text/javascript">
// ... same script as before ...
</script>