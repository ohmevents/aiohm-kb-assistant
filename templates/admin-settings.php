<?php
/**
 * Admin settings template - Final version.
 */
if (!defined('ABSPATH')) exit;

// Set default values for all settings to avoid errors on first load
$settings = wp_parse_args(AIOHM_KB_Assistant::get_settings(), [
    'aiohm_personal_bot_id' => '',
    'openai_api_key' => '',
    'gemini_api_key' => '',
    'claude_api_key' => '',
    'chat_enabled' => true,
    'show_floating_chat' => false,
    'scan_schedule' => 'none',
]);
?>
<div class="wrap aiohm-settings-page">
    <h1><?php _e('AIOHM Settings', 'aiohm-kb-assistant'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('aiohm_kb_settings'); ?>
        
        <div class="aiohm-settings-section">
            <h2><?php _e('API Keys & Service Connections', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('Connect your assistant to the required AI services and your AIOHM.app account.', 'aiohm-kb-assistant'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="aiohm_personal_bot_id"><?php _e('AIOHM Personal Bot ID', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="aiohm_personal_bot_id" name="aiohm_kb_settings[aiohm_personal_bot_id]" 
                                   value="<?php echo esc_attr($settings['aiohm_personal_bot_id']); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="aiohm_personal_bot_id">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="button button-secondary aiohm-check-key" data-type="aiohm_bot_id">
                                <?php _e('Check Connection', 'aiohm-kb-assistant'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                        <p class="description" id="aiohm_bot_id-api-status"><?php _e("Your unique Bot ID from aiohm.app. This links your plugin to your user profile.", 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="openai_api_key"><?php _e('OpenAI API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="openai_api_key" name="aiohm_kb_settings[openai_api_key]" 
                                   value="<?php echo esc_attr($settings['openai_api_key']); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="openai_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="button button-secondary aiohm-check-key" data-type="openai">
                                <?php _e('Check API Key', 'aiohm-kb-assistant'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                        <p class="description" id="openai-api-status"><?php _e("Required for core AI functionality like embeddings and chat.", 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gemini_api_key"><?php _e('Gemini API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="gemini_api_key" name="aiohm_kb_settings[gemini_api_key]" 
                                   value="<?php echo esc_attr($settings['gemini_api_key']); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="gemini_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                             <button type="button" class="button button-secondary aiohm-check-key" data-type="gemini">
                                <?php _e('Check API Key', 'aiohm-kb-assistant'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                        <p class="description" id="gemini-api-status"><?php _e("Optional. Add your Google Gemini API key to enable Gemini models.", 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
                 <tr>
                    <th scope="row"><label for="claude_api_key"><?php _e('Claude API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <div class="aiohm-api-key-wrapper">
                            <input type="password" id="claude_api_key" name="aiohm_kb_settings[claude_api_key]" 
                                   value="<?php echo esc_attr($settings['claude_api_key']); ?>" class="regular-text">
                            <button type="button" class="button button-secondary aiohm-show-hide-key" data-target="claude_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                             <button type="button" class="button button-secondary aiohm-check-key" data-type="claude">
                                <?php _e('Check API Key', 'aiohm-kb-assistant'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                        <p class="description" id="claude-api-status"><?php _e("Optional. Add your Anthropic Claude API key to enable Claude models.", 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aiohm-settings-section">
            <h2><?php _e('Chat Assistant Settings', 'aiohm-kb-assistant'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Chat Assistant', 'aiohm-kb-assistant'); ?></th>
                    <td>
                        <label for="chat_enabled">
                            <input type="checkbox" id="chat_enabled" name="aiohm_kb_settings[chat_enabled]" value="1" <?php checked(1, $settings['chat_enabled']); ?> />
                            <?php _e('Enable the public-facing chat assistant on your website.', 'aiohm-kb-assistant'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Enable Floating Chat Widget', 'aiohm-kb-assistant'); ?></th>
                    <td>
                        <label for="show_floating_chat">
                            <input type="checkbox" id="show_floating_chat" name="aiohm_kb_settings[show_floating_chat]" value="1" <?php checked(1, $settings['show_floating_chat']); ?> />
                            <?php _e('Display a floating chat widget on all pages.', 'aiohm-kb-assistant'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>
<style>
.aiohm-settings-section { background: #fff; padding: 1px 20px 10px; border: 1px solid #dcdcde; margin-bottom: 20px; }
.aiohm-api-key-wrapper { display: flex; gap: 5px; align-items: center; }
.description.success { color: #28a745; }
.description.error { color: #dc3545; }
</style>
<script>
jQuery(document).ready(function($){
    // Generic hide/show button handler
    $('.aiohm-show-hide-key').on('click', function(){
        const $input = $('#' + $(this).data('target'));
        const type = $input.attr('type');
        $input.attr('type', type === 'password' ? 'text' : 'password');
    });

    // Generic API Key Check handler
    $('.aiohm-check-key').on('click', function(){
        const $btn = $(this);
        const keyType = $btn.data('type');
        const $input = $btn.siblings('input[type="password"], input[type="text"]');
        const apiKey = $input.val();
        const $spinner = $btn.siblings('.spinner');
        const $status = $('#' + keyType + '-api-status');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.text('Checking...').removeClass('success error');

        $.post(ajaxurl, {
            action: 'aiohm_check_api_key',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>',
            api_key: apiKey,
            key_type: keyType
        }).done(function(response){
            $status.text(response.data.message).addClass(response.success ? 'success' : 'error');
        }).fail(function(){
            $status.text('An unknown error occurred.').addClass('error');
        }).always(function(){
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
});
</script>