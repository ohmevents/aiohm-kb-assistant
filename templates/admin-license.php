<?php
/**
 * Admin License page template.
 * This file displays license status and options for AIOHM Tribe membership.
 */
 
 // The POST handling section for aiohm_register is removed here as per previous context updates
 // that moved the primary license page logic to a new design, and these POST handlers
 // are not present in the new admin-license.php provided earlier.

// Prevent direct access
if (!defined('ABSPATH')) exit;

// --- Start: Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
$personal_api_key = $settings['aiohm_personal_bot_id'] ?? '';
$is_user_linked = !empty($personal_api_key);
$user_plans_details = [];
$username = null;
$has_tribe_plan = false;
$has_club_plan = false;

// Variables to store API data for display
$api_profile_data = null;
$api_memberships_data = null;

if ($is_user_linked && class_exists('AIOHM_App_API_Client')) {
    $api_client = new AIOHM_App_API_Client();

    // Log profile details API call
    $profile_response = $api_client->get_member_details($personal_api_key);
    if (is_wp_error($profile_response)) {
        AIOHM_KB_Assistant::log('License Page API Error (Profile Details): ' . $profile_response->get_error_message(), 'error');
    } else {
        AIOHM_KB_Assistant::log('License Page API Success (Profile Details) for user ID: ' . $personal_api_key, 'info');
        if (isset($profile_response['response']['result'])) {
            $user_profile_info = $profile_response['response']['result'];
            $username = $user_profile_info['display_name'] ?? ($user_profile_info['username'] ?? null);
            $api_profile_data = $user_profile_info; // Store for display
        }
    }

    // Log memberships API call
    $memberships_response = $api_client->get_member_memberships($personal_api_key);
    if (is_wp_error($memberships_response)) {
        AIOHM_KB_Assistant::log('License Page API Error (Memberships): ' . $memberships_response->get_error_message(), 'error');
    } else {
        AIOHM_KB_Assistant::log('License Page API Success (Memberships) for user ID: ' . $personal_api_key, 'info');
        if (!empty($memberships_response['response']['result']['memberships'])) {
            $user_plans_details = $memberships_response['response']['result']['memberships'];
            $api_memberships_data = $user_plans_details; // Store for display
            foreach ($user_plans_details as $plan) {
                if (isset($plan['name'])) {
                    if (stripos($plan['name'], 'Tribe') !== false) { $has_tribe_plan = true; }
                    if (stripos($plan['name'], 'Club') !== false) { $has_club_plan = true; }
                }
            }
        }
    }
}
// --- End: Data Fetching and Status Checks ---
?>
<div class="wrap aiohm-license-page">
    <h1><?php _e('AIOHM Membership & Features', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Connect your account to see the features available with your membership tier.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-feature-grid">

        <div class="aiohm-feature-box <?php echo $has_tribe_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">
                <img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png'); ?>" alt="OHM Logo" class="ohm-logo-icon">
            </div>
            <h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <h4 class="plan-price"><?php _e('This free tier is where brand resonance begins.', 'aiohm-kb-assistant'); ?></h4>
            <p><?php _e('Root into your why. Begin with deep reflection and intentional alignment. Access your personal Brand Soul Map through our guided questionnaire and shape your AI with the truths that matter most to you.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.aiohm.app/tribe" target="_blank" class="button button-primary" style="margin-top: auto;">箔 <?php _e('Join AIOHM Tribe', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">側</div>
                <h3><?php echo $username ? esc_html($username) : __('Account Connected', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Your site is linked to your AIOHM Tribe profile, unlocking personal features like the AI Brand Soul questionnaire and custom chat experiences.', 'aiohm-kb-assistant'); ?></p>
                <form method="post" action="options.php" class="aiohm-disconnect-form">
                    <?php settings_fields('aiohm_kb_settings'); ?>
                    <input type="hidden" name="aiohm_kb_settings[aiohm_personal_bot_id]" value="">
                    <?php foreach ($settings as $key => $value) { if ($key !== 'aiohm_personal_bot_id') { echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr(is_array($value) ? json_encode($value) : $value) . '">'; } } ?>
                    <button type="submit" class="button button-primary button-disconnect"><?php _e('Disconnect Account', 'aiohm-kb-assistant'); ?></button>
                </form>
             <?php else : ?>
                <div class="box-icon">泊</div>
                <h3><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Enter your AIOHM User ID below to link your site and unlock personal features. You can find this in your AIOHM member profile.', 'aiohm-kb-assistant'); ?></p>

                <div class="aiohm-connect-form-wrapper">
                    <form id="aiohm-check-id-form">
                        <input type="text" id="aiohm_personal_bot_id_check" placeholder="Enter Your AIOHM User ID" required>
                        <button type="submit" id="check-aiohm-id-btn" class="button button-secondary"><?php _e('Verify & Connect', 'aiohm-kb-assistant'); ?></button>
                    </form>
                    <form method="post" action="options.php" id="aiohm-save-id-form" style="display:none;">
                         <?php settings_fields('aiohm_kb_settings'); ?>
                         <input type="hidden" id="aiohm_personal_bot_id_save" name="aiohm_kb_settings[aiohm_personal_bot_id]" value="">
                         <?php foreach ($settings as $key => $value) { if ($key !== 'aiohm_personal_bot_id') { echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr(is_array($value) ? json_encode($value) : $value) . '">'; } } ?>
                         <button type="submit" class="button button-primary"><?php _e('Save and Activate Connection', 'aiohm-kb-assistant'); ?></button>
                    </form>
                </div>
             <?php endif; ?>
        </div>

        <div class="aiohm-feature-box <?php echo $has_club_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png'); ?>" alt="OHM Logo" class="ohm-logo-icon"></div><h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_club_plan) : ?>
                <p><?php _e('You have access to the Club tier. Use the Brand Assistant in your dashboard.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('1 euro per month for first 100 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p>Club members gain exclusive access to Mirror Mode for soul-aligned insights and Muse Mode for idea-rich, emotionally attuned content. This is where your brand’s clarity meets creative flow.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">箔 <?php _e('Join AIOHM Club', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div id="aiohm-connection-status" style="display:none; margin-top: 20px;"></div>

    <?php if ($is_user_linked): ?>
    <div class="aiohm-settings-section" style="margin-top: 20px;">
        <h2><?php _e('API Connection Debug Info (from aiohm.app)', 'aiohm-kb-assistant'); ?></h2>
        <?php if ($api_profile_data && is_array($api_profile_data)): ?>
            <h3><?php _e('User Profile Details:', 'aiohm-kb-assistant'); ?></h3>
            <pre style="background: #f8f8f8; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;"><code><?php echo esc_html(json_encode($api_profile_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
        <?php elseif (is_wp_error($profile_response)): ?>
            <p style="color: #dc3545;"><?php _e('Error fetching profile details:', 'aiohm-kb-assistant'); ?> <?php echo esc_html($profile_response->get_error_message()); ?></p>
        <?php else: ?>
            <p><?php _e('No profile data available or API call failed silently.', 'aiohm-kb-assistant'); ?></p>
        <?php endif; ?>

        <?php if ($api_memberships_data && is_array($api_memberships_data)): ?>
            <h3><?php _e('User Memberships Details:', 'aiohm-kb-assistant'); ?></h3>
            <pre style="background: #f8f8f8; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;"><code><?php echo esc_html(json_encode($api_memberships_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
        <?php elseif (is_wp_error($memberships_response)): ?>
            <p style="color: #dc3545;"><?php _e('Error fetching memberships details:', 'aiohm-kb-assistant'); ?> <?php echo esc_html($memberships_response->get_error_message()); ?></p>
        <?php else: ?>
            <p><?php _e('No memberships data available or API call failed silently.', 'aiohm-kb-assistant'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>


</div>

<style>
    /* OHM Brand Identity */
    :root {
        --ohm-primary: #457d58; --ohm-dark: #272727; --ohm-light-accent: #cbddd1; --ohm-light-bg: #EBEBEB; --ohm-dark-accent: #1f5014; --ohm-font-primary: 'Montserrat', sans-serif; --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    .aiohm-license-page .aiohm-feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .aiohm-license-page .aiohm-feature-box { background: #fff; border: 1px solid var(--ohm-light-bg); border-radius: 8px; padding: 25px; display: flex; flex-direction: column; text-align: center; }
    .aiohm-license-page .aiohm-feature-box.plan-active { border-top: 4px solid var(--ohm-primary); }
    .aiohm-license-page .box-icon { font-size: 2.5em; line-height: 1; margin-bottom: 15px; height: 40px; color: var(--ohm-primary);}
    .aiohm-license-page .aiohm-feature-box h3 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); margin-top: 0; font-size: 1.3em; }
    .aiohm-license-page .aiohm-feature-box p { flex-grow: 1; font-family: var(--ohm-font-secondary); color: var(--ohm-dark); font-size: 1em; line-height: 1.6; margin-bottom: 15px; }
    .aiohm-license-page .button-primary { background-color: var(--ohm-primary); border-color: var(--ohm-dark-accent); color: #fff; font-family: var(--ohm-font-primary); font-weight: bold; }
    .aiohm-license-page .button-primary:hover { background-color: var(--ohm-dark-accent); border-color: var(--ohm-dark-accent); }
    .aiohm-disconnect-form, .aiohm-connect-form-wrapper { margin-top: auto; }
    .aiohm-connect-form-wrapper input[type="text"] { width: 100%; padding: 8px; margin-bottom: 10px; }
    .aiohm-connect-form-wrapper .button { width: 100%; text-align: center; justify-content: center;}
    .button.button-disconnect {
        width: 100%;
        background: var(--ohm-primary) !important;
        color: #fff !important;
        border-color: var(--ohm-dark-accent) !important;
    }
    .button.button-disconnect:hover {
        background: var(--ohm-dark-accent) !important;
    }
    .aiohm-license-page .box-icon .ohm-logo-icon {
        max-height: 100%;
        width: auto;
        display: inline-block;
        vertical-align: middle;
    }
    /* Custom styles for our connection status message */
    .aiohm-status-message {
        padding: 10px 15px;
        border-radius: 4px;
        border-left-width: 4px;
        border-left-style: solid;
    }
    .aiohm-status-message.error {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    .aiohm-status-message.success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>';

    function showConnectionStatus(message, type = 'error') {
        const $statusContainer = $('#aiohm-connection-status');
        const messageClass = (type === 'success') ? 'success' : 'error';
        
        // Create the styled message and inject it into our container
        const messageHtml = `<div class="aiohm-status-message ${messageClass}">${message}</div>`;
        $statusContainer.html(messageHtml).fadeIn();

        // Auto-hide after 5 seconds
        setTimeout(() => $statusContainer.fadeOut(), 5000);
    }

    $('#aiohm-check-id-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#check-aiohm-id-btn');
        const botId = $('#aiohm_personal_bot_id_check').val();
        const originalBtnText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-top: 0; vertical-align: middle;"></span> Verifying...');

        $.post(ajaxurl, {
            action: 'aiohm_check_api_key',
            nonce: nonce,
            api_key: botId,
            key_type: 'aiohm_bot_id'
        }).done(function(response) {
            if (response.success) {
                showConnectionStatus(response.data.message, 'success');
                $('#aiohm_personal_bot_id_save').val(botId);
                $('#aiohm-check-id-form').hide();
                $('#aiohm-save-id-form').show();
            } else {
                showConnectionStatus(response.data.message || 'Verification failed. Please check the ID and try again.', 'error');
            }
        }).fail(function() {
            showConnectionStatus('An unexpected server error occurred.', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html(originalBtnText);
        });
    });
});
</script>