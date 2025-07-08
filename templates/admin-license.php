<?php
/**
 * Admin License page template - Final version.
 * This version uses the new PMPro integration and an email-based connection.
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// --- Start: Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
$user_email = $settings['aiohm_app_email'] ?? '';
$is_user_linked = !empty($user_email);
$has_club_access = false;
$has_tribe_plan = $is_user_linked; // A linked account implies at least Tribe level.

if ($is_user_linked && class_exists('AIOHM_KB_PMP_Integration')) {
    $has_club_access = AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();
}
// --- End: Data Fetching and Status Checks ---
?>
<div class="wrap aiohm-license-page">
    <h1><?php _e('AIOHM Membership & Features', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Connect your account to see the features available with your membership tier.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-connection-status" style="display:none; margin-top: 20px;"></div>

    <div class="aiohm-feature-grid">

        <div class="aiohm-feature-box <?php echo $has_tribe_plan ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon">
                <img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png'); ?>" alt="OHM Logo" class="ohm-logo-icon">
            </div>
            <h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <h4 class="plan-price"><?php _e('This free tier is where brand resonance begins.', 'aiohm-kb-assistant'); ?></h4>
            <p><?php _e('Root into your why. Begin with deep reflection and intentional alignment. Access your personal Brand Soul Map through our guided questionnaire and shape your AI with the truths that matter most to you.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.aiohm.app/tribe" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php _e('Join AIOHM Tribe', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">🔗</div>
                <h3><?php _e('Account Connected', 'aiohm-kb-assistant'); ?></h3>
                <p><?php printf(__('Your site is linked via the email: %s', 'aiohm-kb-assistant'), '<strong>' . esc_html($user_email) . '</strong>'); ?></p>
                <form method="post" action="options.php" class="aiohm-disconnect-form">
                    <?php settings_fields('aiohm_kb_settings'); ?>
                    <input type="hidden" name="aiohm_kb_settings[aiohm_app_email]" value="">
                    <?php 
                    foreach ($settings as $key => $value) { 
                        if ($key !== 'aiohm_app_email') { 
                            echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr(is_array($value) ? json_encode($value) : $value) . '">'; 
                        } 
                    } 
                    ?>
                    <button type="submit" class="button button-primary button-disconnect"><?php _e('Disconnect Account', 'aiohm-kb-assistant'); ?></button>
                </form>
             <?php else : ?>
                <div class="box-icon">🔌</div>
                <h3><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php _e('Enter your AIOHM Email below to link your site and unlock personal features. You can find this in your AIOHM member profile.', 'aiohm-kb-assistant'); ?></p>

                <div class="aiohm-connect-form-wrapper">
                    <form id="aiohm-check-email-form" method="post" action="options.php">
                        <?php settings_fields('aiohm_kb_settings'); ?>
                        <input type="email" id="aiohm_app_email_check" name="aiohm_kb_settings[aiohm_app_email]" placeholder="Enter Your AIOHM Email" required>
                        <?php 
                        foreach ($settings as $key => $value) { 
                            if ($key !== 'aiohm_app_email') { 
                                echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr(is_array($value) ? json_encode($value) : $value) . '">'; 
                            } 
                        } 
                        ?>
                        <button type="submit" id="check-aiohm-email-btn" class="button button-secondary"><?php _e('Verify & Connect', 'aiohm-kb-assistant'); ?></button>
                    </form>
                </div>
             <?php endif; ?>
        </div>

        <div class="aiohm-feature-box <?php echo $has_club_access ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png'); ?>" alt="OHM Logo" class="ohm-logo-icon"></div><h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_club_access) : ?>
                <p><?php _e('You have access to the Club tier. Use the Brand Assistant in your dashboard.', 'aiohm-kb-assistant'); ?></p>
                <a href="https://aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('1 euro per month for first 100 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p>Club members gain exclusive access to Mirror Mode for soul-aligned insights and Muse Mode for idea-rich, emotionally attuned content. This is where your brand’s clarity meets creative flow.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php _e('Join AIOHM Club', 'aiohm-kb-assistant'); ?></a>
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
    .aiohm-disconnect-form, .aiohm-connect-form-wrapper { margin-top: auto; }
    .aiohm-connect-form-wrapper input[type="email"] { width: 100%; padding: 8px; margin-bottom: 10px; }
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
    // This script is no longer strictly necessary if the form submits directly,
    // but it can provide a better user experience by giving feedback without a page reload.
    // For simplicity, this version will use a direct form submission.
    // You can re-add AJAX handling for a smoother UX if desired.
});
</script>