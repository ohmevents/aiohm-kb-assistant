<?php
/**
 * Admin License page template - Redesigned for improved user experience.
 * This version fetches and displays the user's avatar, username, and data-bot-id from aiohm.app for verification.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// --- Start: Data Fetching and Status Checks ---

$settings = AIOHM_KB_Assistant::get_settings();
$personal_api_key = $settings['aiohm_personal_bot_id'] ?? ''; // This should be the ARMember User ID
$is_user_linked = !empty($personal_api_key);

$plans = [];
$user_profile_info = null;
$username = null;
$avatar_url = null;
$data_bot_id_from_api = null;
$debug_api_response = null;

if ($is_user_linked && class_exists('AIOHM_App_API_Client')) {
    $api_client = new AIOHM_App_API_Client();
    
    // Fetch memberships
    $memberships_response = $api_client->get_member_memberships($personal_api_key);
    if (!is_wp_error($memberships_response) && !empty($memberships_response['data'])) {
        foreach ($memberships_response['data'] as $plan) {
            $plans[] = ['name' => $plan['plan_title'] ?? 'Unnamed Plan'];
        }
    }
    
    // Fetch profile details including username, avatar, and data-bot-id for verification
    $profile_response = $api_client->get_member_details($personal_api_key, ['user_login', 'avatar', 'data-bot-id']);
    $debug_api_response = $profile_response; // Store for debugging

    if (!is_wp_error($profile_response) && !empty($profile_response['data'])) {
        $user_profile_info = $profile_response['data'];

        $username = $user_profile_info['user_login'] ?? null;
        $avatar_url = $user_profile_info['avatar'] ?? null;
        $data_bot_id_from_api = $user_profile_info['data-bot-id'] ?? null;
    }
}

function aiohm_license_page_has_plan($slug, $plans_array) {
    foreach ($plans_array as $plan) {
        if (isset($plan['name']) && stripos($plan['name'], $slug) !== false) {
            return true;
        }
    }
    return false;
}

$has_tribe_plan = aiohm_license_page_has_plan('Tribe', $plans);
$has_club_plan = aiohm_license_page_has_plan('Club', $plans);
$has_private_plan = aiohm_license_page_has_plan('Private', $plans);

// --- End: Data Fetching and Status Checks ---
?>

<div class="wrap aiohm-license-page">
    <h1><?php _e('AIOHM Membership & Features', 'aiohm-kb-assistant'); ?></h1>

    <?php
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Your ID has been saved and your account is connected!', 'aiohm-kb-assistant') . '</p></div>';
    }

    // Display API errors if they exist
    if (is_wp_error($debug_api_response)) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>API Error:</strong> ' . esc_html($debug_api_response->get_error_message()) . '</p><p>Please ensure you are using the correct ARMember User ID from aiohm.app.</p></div>';
    }
    ?>

    <p class="description"><?php _e('Connect your account and see the features available with your membership tier.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-feature-grid">

        <div class="aiohm-feature-box">
            <?php if (!$is_user_linked) : ?>
                <div class="box-icon">🔑</div>
                <h3><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Log into your aiohm.app account to find your **ARMember User ID**. Paste it below to connect your site.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app" target="_blank" class="button button-secondary"><?php _e('Login to aiohm.app', 'aiohm-kb-assistant'); ?></a>
                <form method="post" action="options.php" class="aiohm-connect-form">
                    <?php settings_fields('aiohm_kb_settings'); ?>
                    <label for="aiohm_personal_bot_id"><?php _e('Paste Your ARMember User ID', 'aiohm-kb-assistant'); ?></label>
                    <input type="text" id="aiohm_personal_bot_id" name="aiohm_kb_settings[aiohm_personal_bot_id]" value="" placeholder="<?php _e('Paste your ARMember User ID here', 'aiohm-kb-assistant'); ?>" class="large-text">
                    <p class="description" style="text-align: left;"><?php _e('This ID links your WordPress site to your aiohm.app user profile.', 'aiohm-kb-assistant'); ?></p>
                    <input type="hidden" name="aiohm_kb_settings[openai_api_key]" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>">
                    <input type="hidden" name="aiohm_kb_settings[gemini_api_key]" value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>">
                    <input type="hidden" name="aiohm_kb_settings[claude_api_key]" value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>">
                    <?php submit_button(__('Connect', 'aiohm-kb-assistant')); ?>
                </form>
            <?php else : ?>
                <div class="box-icon">
                    <?php if ($avatar_url) : ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" class="aiohm-profile-avatar" alt="User Avatar">
                    <?php else : ?>
                        👤
                    <?php endif; ?>
                </div>
                <h3>
                    <?php 
                    if ($username) { echo esc_html($username); } 
                    else { _e('Your Profile', 'aiohm-kb-assistant'); }
                    ?>
                </h3>
                <p><?php _e('Your site is successfully connected to AIOHM.app. Your access levels are shown below.', 'aiohm-kb-assistant'); ?></p>
                <div class="aiohm-profile-status">
                    <strong><?php _e('Status:', 'aiohm-kb-assistant'); ?></strong> <span class="status-connected"><?php _e('Connected', 'aiohm-kb-assistant'); ?></span><br>
                    <strong><?php _e('ARMember User ID:', 'aiohm-kb-assistant'); ?></strong> <span class="aiohm-user-id"><?php echo esc_html($personal_api_key); ?></span><br>
                    <?php if ($data_bot_id_from_api) : ?>
                        <strong><?php _e('Bot ID (from API):', 'aiohm-kb-assistant'); ?></strong> <span class="aiohm-user-id"><?php echo esc_html($data_bot_id_from_api); ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary"><?php _e('Manage API Keys', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>
        
        <div class="aiohm-feature-box <?php echo $has_tribe_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">✨</div><h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('The foundational tier for personal brand alignment. Get access to the AI Brand Soul questionnaire to define your unique voice.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app" target="_blank" class="button button-secondary"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
        </div>
        <div class="aiohm-feature-box <?php echo $has_club_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">🚀</div><h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Unlock the AI Brand Assistant. Use Muse Mode to generate content, captions, and emails that resonate with your brand voice.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app" target="_blank" class="button button-secondary"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
        </div>
        <div class="aiohm-feature-box <?php echo $has_private_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">💎</div><h3><?php _e('AIOHM Private', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('The ultimate tier for sovereignty. Connect to private, decentralized Large Language Models for maximum privacy and control.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app" target="_blank" class="button button-secondary"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
        </div>
    </div>
</div>

<style>
    /* Styles remain the same */
    .aiohm-license-page .aiohm-feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .aiohm-license-page .aiohm-feature-box { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 25px; display: flex; flex-direction: column; text-align: center; }
    .aiohm-license-page .aiohm-feature-box.plan-active { border-top: 4px solid #4CAF50; }
    .aiohm-license-page .aiohm-feature-box.plan-inactive { border-top: 4px solid #e0e0e0; opacity: 0.85; }
    .aiohm-license-page .box-icon { font-size: 2.5em; line-height: 1; margin-bottom: 15px; height: 40px; }
    .aiohm-license-page .aiohm-profile-avatar { width: 40px; height: 40px; border-radius: 50%; margin: 0 auto; display: block; object-fit: cover; }
    .aiohm-license-page .aiohm-feature-box h3 { margin-top: 0; font-size: 1.3em; }
    .aiohm-license-page .aiohm-feature-box p { flex-grow: 1; font-size: 1em; line-height: 1.6; color: #555; }
    .aiohm-license-page .aiohm-feature-box .button { margin-top: 15px; width: 100%; }
    .aiohm-license-page .aiohm-connect-form { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
    .aiohm-license-page .aiohm-connect-form label { font-weight: bold; display: block; margin-bottom: 5px; text-align: left; }
    .aiohm-license-page .aiohm-profile-status { text-align: left; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #e0e0e0; line-height: 1.7; margin-bottom: 15px; }
    .aiohm-license-page .status-connected { color: #28a745; font-weight: bold; }
    .aiohm-license-page .aiohm-user-id { font-family: monospace; font-size: 0.9em; background: #e0e0e0; padding: 2px 4px; border-radius: 3px; }
</style>