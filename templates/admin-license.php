<?php
/**
 * Admin License page template.
 * This file displays license status and options for AIOHM Tribe membership.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Variables passed from AIOHM_KB_Settings_Page::render_license_page() are available:
// $settings, $personal_api_key, $is_user_linked

// --- Start: Handle POST request for Tribe Registration ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aiohm_register'])) {
    // This includes the functionality from the license-form.php which seems intended for registration.
    $name  = sanitize_text_field($_POST['aiohm_name'] ?? '');
    $email = sanitize_email($_POST['aiohm_email'] ?? '');
    
    // Assuming aiohm_register_tribe_member() is the intended function to call.
    // This function should handle the API call to aiohm.app.
    if (function_exists('aiohm_register_tribe_member')) {
        $result = aiohm_register_tribe_member($name, $email);

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>🎉 Successfully registered and connected to aiohm.app! Please refresh to see your updated status.</p></div>';
            // After successful registration, we should update the state
            $is_user_linked = true; 
        }
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error: Registration function is not available.</p></div>';
    }
}
// --- End: Handle POST request ---


// --- Start: Fetch Membership Details ---
$plan_data = [];
$arm_user_id = function_exists('aiohm_get_user_arm_id') ? aiohm_get_user_arm_id() : null;

if (!empty($arm_user_id) && class_exists('AIOHM_API')) {
    $api = new AIOHM_API();
    $response = $api->request('arm_member_memberships', ['arm_user_id' => $arm_user_id]);

    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['data']) && is_array($body['data'])) {
            foreach ($body['data'] as $plan) {
                $plan_data[] = [
                    'name' => $plan['plan_title'] ?? 'Unnamed Plan',
                    'status' => $plan['status'] ?? 'Active',
                    'expires' => $plan['expire_date'] ?? 'N/A'
                ];
            }
        }
    }
}
// --- End: Fetch Membership Details ---


// --- Start: Determine ARMember Status ---
$is_armember_active = class_exists('ARMemberLite'); // Check if local ARMember is active
$aiohm_app_arm_user_id = $settings['aiohm_app_arm_user_id'] ?? '';
$user_armember_access_level = 'basic'; // Default
$current_user_id = get_current_user_id();

if ($current_user_id) {
    $user_profile = get_user_meta($current_user_id, 'aiohm_knowledge_profile', true);
    if (is_array($user_profile) && isset($user_profile['access_level'])) {
        $user_armember_access_level = $user_profile['access_level'];
    }
}
// --- End: Determine ARMember Status ---
?>

<div class="wrap">
    <h1><?php _e('AIOHM License & Tribe Membership', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Manage your plugin license status and connect with your AIOHM Tribe account for exclusive features.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-admin-notice" style="display:none; margin-top: 10px;"></div>

    <?php // Display the registration form if the user is not yet linked. ?>
    <?php if (!$is_user_linked) : ?>
    <div class="aiohm-settings-section" style="margin-top: 20px;">
        <h2>Activate Your Free AIOHM Tribe Membership</h2>
        <p>Join the tribe to connect your plugin with aiohm.app and unlock personal AI features.</p>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="aiohm_name">Your Name</label></th>
                    <td><input type="text" id="aiohm_name" name="aiohm_name" class="regular-text" placeholder="Your Name" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aiohm_email">Your Email</label></th>
                    <td><input type="email" id="aiohm_email" name="aiohm_email" class="regular-text" placeholder="Your Email" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="aiohm_register" class="button button-primary" value="Join the Tribe" />
            </p>
        </form>
    </div>
    <?php endif; ?>

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
                                <p class="description error-message"><?php _e('Your plugin is not yet linked to an aiohm.app account. Use the form above to register or go to settings to enter your key.', 'aiohm-kb-assistant'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary">
                                    <span class="dashicons dashicons-admin-settings"></span> <?php _e('Go to Settings', 'aiohm-kb-assistant'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="aiohm-license-column">
            <div class="aiohm-settings-section">
                <h2><?php _e('On-Site Membership Sync', 'aiohm-kb-assistant'); ?></h2>
                <?php if ($is_armember_active || ($is_user_linked && !empty($aiohm_app_arm_user_id))) : ?>
                    <p><?php 
                        if ($is_armember_active) {
                            _e('This section synchronizes your WordPress user\'s membership status with AIOHM based on your local ARMember plugin installation.', 'aiohm-kb-assistant');
                        } else {
                            _e('This section synchronizes your WordPress user\'s membership status with AIOHM by fetching data from your **aiohm.app ARMember profile**.', 'aiohm-kb-assistant');
                        }
                    ?></p>
                     <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Your Detected Access Level', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <span class="aiohm-access-level-badge aiohm-access-<?php echo esc_attr($user_armember_access_level); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $user_armember_access_level))); ?>
                                </span>
                                <p class="description"><?php _e('This level determines your access to content in the AI chat.', 'aiohm-kb-assistant'); ?></p>
                            </td>
                        </tr>
                        <?php if (!empty($plan_data)): ?>
                        <tr>
                            <th scope="row"><?php _e('Your Memberships', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($plan_data as $p) : ?>
                                        <li><strong><?php echo esc_html($p['name']); ?></strong> — <?php echo esc_html($p['status']); ?> (Expires: <?php echo esc_html($p['expires']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th scope="row"><?php _e('Manual Sync', 'aiohm-kb-assistant'); ?></th>
                            <td>
                                <button type="button" class="button button-primary" id="aiohm-sync-armember-btn">
                                    <span class="dashicons dashicons-update"></span> <?php _e('Sync Membership Now', 'aiohm-kb-assistant'); ?>
                                </button>
                                <p class="description"><?php _e('Click to immediately update your access level.', 'aiohm-kb-assistant'); ?></p>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .aiohm-settings-section {
        background: #fff;
        padding: 1px 20px 20px;
        border: 1px solid #dcdcde;
        border-radius: 4px;
        height: 100%;
        box-sizing: border-box;
    }
    .aiohm-license-columns-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    @media (max-width: 960px) {
        .aiohm-license-columns-wrapper {
            grid-template-columns: 1fr;
        }
    }
    .aiohm-status-connected {
        font-weight: bold;
        color: #28a745;
        padding: 4px 8px;
        background-color: #e6ffe6;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 5px;
    }
    .aiohm-status-not-connected {
        font-weight: bold;
        color: #dc3545;
        padding: 4px 8px;
        background-color: #fff5f5;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 5px;
    }
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
    .success-message { color: #28a745; }
    .error-message { color: #dc3545; }
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

        function showLicenseAdminNotice(message, type = 'success') {
            clearTimeout(noticeTimer);
            const $noticeDiv = $('.aiohm-admin-notice');
            $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice notice-' + type).addClass('is-dismissible');
            $noticeDiv.find('p').html(message);
            $noticeDiv.fadeIn();
            
            // Auto-dismiss
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
                    setTimeout(() => {
                         location.reload(); 
                    }, 800);
                } else {
                    showLicenseAdminNotice(response.data.message, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            }).fail(function() {
                showLicenseAdminNotice('An unexpected server error occurred during ARMember sync.', 'error');
                $btn.prop('disabled', false).html(originalText);
            });
        });
    });
</script>