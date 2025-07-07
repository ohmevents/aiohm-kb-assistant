<?php
/**
 * Admin License page template - Final version
 * This version fixes the AIOHM Private box display logic.
 */

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
$has_private_plan = false;

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

    <div class="aiohm-feature-grid aiohm-feature-grid-3-col">

        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">👤</div>
                <h3><?php echo $username ? esc_html($username) : __('Profile Connected', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Your site is now beautifully synced... Here, you will find your membership details and personal AI features.', 'aiohm-kb-assistant'); ?></p>
                <div class="aiohm-profile-status">
                    <strong><?php _e('Status:', 'aiohm-kb-assistant'); ?></strong> <span class="status-connected"><?php _e('Connected', 'aiohm-kb-assistant'); ?></span><br>
                    <strong><?php _e('ARMember User ID:', 'aiohm-kb-assistant'); ?></strong> <span class="aiohm-user-id"><?php echo esc_html($personal_api_key); ?></span>
                </div>
             <?php else : ?>
                <div class="box-icon">🔑</div>
                <h3><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Please go to Settings and connect your account to see your membership status.', 'aiohm-kb-assistant'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-primary" style="margin-top: auto;"><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></a>
             <?php endif; ?>
        </div>
        
        <div class="aiohm-feature-box <?php echo $has_tribe_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">✨</div>
            <h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_tribe_plan) : ?>
                <p><?php _e('You have access to the foundational tier for personal brand alignment.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <p><?php _e('The foundational tier for personal brand alignment. Get access to the AI Brand Soul questionnaire.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/register" target="_blank" class="button button-primary" style="margin-top: auto;"><?php _e('Join the Tribe', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>

        <div class="aiohm-feature-box <?php echo $has_club_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">🚀</div>
            <h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
             <?php if ($has_club_plan) : ?>
                <p><?php _e('You have access to the Club tier. Use the Brand Assistant in your dashboard.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('1 euro per month for first 100 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p>Where clarity meets magic.<br>Club membership unlocks two transformative tools: Mirror way and Muse way.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">🔓 <?php _e('Join the Club', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>

    </div><div class="aiohm-private-wrapper">
        <section class="aiohm-private-sales">
          <div class="container">
            <h1 class="headline">AIOHM PRIVATE</h1>
            <p class="intro">VPS - Private Artificial Intelligence for Organic Harmonic Marketing</p>
            <div class="cta">
              <a href="https://www.aiohm.app/private" target="_blank" class="button button-primary">Register for Private</a>
            </div>
          </div>
        </section>
    </div>

</div>

<style>
    /* Styles for the 3-box grid */
    .aiohm-license-page .aiohm-feature-grid-3-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .aiohm-license-page .aiohm-feature-box { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 25px; display: flex; flex-direction: column; text-align: center; }
    .aiohm-license-page .aiohm-feature-box.plan-active { border-top: 4px solid var(--ohm-primary, #4CAF50); }
    .aiohm-license-page .aiohm-feature-box.plan-inactive { border-top: 4px solid #e0e0e0; opacity: 0.85; }
    .aiohm-license-page .box-icon { font-size: 2.5em; line-height: 1; margin-bottom: 15px; height: 40px; }
    .aiohm-license-page .aiohm-feature-box h3 { margin-top: 0; font-size: 1.3em; }
    .aiohm-license-page .plan-price { font-size: 1em; color: #1d2327; font-weight: bold; margin: -10px 0 15px 0; }
    .aiohm-license-page .aiohm-feature-box .plan-description, .aiohm-license-page .aiohm-feature-box > p { flex-grow: 1; font-size: 1em; line-height: 1.6; color: #555; margin-bottom: 15px; text-align: left; }
    .aiohm-license-page .aiohm-profile-status { text-align: left; background: #f8f9fa; padding: 15px; border-radius: 4px; line-height: 1.7; margin-bottom: 15px; }
    
    /* Styles for the AIOHM Private full-width section */
    .aiohm-private-wrapper { margin-top: 40px; }
    .aiohm-private-sales { padding: 40px 0; background: #fff; color: #2c2c2c; border: 1px solid #ddd; border-radius: 8px; }
    .aiohm-private-sales .container { max-width: 900px; margin: 0 auto; text-align: center; }
    .aiohm-private-sales .headline { font-size: 32px; font-weight: bold; margin-top: 0; margin-bottom: 15px; font-family: 'Montserrat', sans-serif; }
    .aiohm-private-sales .intro { font-size: 18px; max-width: 700px; margin: 0 auto 30px auto; color: #555; }
    .aiohm-private-sales .cta .button { font-size: 18px; padding: 12px 30px; height: auto; }
</style>