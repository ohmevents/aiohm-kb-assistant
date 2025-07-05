<?php
/**
 * Admin settings template.
 * This version is complete and verified to be stable.
 */
if (!defined('ABSPATH')) exit;

// Set default values for all settings to avoid errors on first load
$settings = wp_parse_args($settings, [
    'personal_api_key' => '',
    'openai_api_key' => '',
    'system_prompt' => 'You are a helpful AI assistant.',
    'scan_schedule' => 'none',
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
                        <p class="description"><?php _e('Get this key from your account dashboard on aiohm.app after joining the Tribe.', 'aiohm-kb-assistant'); ?></p>
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
                        <p class="description" id="aiohm-api-status"></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aiohm-settings-section">
            <h2><?php _e('Assistant Personality', 'aiohm-kb-assistant'); ?></h2>
            <p><?php _e('Give your AI its unique voice and instructions.', 'aiohm-kb-assistant'); ?></p>
             <table class="form-table">
                <tr>
                    <th scope="row"><label for="system_prompt"><?php _e('Custom Instructions', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <textarea id="system_prompt" name="aiohm_kb_settings[system_prompt]" rows="8" class="large-text"><?php echo esc_textarea($settings['system_prompt']); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aiohm-settings-section">
            <h2><?php _e('Knowledge Base Automation', 'aiohm-kb-assistant'); ?></h2>
            <table class="form-table">
                 <tr>
                    <th scope="row"><label for="scan_schedule"><?php _e('Automatic Scan Schedule', 'aiohm-kb-assistant'); ?></label></th>
                    <td>
                        <select id="scan_schedule" name="aiohm_kb_settings[scan_schedule]">
                            <option value="none" <?php selected($settings['scan_schedule'], 'none'); ?>><?php _e('None (Manual Only)', 'aiohm-kb-assistant'); ?></option>
                            <option value="daily" <?php selected($settings['scan_schedule'], 'daily'); ?>><?php _e('Once a day', 'aiohm-kb-assistant'); ?></option>
                            <option value="weekly" <?php selected($settings['scan_schedule'], 'weekly'); ?>><?php _e('Once a week', 'aiohm-kb-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Automatically scan your website content to keep the knowledge base updated.', 'aiohm-kb-assistant'); ?></p>
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