<?php
/**
 * Admin "Muse Mode" Settings page template for the private assistant.
 */

if (!defined('ABSPATH')) exit;

// Fetch all settings and then get the specific part for Muse Mode
$all_settings = AIOHM_KB_Assistant::get_settings();
$settings = $all_settings['muse_mode'] ?? [];
$global_settings = $all_settings; // for API keys

// Helper function for color contrast
function aiohm_is_color_dark_muse($hex) {
    if (empty($hex)) return false;
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    if (strlen($hex) != 6) return false;
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance < 0.5;
}

$default_prompt = "You are Muse, a private brand assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.";
$system_prompt = !empty($settings['system_prompt']) ? $settings['system_prompt'] : $default_prompt;
?>

<div class="wrap aiohm-settings-page aiohm-muse-mode-page">
    <h1><?php _e('Muse Mode Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Configure the personality and model for your private brand assistant, used by the `[aiohm_private_assistant]` shortcode.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <div class="aiohm-mirror-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="muse-mode-settings-form">
                <?php wp_nonce_field('aiohm_muse_mode_nonce', 'aiohm_muse_mode_nonce_field'); ?>
                
                <div class="aiohm-setting-block">
                    <label for="assistant_name">Assistant Name</label>
                    <input type="text" id="assistant_name" name="aiohm_kb_settings[muse_mode][assistant_name]" value="<?php echo esc_attr($settings['assistant_name'] ?? 'Muse'); ?>">
                    <p class="description">This name will appear in the private chat header.</p>
                </div>

                <div class="aiohm-setting-block">
                    <div class="aiohm-setting-header">
                        <label for="system_prompt"><?php _e('System Prompt for Muse', 'aiohm-kb-assistant'); ?></label>
                        <button type="button" id="reset-prompt-btn" class="button-link"><?php _e('Reset to Default', 'aiohm-kb-assistant'); ?></button>
                    </div>
                    <textarea id="system_prompt" name="aiohm_kb_settings[muse_mode][system_prompt]" rows="15"><?php echo esc_textarea($system_prompt); ?></textarea>
                    <p class="description">The core instructions for your private creative assistant.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="ai_model_selector">AI Model</label>
                    <select id="ai_model_selector" name="aiohm_kb_settings[muse_mode][ai_model]">
                        <?php if (!empty($global_settings['openai_api_key'])): ?>
                            <option value="gpt-4" <?php selected($settings['ai_model'] ?? 'gpt-4', 'gpt-4'); ?>>OpenAI: GPT-4</option>
                            <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'] ?? '', 'gpt-3.5-turbo'); ?>>OpenAI: GPT-3.5 Turbo</option>
                        <?php endif; ?>
                        <?php if (!empty($global_settings['gemini_api_key'])): ?>
                            <option value="gemini-pro" <?php selected($settings['ai_model'] ?? '', 'gemini-pro'); ?>>Google: Gemini Pro</option>
                        <?php endif; ?>
                        <?php if (!empty($global_settings['claude_api_key'])): ?>
                            <option value="claude-3-sonnet" <?php selected($settings['ai_model'] ?? '', 'claude-3-sonnet'); ?>>Anthropic: Claude 3 Sonnet</option>
                        <?php endif; ?>
                    </select>
                     <p class="description">Select the model for your private assistant. We recommend a more powerful model like GPT-4 for creative tasks.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="temperature">Temperature: <span class="temp-value"><?php echo esc_attr($settings['temperature'] ?? '0.7'); ?></span></label>
                    <input type="range" id="temperature" name="aiohm_kb_settings[muse_mode][temperature]" value="<?php echo esc_attr($settings['temperature'] ?? '0.7'); ?>" min="0" max="1" step="0.1">
                    <p class="description">Lower is more predictable; higher is more creative.</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="save-muse-mode-settings" class="button button-primary"><?php _e('Save Muse Settings', 'aiohm-kb-assistant'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiohm-test-column">
             <div id="aiohm-test-chat" class="aiohm-chat-container">
                <div class="aiohm-chat-header">
                    <div class="aiohm-chat-title-preview"><?php echo esc_html($settings['assistant_name'] ?? 'Muse'); ?></div>
                </div>
                <div class="aiohm-chat-messages">
                    <div class="aiohm-message aiohm-message-bot">
                        <div class="aiohm-message-bubble"><div class="aiohm-message-content">This is a preview of your private assistant. The settings on the left will be used by the `[aiohm_private_assistant]` shortcode.</div></div>
                    </div>
                </div>
                <div class="aiohm-chat-input-container">
                    <div class="aiohm-chat-input-wrapper">
                        <textarea class="aiohm-chat-input" placeholder="Test Muse..." rows="1"></textarea>
                        <button type="button" class="aiohm-chat-send-btn" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    .aiohm-muse-mode-page .aiohm-mirror-mode-layout { grid-template-columns: 1fr 1fr; }
    /* Styles are identical to mirror mode page for consistency */
</style>

<script>
    jQuery(document).ready(function($) {
        // This script will handle saving and updating the preview
        $('#temperature').on('input', function() {
            $(this).siblings('.temp-value').text($(this).val());
        });

        $('#save-muse-mode-settings').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'aiohm_save_muse_mode_settings',
                nonce: $('#aiohm_muse_mode_nonce_field').val(),
                form_data: $('#muse-mode-settings-form').serialize()
            }).done(function(response) {
                if(response.success) {
                    $('#aiohm-admin-notice').removeClass('notice-error').addClass('notice-success').find('p').html(response.data.message);
                } else {
                    $('#aiohm-admin-notice').removeClass('notice-success').addClass('notice-error').find('p').html(response.data.message || 'An error occurred.');
                }
                $('#aiohm-admin-notice').fadeIn().delay(3000).fadeOut();
            }).always(function() {
                $btn.prop('disabled', false).text('Save Muse Settings');
            });
        });
    });
</script>