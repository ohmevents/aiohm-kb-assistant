<?php
/**
 * Admin License page template.
 * This file displays license status and options for AIOHM Tribe membership.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables are passed from render_license_page in AIOHM_KB_Settings_Page
// $settings, $personal_api_key, $is_user_linked, $is_armember_active are available.

// Retrieve the new aiohm_app_arm_user_id setting
$aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';

// Get user's current stored access level (this will be from local ARMember or aiohm.app API after sync)
$current_user_id = get_current_user_id();
$user_armember_access_level = 'basic'; // Default
if ($current_user_id) {
    $user_profile = get_user_meta($current_user_id, 'aiohm_knowledge_profile', true);
    $user_armember_access_level = $user_profile['access_level'] ?? 'basic';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aiohm_register'])) {
    include_once plugin_dir_path(__FILE__) . '../includes/api-client-app.php';

    $name  = sanitize_text_field($_POST['aiohm_name'] ?? '');
    $email = sanitize_email($_POST['aiohm_email'] ?? '');

    $result = aiohm_register_tribe_member($name, $email);

    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>🎉 Successfully registered and connected to aiohm.app!</p></div>';
    }
}


?>



<div class="wrap">
    <h1><?php _e('AIOHM License & Tribe Membership', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Manage your plugin license status and connect with your AIOHM Tribe account for exclusive features.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-admin-notice" style="display:none; margin-top: 10px;"></div>

    <div class="aiohm-license-columns-wrapper">

        <div class="aiohm-license-column">
            <div class="aiohm-settings-section">
                <h2><?php _e('AIOHM.app Account Connection', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Connect your plugin to your aiohm.app account for personal AI features like the Brand Soul questionnaire.', 'aiohm-kb-assistant'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Personal API Key Status', 'aiohm-kb-assistant'); ?></th>
                        <td>
                            <?php if ($is_user_linked) : ?>
                                <span class="aiohm-status-connected"><?php _e('Connected', 'aiohm-kb-assistant'); ?></span>
                                <p class="description success-message"><?php _e('Your plugin is successfully linked to your aiohm.app account.', 'aiohm-kb-assistant'); ?></p>
                                <h3 style="margin-top: 20px;"><?php _e('AI Brand Soul Features', 'aiohm-kb-assistant'); ?></h3>
                                <p class="description"><?php _e('You are connected to AIOHM Tribe! Access your personalized AI Brand Soul questionnaire and other exclusive tools here:', 'aiohm-kb-assistant'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=aiohm-dashboard&tab=tribe'); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-star-filled"></span> <?php _e('Go to AI Brand Soul', 'aiohm-kb-assistant'); ?>
                                </a>
                            <?php else : ?>
                                <span class="aiohm-status-not-connected"><?php _e('Not Connected', 'aiohm-kb-assistant'); ?></span>
                                <p class="description error-message"><?php _e('Your plugin is not yet linked to an aiohm.app account. Connect to unlock personal AI features.', 'aiohm-kb-assistant'); ?></p>
                                <a href="https://www.aiohm.app/register" target="_blank" class="button button-primary" style="margin-bottom: 10px;">
                                    <span class="dashicons dashicons-external"></span> <?php _e('Register for Free (aiohm.app)', 'aiohm-kb-assistant'); ?>
                                </a>
                                <p class="description" style="margin-top: 15px;"><?php _e('Already a member? Paste your Personal API Key into the plugin settings.', 'aiohm-kb-assistant'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary">
                                    <span class="dashicons dashicons-admin-settings"></span> <?php _e('Go to Settings', 'aiohm-kb-assistant'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div><div class="aiohm-license-column">
            <div class="aiohm-settings-section">
                <h2><?php _e('On-Site Membership Sync (via ARMember)', 'aiohm-kb-assistant'); ?></h2>
                <?php if ($is_armember_active) : ?>
                    <p><?php _e('This section synchronizes your WordPress user\'s membership status with AIOHM based on your local ARMember plugin installation.', 'aiohm-kb-assistant'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Your Detected Access Level', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <span class="aiohm-access-level-badge aiohm-access-<?php echo esc_attr($user_armember_access_level); ?>">
                                    <?php echo esc_html(ucfirst($user_armember_access_level)); ?>
                                </span>
                                <p class="description"><?php _e('This level determines your access to content in the AI chat if you use content access restrictions.', 'aiohm-kb-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Manual Sync', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <button type="button" class="button button-primary" id="aiohm-sync-armember-btn">
                                    <span class="dashicons dashicons-update"></span> <?php _e('Sync Membership Now', 'aiohm-kb-assistant'); ?>
                                </button>
                                <p class="description"><?php _e('Click to immediately update your access level based on your ARMember subscriptions on *this site*.', 'aiohm-kb-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                <?php elseif ($is_user_linked && !empty($aiohm_app_arm_user_id)) : ?>
                    <p><?php _e('This section synchronizes your WordPress user\'s membership status with AIOHM by fetching data from your **aiohm.app ARMember profile**.', 'aiohm-kb-assistant'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Your Detected Access Level', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <span class="aiohm-access-level-badge aiohm-access-<?php echo esc_attr($user_armember_access_level); ?>">
                                    <?php echo esc_html(ucfirst($user_armember_access_level)); ?>
                                </span>
                                <p class="description"><?php _e('This level is fetched from your aiohm.app ARMember profile and determines your access to content.', 'aiohm-kb-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Manual Sync', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <button type="button" class="button button-primary" id="aiohm-sync-armember-btn">
                                    <span class="dashicons dashicons-update"></span> <?php _e('Sync Membership Now', 'aiohm-kb-assistant'); ?>
                                </button>
                                <p class="description"><?php _e('Click to immediately update your access level from your aiohm.app ARMember profile.', 'aiohm-kb-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                <?php else : ?>
                    <p class="error-message">
                        <?php _e('To enable membership synchronization:', 'aiohm-kb-assistant'); ?><br>
                        1. <?php _e('If your ARMember is installed on *this WordPress site*, please install and activate the ARMember plugin.', 'aiohm-kb-assistant'); ?><br>
                        2. <?php _e('If your ARMember Tribe membership is managed on *aiohm.app*, go to plugin **Settings** and enter both your **Personal AIOHM.app API Key** and your **AIOHM.app ARMember User ID**.', 'aiohm-kb-assistant'); ?>
                    </p>
                    <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary" style="margin-right: 10px;">
                        <span class="dashicons dashicons-admin-settings"></span> <?php _e('Go to Settings', 'aiohm-kb-assistant'); ?>
                    </a>
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-plugins"></span> <?php _e('Go to Plugins', 'aiohm-kb-assistant'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div></div></div><style>
    /* Shared settings section style (from admin-settings.php) */
    .aiohm-settings-section {
        background: #fff;
        padding: 1px 20px 20px;
        border: 1px solid #dcdcde;
        border-radius: 4px;
        height: 100%; /* Ensure columns are equal height */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }

    /* Column Wrapper */
    .aiohm-license-columns-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Two equal columns */
        gap: 20px; /* Space between columns */
        margin-top: 20px;
    }

    /* Responsive adjustment for columns */
    @media (max-width: 960px) {
        .aiohm-license-columns-wrapper {
            grid-template-columns: 1fr; /* Stack columns on smaller screens */
        }
    }

    /* Status Indicators */
    .aiohm-status-connected {
        font-weight: bold;
        color: #28a745; /* Green */
        padding: 4px 8px;
        background-color: #e6ffe6;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 5px;
    }

    .aiohm-status-not-connected {
        font-weight: bold;
        color: #dc3545; /* Red */
        padding: 4px 8px;
        background-color: #fff5f5;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 5px;
    }

    /* Access Level Badges (Similar to content type badges) */
    .aiohm-access-level-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 5px;
    }
    .aiohm-access-basic { background-color: #e0e0e0; color: #424242; }
    .aiohm-access-premium { background-color: #fff3e0; color: #ff9800; }
    .aiohm-access-premium_plus { background-color: #e3f2fd; color: #2196f3; }


    /* Button Styling */
    .aiohm-license-column .button-hero {
        font-size: 1.1em;
        padding: 10px 20px;
        height: auto;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        justify-content: center; /* Center content within the button */
        text-align: center; /* Ensure text alignment */
    }

    /* Specific messages */
    .success-message { color: #28a745; }
    .error-message { color: #dc3545; }

    /* For spinner on button */
    .dashicons.spin {
        animation: aiohm-spin 1s infinite linear;
    }
    @keyframes aiohm-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';
        let noticeTimer;

        // Function to display admin notices on this page
        function showLicenseAdminNotice(message, type = 'success') {
            clearTimeout(noticeTimer);
            const $noticeDiv = $('.aiohm-admin-notice'); // Target the specific notice div on this page
            if ($noticeDiv.length === 0) {
                // If the div doesn't exist (e.g., if we were to move this function), create it
                $('<div class="aiohm-admin-notice notice is-dismissible" style="margin-top: 10px;"><p></p></div>').insertBefore('.aiohm-license-columns-wrapper');
                $noticeDiv = $('.aiohm-admin-notice');
            }
            $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type);
            $noticeDiv.find('p').html(message);
            $noticeDiv.fadeIn();
            $noticeDiv.on('click', '.notice-dismiss', function() {
                $noticeDiv.fadeOut();
            });
            noticeTimer = setTimeout(() => $noticeDiv.fadeOut(), 5000);
        }

        // Handle ARMember Sync Button Click
        $('#aiohm-sync-armember-btn').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e('Syncing...', 'aiohm-kb-assistant'); ?>');
            
            $.post(ajaxurl, {
                action: 'aiohm_sync_current_armember_user',
                nonce: nonce
            }).done(function(response) {
                if (response.success) {
                    showLicenseAdminNotice(response.data.message, 'success');
                    // Reload the page to update the PHP-rendered access level
                    setTimeout(() => {
                         location.reload(); 
                    }, 500); // Small delay to show success message
                } else {
                    showLicenseAdminNotice(response.data.message, 'error');
                }
            }).fail(function() {
                showLicenseAdminNotice('An unexpected server error occurred during ARMember sync.', 'error');
            }).always(function() {
                $btn.prop('disabled', false).html(originalText);
            });
        });
    });
</script>