<?php
/**
 * Admin License page template - Final version.
 * This version fixes the disconnect button functionality and style.
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// --- Start: Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
$personal_api_key = $settings['aiohm_personal_bot_id'] ?? '';
$is_user_linked = !empty($personal_api_key);
$user_plans_details = []; $username = null; $has_tribe_plan = false; $has_club_plan = false; $has_private_plan = false;
if ($is_user_linked && class_exists('AIOHM_App_API_Client')) {
    $api_client = new AIOHM_App_API_Client();
    $profile_response = $api_client->get_member_details($personal_api_key);
    if (!is_wp_error($profile_response) && isset($profile_response['response']['result'])) {
        $user_profile_info = $profile_response['response']['result'];
        $username = $user_profile_info['display_name'] ?? ($user_profile_info['username'] ?? null);
    }
    $memberships_response = $api_client->get_member_memberships($personal_api_key);
    if (!is_wp_error($memberships_response) && !empty($memberships_response['response']['result']['memberships'])) {
        $user_plans_details = $memberships_response['response']['result']['memberships'];
        foreach ($user_plans_details as $plan) {
            if (isset($plan['name'])) {
                if (stripos($plan['name'], 'Tribe') !== false) { $has_tribe_plan = true; }
                if (stripos($plan['name'], 'Club') !== false) { $has_club_plan = true; }
                if (stripos($plan['name'], 'Private') !== false) { $has_private_plan = true; }
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
        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">👤</div>
                <h3><?php echo $username ? esc_html($username) : __('Profile Connected', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Your site is now beautifully synced with your AIOHM personal account.', 'aiohm-kb-assistant'); ?></p>
                <form method="post" action="options.php" class="aiohm-disconnect-form">
                    <?php settings_fields('aiohm_kb_settings'); ?>
                    <input type="hidden" name="aiohm_kb_settings[aiohm_personal_bot_id]" value="">
                    <?php foreach ($settings as $key => $value) { if ($key !== 'aiohm_personal_bot_id') { echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">'; } } ?>
                    <button type="submit" class="button button-primary button-disconnect"><?php _e('Disconnect Account', 'aiohm-kb-assistant'); ?></button>
                </form>
             <?php else : ?>
                <div class="box-icon">🔑</div>
                <h3><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Go to Settings to connect your account.', 'aiohm-kb-assistant'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-primary" style="margin-top: auto;"><?php _e('Connect Account', 'aiohm-kb-assistant'); ?></a>
             <?php endif; ?>
        </div>
        <div class="aiohm-feature-box <?php echo $has_tribe_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">✨</div><h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_tribe_plan) : ?>
                <p><?php _e('You have access to the foundational tier for personal brand alignment.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <p><?php _e('The foundational tier for personal brand alignment. Get access to the AI Brand Soul questionnaire.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/register" target="_blank" class="button button-primary" style="margin-top: auto;"><?php _e('Join the Tribe', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>
        <div class="aiohm-feature-box <?php echo $has_club_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">🚀</div><h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_club_plan) : ?>
                <p><?php _e('You have access to the Club tier. Use the Brand Assistant in your dashboard.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('1 euro per month for first 100 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p>Where clarity meets magic.<br>Club membership unlocks two transformative tools: Mirror way and Muse way.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">🔓 <?php _e('Join the Club', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>
    </div>
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
    .aiohm-license-page .status-connected { color: var(--ohm-primary); font-weight: bold; }
    .aiohm-disconnect-form { margin-top: auto; }
    .button.button-disconnect {
        width: 100%;
        background: var(--ohm-primary) !important;
        color: #fff !important;
        border-color: var(--ohm-dark-accent) !important;
    }
    .button.button-disconnect:hover {
        background: var(--ohm-dark-accent) !important;
    }
</style>