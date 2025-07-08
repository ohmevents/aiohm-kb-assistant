<?php
/**
 * Admin License page template - Final version with corrected logo and dynamic content.
 */

if (!defined('ABSPATH')) exit;

// --- Start: Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
$user_email = $settings['aiohm_app_email'] ?? '';
$is_user_linked = !empty($user_email);
$has_club_access = false;
$membership_details = null;
$display_name = null;

if ($is_user_linked && class_exists('AIOHM_KB_PMP_Integration')) {
    $has_club_access = AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();
    $membership_details = AIOHM_KB_PMP_Integration::get_user_membership_details();
    $display_name = AIOHM_KB_PMP_Integration::get_user_display_name();
}
// --- End: Data Fetching and Status Checks ---
?>
<div class="wrap aiohm-license-page">
    <h1><?php _e('AIOHM Membership & Features', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Connect your account to see the features available with your membership tier.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-feature-grid">

        <div class="aiohm-feature-box <?php echo $is_user_linked ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png'); ?>" alt="AIOHM Logo" class="ohm-logo-icon"></div>
            <h3><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($is_user_linked) : ?>
                <h4 class="plan-price"><?php _e('Welcome to the Tribe!', 'aiohm-kb-assistant'); ?></h4>
                <div class="membership-info">
                    <p><strong>Name:</strong> <?php echo esc_html($display_name ?? 'N/A'); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($user_email); ?></p>
                </div>
                <div class="plan-description"><p><?php _e('As a Tribe member, you can now use the core features of the AIOHM Assistant, including the Brand Soul questionnaire and knowledge base management.', 'aiohm-kb-assistant'); ?></p></div>
                <a href="https://www.aiohm.app/members/" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('View Your Tribe Profile', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('This free tier is where brand resonance begins.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p><?php _e('Root into your why. Begin with deep reflection and intentional alignment. Access your personal Brand Soul Map through our guided questionnaire and shape your AI with the truths that matter most to you.', 'aiohm-kb-assistant'); ?></p></div>
                <a href="https://www.aiohm.app/tribe" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php _e('Join AIOHM Tribe', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>

        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">🔗</div>
                <h3><?php echo esc_html($display_name ?? 'Account Connected'); ?></h3>
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
                <p><?php _e('Enter your AIOHM Email below to link your site and unlock personal features.', 'aiohm-kb-assistant'); ?></p>
                <div class="aiohm-connect-form-wrapper">
                    <form method="post" action="options.php">
                        <?php settings_fields('aiohm_kb_settings'); ?>
                        <input type="email" name="aiohm_kb_settings[aiohm_app_email]" placeholder="Enter Your AIOHM Email" required>
                        <?php 
                        foreach ($settings as $key => $value) { 
                            if ($key !== 'aiohm_app_email') { 
                                echo '<input type="hidden" name="aiohm_kb_settings[' . esc_attr($key) . ']" value="' . esc_attr(is_array($value) ? json_encode($value) : $value) . '">'; 
                            } 
                        } 
                        ?>
                        <button type="submit" class="button button-secondary"><?php _e('Verify & Connect', 'aiohm-kb-assistant'); ?></button>
                    </form>
                </div>
             <?php endif; ?>
        </div>

        <div class="aiohm-feature-box <?php echo $has_club_access ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png'); ?>" alt="AIOHM Logo" class="ohm-logo-icon"></div>
            <h3><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_club_access && $membership_details) : ?>
                <h4 class="plan-price"><?php _e('You have unlocked Club features!', 'aiohm-kb-assistant'); ?></h4>
                <div class="membership-info">
                    <p><strong>Level:</strong> <?php echo esc_html($membership_details['level_name']); ?></p>
                    <p><strong>Started:</strong> <?php echo esc_html($membership_details['start_date']); ?></p>
                    <p><strong>Expires:</strong> <?php echo esc_html($membership_details['end_date']); ?></p>
                </div>
                <a href="https://www.aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php _e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php _e('1 euro per month for first 1000 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="plan-description"><p>Club members gain exclusive access to Mirror Mode for Q&A chat-bot and Muse Mode for brand idea-rich, emotionally attuned content.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php _e('Join AIOHM Club', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    :root {
        --ohm-primary: #457d58; --ohm-dark: #272727; --ohm-light-accent: #cbddd1; --ohm-light-bg: #EBEBEB; --ohm-dark-accent: #1f5014; --ohm-font-primary: 'Montserrat', sans-serif; --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    .aiohm-license-page .aiohm-feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .aiohm-license-page .aiohm-feature-box { background: #fff; border: 1px solid var(--ohm-light-bg); border-radius: 8px; padding: 25px; display: flex; flex-direction: column; text-align: center; }
    .aiohm-license-page .aiohm-feature-box.plan-active { border-top: 4px solid var(--ohm-primary); }
    .aiohm-license-page .box-icon { font-size: 2.5em; line-height: 1; margin-bottom: 15px; height: 40px; color: var(--ohm-primary);}
    .aiohm-license-page .box-icon .ohm-logo-icon { max-height: 100%; width: auto; }
    .aiohm-license-page .aiohm-feature-box h3 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); margin-top: 0; font-size: 1.3em; }
    .aiohm-license-page .aiohm-feature-box p { font-family: var(--ohm-font-secondary); color: var(--ohm-dark); font-size: 1em; line-height: 1.6; margin-bottom: 15px; }
    .aiohm-license-page .plan-description { flex-grow: 1; }
    .aiohm-license-page .button-primary { background-color: var(--ohm-primary); border-color: var(--ohm-dark-accent); color: #fff; font-family: var(--ohm-font-primary); font-weight: bold; }
    .aiohm-connect-form-wrapper input[type="email"] { width: 100%; padding: 8px; margin-bottom: 10px; }
    .aiohm-connect-form-wrapper .button { width: 100%; text-align: center; justify-content: center;}
    .membership-info { margin: 15px 0; padding: 10px; background: #f9f9f9; border: 1px solid var(--ohm-light-bg); border-radius: 4px; text-align: left; flex-grow: 1; }
    .membership-info p { margin: 5px 0; flex-grow: 0; }
    .aiohm-disconnect-form { margin-top: auto; }
</style>