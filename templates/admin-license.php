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
    <h1><?php esc_html_e('AIOHM Membership & Features', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php esc_html_e('Connect your account to see the features available with your membership tier.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-feature-grid">

        <div class="aiohm-feature-box <?php echo $is_user_linked ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><?php
                echo wp_kses_post(AIOHM_KB_Core_Init::render_image(
                    AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png',
                    esc_attr__('OHM Logo', 'aiohm-kb-assistant'),
                    ['class' => 'ohm-logo-icon']
                ));
            ?></div>
            <h3><?php esc_html_e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($is_user_linked) : ?>
                <h4 class="plan-price"><?php esc_html_e('Welcome to the Tribe!', 'aiohm-kb-assistant'); ?></h4>
                <div class="membership-info">
                    <p><strong>Name:</strong> <?php echo esc_html($display_name ?? 'N/A'); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($user_email); ?></p>
                </div>
                <div class="plan-description"><p><?php esc_html_e('As a Tribe member, you can now use the core features of the AIOHM Assistant, including the Brand Soul questionnaire and knowledge base management.', 'aiohm-kb-assistant'); ?></p></div>
                <a href="https://www.aiohm.app/members/" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php esc_html_e('View Your Tribe Profile', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php esc_html_e('Free - Where brand resonance begins.', 'aiohm-kb-assistant'); ?></h4>
                <div class="tribe-counter-container">
                    <div class="tribe-counter-display">
                        <div class="counter-number" id="tribe-members-count">
                            <span class="loading-dots">•••</span>
                        </div>
                        <div class="counter-label">tribe members</div>
                        <div class="counter-subtext" id="tribe-stats">Loading tribe status...</div>
                    </div>
                </div>
                <div class="plan-description"><p><?php esc_html_e('Access your personal Brand Soul Map through our guided questionnaire and shape your AI with the truths that matter most to you.', 'aiohm-kb-assistant'); ?></p></div>
                <a href="https://www.aiohm.app/register" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php esc_html_e('Join AIOHM Tribe', 'aiohm-kb-assistant'); ?></a>
            <?php endif; ?>
        </div>

        <div class="aiohm-feature-box">
             <?php if ($is_user_linked) : ?>
                <div class="box-icon">🔗</div>
                <h3><?php echo esc_html($display_name ?? 'Account Connected'); ?></h3>
                <p><?php 
                    // translators: %s is the user's email address
                    printf(esc_html__('Your site is linked via the email: %s', 'aiohm-kb-assistant'), '<strong>' . esc_html($user_email) . '</strong>'); ?></p>
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
                    <button type="submit" class="button button-primary button-disconnect"><?php esc_html_e('Disconnect Account', 'aiohm-kb-assistant'); ?></button>
                </form>
             <?php else : ?>
                <div class="box-icon">🔌</div>
                <h3><?php esc_html_e('Connect Your Account', 'aiohm-kb-assistant'); ?></h3>
                <p><?php esc_html_e('Enter your AIOHM Email below to verify your membership and connect your account.', 'aiohm-kb-assistant'); ?></p>
                <div class="aiohm-connect-form-wrapper">
                    <div id="aiohm-verification-step-1">
                        <input type="email" id="aiohm-verification-email" placeholder="Enter Your AIOHM Email" required>
                        <button type="button" id="aiohm-send-code-btn" class="button button-secondary"><?php esc_html_e('Send Verification Code', 'aiohm-kb-assistant'); ?></button>
                    </div>
                    <div id="aiohm-verification-step-2" style="display: none;">
                        <p class="aiohm-verification-message">We've sent a verification code to your email. Please enter it below:</p>
                        <input type="text" id="aiohm-verification-code" placeholder="Enter 6-digit code" maxlength="6" required>
                        <div class="aiohm-verification-actions">
                            <button type="button" id="aiohm-verify-code-btn" class="button button-primary"><?php esc_html_e('Verify & Connect', 'aiohm-kb-assistant'); ?></button>
                            <button type="button" id="aiohm-resend-code-btn" class="button button-link"><?php esc_html_e('Resend Code', 'aiohm-kb-assistant'); ?></button>
                        </div>
                    </div>
                    <div id="aiohm-verification-status" class="aiohm-status-message" style="display: none;"></div>
                </div>
             <?php endif; ?>
        </div>

        <div class="aiohm-feature-box <?php echo $has_club_access ? 'plan-active' : 'plan-inactive'; ?>">
            <div class="box-icon"><?php
                echo wp_kses_post(AIOHM_KB_Core_Init::render_image(
                    AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png',
                    esc_attr__('AIOHM Logo', 'aiohm-kb-assistant'),
                    ['class' => 'ohm-logo-icon']
                ));
            ?></div>
            <h3><?php esc_html_e('AIOHM Club', 'aiohm-kb-assistant'); ?></h3>
            <?php if ($has_club_access && $membership_details) : ?>
                <h4 class="plan-price"><?php esc_html_e('You have unlocked Club features!', 'aiohm-kb-assistant'); ?></h4>
                <div class="membership-info">
                    <p><strong>Level:</strong> <?php echo esc_html($membership_details['level_name']); ?></p>
                    <p><strong>Started:</strong> <?php echo esc_html($membership_details['start_date']); ?></p>
                    <p><strong>Expires:</strong> <?php echo esc_html($membership_details['end_date']); ?></p>
                </div>
                <a href="https://www.aiohm.app/club" target="_blank" class="button button-secondary" style="margin-top: auto;"><?php esc_html_e('Manage Membership', 'aiohm-kb-assistant'); ?></a>
            <?php else: ?>
                <h4 class="plan-price"><?php esc_html_e('1€/month or 10€/year for first 1000 members.', 'aiohm-kb-assistant'); ?></h4>
                <div class="club-countdown-container">
                    <div class="club-countdown-display">
                        <div class="countdown-number" id="remaining-spots">
                            <span class="loading-dots">•••</span>
                        </div>
                        <div class="countdown-label">spots remaining</div>
                        <div class="countdown-progress">
                            <div class="progress-bar" id="progress-bar"></div>
                        </div>
                        <div class="countdown-subtext" id="club-stats">Loading club status...</div>
                    </div>
                </div>
                <div class="plan-description"><p>Club members gain exclusive access to Mirror Mode for Q&A chat-bot and Muse Mode for brand idea-rich, emotionally attuned content.</p></div>
                <a href="https://www.aiohm.app/club/" target="_blank" class="button button-primary" style="margin-top: auto;">→ <?php esc_html_e('Join AIOHM Club', 'aiohm-kb-assistant'); ?></a>
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
    .aiohm-connect-form-wrapper input[type="email"], .aiohm-connect-form-wrapper input[type="text"] { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid var(--ohm-light-bg); border-radius: 4px; }
    .aiohm-connect-form-wrapper .button { width: 100%; text-align: center; justify-content: center; margin-bottom: 5px; }
    .aiohm-verification-actions { display: flex; gap: 10px; }
    .aiohm-verification-actions .button { flex: 1; }
    .aiohm-verification-message { font-size: 14px; color: var(--ohm-dark); margin: 10px 0; }
    .aiohm-status-message { padding: 10px; border-radius: 4px; margin: 10px 0; }
    .aiohm-status-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .aiohm-status-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .aiohm-status-message.loading { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .membership-info { margin: 15px 0; padding: 10px; background: #f9f9f9; border: 1px solid var(--ohm-light-bg); border-radius: 4px; text-align: left; flex-grow: 1; }
    .membership-info p { margin: 5px 0; flex-grow: 0; }
    .aiohm-disconnect-form { margin-top: auto; }
    
    /* Club Countdown Styles */
    .club-countdown-container { margin: 15px 0; }
    .club-countdown-display { text-align: center; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border: 2px solid var(--ohm-light-accent); }
    .countdown-number { font-size: 3em; font-weight: bold; color: var(--ohm-primary); line-height: 1; margin-bottom: 5px; font-family: var(--ohm-font-primary); }
    .countdown-label { font-size: 0.9em; color: var(--ohm-dark); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; font-family: var(--ohm-font-primary); }
    .countdown-progress { width: 100%; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; margin-bottom: 10px; }
    .progress-bar { height: 100%; background: linear-gradient(90deg, var(--ohm-primary) 0%, var(--ohm-dark-accent) 100%); border-radius: 4px; transition: width 0.5s ease; }
    .countdown-subtext { font-size: 0.8em; color: var(--ohm-dark); opacity: 0.8; }
    .loading-dots { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .countdown-urgent { color: #dc3545 !important; }
    .countdown-warning { color: #fd7e14 !important; }
    
    /* Tribe Counter Styles */
    .tribe-counter-container { margin: 15px 0; }
    .tribe-counter-display { text-align: center; padding: 15px; background: linear-gradient(135deg, #f0f8f0 0%, #e8f5e8 100%); border-radius: 6px; border: 2px solid var(--ohm-light-accent); }
    .counter-number { font-size: 2.5em; font-weight: bold; color: var(--ohm-primary); line-height: 1; margin-bottom: 5px; font-family: var(--ohm-font-primary); }
    .counter-label { font-size: 0.85em; color: var(--ohm-dark); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-family: var(--ohm-font-primary); }
    .counter-subtext { font-size: 0.75em; color: var(--ohm-dark); opacity: 0.8; }
</style>

<script>
jQuery(document).ready(function($) {
    const $step1 = $('#aiohm-verification-step-1');
    const $step2 = $('#aiohm-verification-step-2');
    const $status = $('#aiohm-verification-status');
    const $emailInput = $('#aiohm-verification-email');
    const $codeInput = $('#aiohm-verification-code');
    
    let currentEmail = '';

    function showStatus(message, type) {
        $status.removeClass('success error loading').addClass(type).text(message).show();
    }

    function hideStatus() {
        $status.hide();
    }

    // Send verification code
    $('#aiohm-send-code-btn').on('click', function() {
        const email = $emailInput.val().trim();
        
        if (!email || !email.includes('@')) {
            showStatus('Please enter a valid email address.', 'error');
            return;
        }

        currentEmail = email;
        showStatus('Sending verification code...', 'loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiohm_send_verification_code',
                email: email,
                nonce: '<?php echo esc_js(wp_create_nonce('aiohm_license_verification')); ?>'
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $step1.hide();
                    $step2.show();
                    showStatus(data.message, 'success');
                    $codeInput.focus();
                } else {
                    showStatus(data.error || 'Failed to send verification code.', 'error');
                }
            },
            error: function() {
                showStatus('Network error. Please try again.', 'error');
            }
        });
    });

    // Verify code
    $('#aiohm-verify-code-btn').on('click', function() {
        const code = $codeInput.val().trim();
        
        if (!code) {
            showStatus('Please enter the verification code.', 'error');
            return;
        }

        showStatus('Verifying code...', 'loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiohm_verify_email_code',
                email: currentEmail,
                code: code,
                nonce: '<?php echo esc_js(wp_create_nonce('aiohm_license_verification')); ?>'
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showStatus(data.message, 'success');
                    // Reload page after successful verification
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showStatus(data.error || 'Verification failed.', 'error');
                }
            },
            error: function() {
                showStatus('Network error. Please try again.', 'error');
            }
        });
    });

    // Resend code
    $('#aiohm-resend-code-btn').on('click', function() {
        $('#aiohm-send-code-btn').click();
    });

    // Allow Enter key to trigger actions
    $emailInput.on('keypress', function(e) {
        if (e.which === 13) {
            $('#aiohm-send-code-btn').click();
        }
    });

    $codeInput.on('keypress', function(e) {
        if (e.which === 13) {
            $('#aiohm-verify-code-btn').click();
        }
    });

    // Club countdown functionality
    function loadClubCountdown() {
        $.ajax({
            url: 'https://www.aiohm.app/wp-json/aiohm/v1/get-club-count',
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    updateCountdownDisplay(data);
                } else {
                    showCountdownError();
                }
            },
            error: function() {
                showCountdownError();
            }
        });
    }

    function updateCountdownDisplay(data) {
        const $remainingSpots = $('#remaining-spots');
        const $progressBar = $('#progress-bar');
        const $clubStats = $('#club-stats');
        
        // Update remaining spots number
        $remainingSpots.html(data.remaining_spots.toLocaleString());
        
        // Apply color based on urgency
        if (data.remaining_spots <= 50) {
            $remainingSpots.addClass('countdown-urgent');
        } else if (data.remaining_spots <= 200) {
            $remainingSpots.addClass('countdown-warning');
        }
        
        // Update progress bar
        $progressBar.css('width', data.percentage_filled + '%');
        
        // Update stats text
        const statsText = data.total_members.toLocaleString() + ' of ' + data.max_spots.toLocaleString() + ' spots taken (' + data.percentage_filled + '%)';
        $clubStats.text(statsText);
        
        // Show urgency message if needed
        if (data.remaining_spots <= 50) {
            $clubStats.html(statsText + '<br><strong style="color: #dc3545;">⚡ Almost full! Limited spots remaining</strong>');
        } else if (data.remaining_spots <= 200) {
            $clubStats.html(statsText + '<br><strong style="color: #fd7e14;">🔥 Filling up fast!</strong>');
        }
    }

    function showCountdownError() {
        $('#remaining-spots').html('???');
        $('#club-stats').text('Unable to load current availability');
        $('#progress-bar').css('width', '0%');
    }

    // Load countdown on page load
    loadClubCountdown();
    
    // Refresh countdown every 30 seconds
    setInterval(loadClubCountdown, 30000);

    // Tribe counter functionality
    function loadTribeCounter() {
        $.ajax({
            url: 'https://www.aiohm.app/wp-json/aiohm/v1/get-tribe-count',
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    updateTribeCounterDisplay(data);
                } else {
                    showTribeCounterError();
                }
            },
            error: function() {
                showTribeCounterError();
            }
        });
    }

    function updateTribeCounterDisplay(data) {
        const $tribeCount = $('#tribe-members-count');
        const $tribeStats = $('#tribe-stats');
        
        // Update tribe members count
        $tribeCount.html(data.total_members.toLocaleString());
        
        // Update stats text
        const statsText = 'Growing community of conscious creators';
        $tribeStats.text(statsText);
    }

    function showTribeCounterError() {
        $('#tribe-members-count').html('???');
        $('#tribe-stats').text('Unable to load community stats');
    }

    // Load tribe counter on page load
    loadTribeCounter();
    
    // Refresh tribe counter every 60 seconds
    setInterval(loadTribeCounter, 60000);
});
</script>