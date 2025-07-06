<?php
/**
 * Admin settings template - Final version.
 */
if (!defined('ABSPATH')) exit;

// Set default values for all settings to avoid errors on first load
$settings = wp_parse_args(AIOHM_KB_Assistant::get_settings(), [
    'personal_api_key' => '',
    'openai_api_key' => '',
    'chat_enabled' => true, // Added chat_enabled setting
    'show_floating_chat' => false, // Added show_floating_chat setting
]);
?>
<div class="wrap aiohm-settings-page">
    <h1><?php _e('AIOHM Settings', 'aiohm-kb-assistant'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('aiohm_kb_settings'); ?>
        
        <div class="aiohm-settings-section">
            <h2><?php _e('API Keys', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('Connect your assistant to the required services.', 'aiohm-kb-assistant'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="personal_api_key"><?php _e('Personal AIOHM API Key', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <input type="password" id="personal_api_key" name="aiohm_kb_settings[personal_api_key]" value="<?php echo esc_attr($settings['personal_api_key']); ?>" class="regular-text">
                        <p class="description"><?php _e("Enter the key from your aiohm.app account. <strong>This unlocks personal AI features, like the 'Brand Soul' questionnaire.</strong>", 'aiohm-kb-assistant'); ?></p>
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
                            <button type="button" class="button button-secondary" id="aiohm-check-api-key">
                                <?php _e('Check API Key', 'aiohm-kb-assistant'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                        <p class="description" id="aiohm-api-status"><?php _e("<strong>This key is required for your AI to think and generate responses.</strong> It connects your site to the OpenAI language models.", 'aiohm-kb-assistant'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aiohm-settings-section">
            <h2><?php _e('Chat Assistant Settings', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('Configure the behavior of your public-facing AI chat assistant.', 'aiohm-kb-assistant'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Chat Assistant', 'aiohm-kb-assistant'); ?></th>
                    <td>
                        <label for="chat_enabled">
                            <input type="checkbox" id="chat_enabled" name="aiohm_kb_settings[chat_enabled]" value="1" <?php checked(1, $settings['chat_enabled']); ?> />
                            <?php _e('Check this box to enable the public-facing chat assistant on your website.', 'aiohm-kb-assistant'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Enable Floating Chat Widget', 'aiohm-kb-assistant'); ?></th>
                    <td>
                        <label for="show_floating_chat">
                            <input type="checkbox" id="show_floating_chat" name="aiohm_kb_settings[show_floating_chat]" value="1" <?php checked(1, $settings['show_floating_chat']); ?> />
                            <?php _e('Check this box to display a floating chat widget on all pages.', 'aiohm-kb-assistant'); ?>
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
#aiohm-api-status.success { color: #28a745; }
#aiohm-api-status.error { color: #dc3545; }
</style>
<script>
jQuery(document).ready(function($){
    $('.aiohm-show-hide-key').on('click', function(){
        const $input = $('#' + $(this).data('target'));
        const type = $input.attr('type');
        $input.attr('type', type === 'password' ? 'text' : 'password');
    });

    $('#aiohm-check-api-key').on('click', function(){
        const $btn = $(this);
        const $spinner = $btn.siblings('.spinner');
        const $status = $('#aiohm-api-status');
        const apiKey = $('#openai_api_key').val();

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.text('Checking...').removeClass('success error');

        $.post(ajaxurl, {
            action: 'aiohm_check_api_key',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>',
            api_key: apiKey
        }).done(function(response){
            $status.text(response.data.message).addClass(response.success ? 'success' : 'error');
        }).fail(function(){
            $status.text('An unknown error occurred.');
        }).always(function(){
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });
});
</script>