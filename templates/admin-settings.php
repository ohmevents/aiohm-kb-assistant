<?php
/**
 * Admin settings template - Final branded version.
 */
if (!defined('ABSPATH')) exit;

// --- Start: Data Fetching and Status Checks ---
$settings = wp_parse_args(AIOHM_KB_Assistant::get_settings(), []);
$can_access_settings = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();
// --- End: Data Fetching and Status Checks ---
?>

<div class="wrap aiohm-settings-page">
    <h1><?php _e('AIOHM Settings', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Configure API keys, AI assistants, and content scanning schedules.', 'aiohm-kb-assistant'); ?></p>
    
    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <form method="post" action="options.php">
        <?php settings_fields('aiohm_kb_settings'); ?>

        <div class="aiohm-settings-section">
            <h2><?php _e('API Keys & Service Connections', 'aiohm-kb-assistant'); ?></h2>
            <table class="form-table">
                 <tr>
                    <th scope="row"><label for="default_ai_provider"><?php _e('Default AI Provider', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <select id="default_ai_provider" name="aiohm_kb_settings[default_ai_provider]">
                            <option value="openai" <?php selected($settings['default_ai_provider'] ?? 'openai', 'openai'); ?>>OpenAI</option>
                            <option value="gemini" <?php selected($settings['default_ai_provider'] ?? '', 'gemini'); ?>>Gemini</option>
                        </select>
                        <p class="description"><?php _e('Select the default AI provider to use for generating responses.', 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="openai_api_key"><?php _e('OpenAI API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="openai_api_key" name="aiohm_kb_settings[openai_api_key]" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="openai_api_key"><span class="dashicons dashicons-visibility"></span></button>
                            <button type="button" class="button button-secondary aiohm-test-api-key" data-target="openai_api_key" data-type="openai"><?php _e('Test API', 'aiohm-kb-assistant'); ?></button>
                        </div>
                        <p class="description"><?php printf(__('You can get your OpenAI API key from the <a href="%s" target="_blank">OpenAI API keys page</a>.', 'aiohm-kb-assistant'), 'https://platform.openai.com/account/api-keys'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gemini_api_key"><?php _e('Gemini API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="gemini_api_key" name="aiohm_kb_settings[gemini_api_key]" value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="gemini_api_key"><span class="dashicons dashicons-visibility"></span></button>
                            <button type="button" class="button button-secondary aiohm-test-api-key" data-target="gemini_api_key" data-type="gemini"><?php _e('Test API', 'aiohm-kb-assistant'); ?></button>
                        </div>
                        <p class="description"><?php printf(__('You can get your Gemini API key from the <a href="%s" target="_blank">Google AI Studio</a>.', 'aiohm-kb-assistant'), 'https://aistudio.google.com/app/apikey'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="claude_api_key"><?php _e('Claude API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="claude_api_key" name="aiohm_kb_settings[claude_api_key]" value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="claude_api_key"><span class="dashicons dashicons-visibility"></span></button>
                            <button type="button" class="button button-secondary aiohm-test-api-key" data-target="claude_api_key" data-type="claude"><?php _e('Test API', 'aiohm-kb-assistant'); ?></button>
                        </div>
                        <p class="description"><?php printf(__('You can get your Claude API key from your <a href="%s" target="_blank">Anthropic Account Settings</a>.', 'aiohm-kb-assistant'), 'https://console.anthropic.com/account/keys'); ?></p>
                    </td>
                </tr>
                 <tr>
                    <th scope="row"><label for="private_llm_api_key"><?php _e('Private LLM API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="private_llm_api_key" name="aiohm_kb_settings[private_llm_api_key]" value="" class="regular-text" disabled>
                        </div>
                        <p class="description"><?php _e('This is reserved for future use by AIOHM Private members.', 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aiohm-premium-settings-wrapper <?php if (!$can_access_settings) echo 'is-locked'; ?>">
            <?php if (!$can_access_settings) : ?>
                <div class="aiohm-settings-locked-overlay">
                    <div class="lock-content">
                        <div class="lock-icon">🔒</div>
                        <h2><?php _e('Unlock Advanced Settings', 'aiohm-kb-assistant'); ?></h2>
                        <p><?php _e('These settings require an AIOHM Club or Private membership to configure. Please ensure your AIOHM App Email is configured correctly on the License page, and you have an active Club/Private membership.', 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-license&tab=club')); ?>" class="button button-primary"><?php _e('Explore Memberships', 'aiohm-kb-assistant'); ?></a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="aiohm-settings-section">
                <h2><?php _e('Q&A Chatbot Settings (Public)', 'aiohm-kb-assistant'); ?></h2>
                <table class="form-table">
                    <tr><th scope="row"><?php _e('Enable Q&A Chatbot', 'aiohm-kb-assistant'); ?></th>
                        <td><label><input type="checkbox" name="aiohm_kb_settings[chat_enabled]" value="1" <?php checked($settings['chat_enabled'] ?? false); disabled(!$can_access_settings); ?> /> <?php _e('Enable the `[aiohm_chat]` shortcode.', 'aiohm-kb-assistant'); ?></label></td></tr>
                    <tr><th scope="row"><?php _e('Enable Search Shortcode', 'aiohm-kb-assistant'); ?></th>
                        <td><label><input type="checkbox" name="aiohm_kb_settings[enable_search_shortcode]" value="1" <?php checked($settings['enable_search_shortcode'] ?? false); disabled(!$can_access_settings); ?> /> <?php _e('Enable the `[aiohm_search]` shortcode.', 'aiohm-kb-assistant'); ?></label></td></tr>
                </table>
            </div>

            <div class="aiohm-settings-section">
                <h2><?php _e('Private Brand Assistant (Admin-Only)', 'aiohm-kb-assistant'); ?></h2>
                <table class="form-table">
                    <tr><th scope="row"><?php _e('Enable Private Assistant', 'aiohm-kb-assistant'); ?></th>
                        <td><label><input type="checkbox" name="aiohm_kb_settings[enable_private_assistant]" value="1" <?php checked($settings['enable_private_assistant'] ?? false); disabled(!$can_access_settings); ?> /> <?php _e('Enable the `[aiohm_private_assistant]` shortcode.', 'aiohm-kb-assistant'); ?></label></td></tr>
                </table>
            </div>
            
            <div class="aiohm-settings-section">
                <h2><?php _e('Scheduled Content Scan', 'aiohm-kb-assistant'); ?></h2>
                <table class="form-table">
                    <tr><th scope="row"><label for="scan_schedule"><?php _e('Scan Frequency', 'aiohm-kb-assistant'); ?></label></th>
                        <td><select id="scan_schedule" name="aiohm_kb_settings[scan_schedule]" <?php disabled(!$can_access_settings); ?>><option value="none" <?php selected($settings['scan_schedule'] ?? 'none', 'none'); ?>>None</option><option value="daily" <?php selected($settings['scan_schedule'], 'daily'); ?>>Once Daily</option><option value="weekly" <?php selected($settings['scan_schedule'], 'weekly'); ?>>Once Weekly</option><option value="monthly" <?php selected($settings['scan_schedule'], 'monthly'); ?>>Once Monthly</option></select></td></tr>
                </table>
            </div>
        </div>
        
        <?php submit_button('Save All Settings'); ?>
    </form>
</div>

<style>
    :root {
        --ohm-primary: #457d58; --ohm-dark: #272727; --ohm-light-accent: #cbddd1; --ohm-light-bg: #EBEBEB; --ohm-dark-accent: #1f5014; --ohm-font-primary: 'Montserrat', sans-serif; --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    .aiohm-settings-page h1, .aiohm-settings-page h2 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); }
    .aiohm-settings-page .page-description, .aiohm-settings-page p.description, .aiohm-settings-page th, .aiohm-settings-page label { font-family: var(--ohm-font-secondary); color: var(--ohm-dark); }
    .aiohm-settings-page .page-description { font-size: 1.1em; padding-bottom: 1em; border-bottom: 1px solid var(--ohm-light-bg); }
    .aiohm-settings-section { background: #fff; padding: 1px 20px 20px; border: 1px solid var(--ohm-light-bg); margin-top: 20px; border-radius: 4px; }
    .aiohm-settings-page .button-primary { background: var(--ohm-primary) !important; border-color: var(--ohm-dark-accent) !important; font-family: var(--ohm-font-primary); font-weight: bold; }
    .aiohm-settings-page .button-primary:hover { background: var(--ohm-dark-accent) !important; }
    .aiohm-api-key-wrapper { display: flex; gap: 5px; align-items: center; }
    .aiohm-premium-settings-wrapper { position: relative; }
    .aiohm-premium-settings-wrapper.is-locked .aiohm-settings-section,
    .aiohm-premium-settings-wrapper.is-locked .submit { 
        opacity: 0.90; 
        pointer-events: none; 
    }
    .aiohm-settings-locked-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(235, 235, 235, 0.6); z-index: 10; display: flex; align-items: center; justify-content: center; padding: 20px; text-align: center; border-radius: 4px; }
    .aiohm-settings-locked-overlay .lock-content { background: #ffffff; padding: 40px; border-radius: 8px; border: 1px solid var(--ohm-light-accent); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .aiohm-settings-locked-overlay .lock-icon { font-size: 3em; color: var(--ohm-primary); margin-bottom: 15px; }
</style>

<script>
jQuery(document).ready(function($){
    let noticeTimer;
    
    function showAdminNotice(message, type = 'success') {
        clearTimeout(noticeTimer);
        const $notice = $('#aiohm-admin-notice');
        $notice.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type).addClass('is-dismissible');
        $notice.find('p').html(message);
        $notice.fadeIn();
        noticeTimer = setTimeout(() => $notice.fadeOut(), 5000);
    }

    $('.aiohm-show-hide-key').on('click', function(){
        const $input = $('#' + $(this).data('target'));
        const type = $input.attr('type');
        $input.attr('type', type === 'password' ? 'text' : 'password');
    });

    $('.aiohm-test-api-key').on('click', function(){
        const $btn = $(this);
        const targetId = $btn.data('target');
        const keyType = $btn.data('type');
        const apiKey = $('#' + targetId).val();
        const originalText = $btn.text();

        if (!apiKey) {
            showAdminNotice('Please enter an API key before testing.', 'warning');
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin-top:0; vertical-align:middle;"></span>');

        $.post(ajaxurl, {
            action: 'aiohm_check_api_key',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>',
            api_key: apiKey,
            key_type: keyType
        })
        .done(function(response) {
            if (response.success) {
                showAdminNotice(response.data.message, 'success');
            } else {
                showAdminNotice(response.data.message || 'An unknown error occurred.', 'error');
            }
        })
        .fail(function() {
            showAdminNotice('A server error occurred. Please try again.', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });
});
</script>