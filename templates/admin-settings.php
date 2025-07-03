<?php
/**
 * Admin settings template for AIOHM Knowledge Assistant
 *
 * @package AIOHM_KB_Assistant
 * @author ohmevents
 * @version 1.0.0
 * @created 2025-07-02 12:31:42
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure settings are available
if (!isset($settings)) {
    wp_die(__('Required settings data is missing.', 'aiohm-kb-assistant'));
}
?>
<div class="wrap">
    <h1><?php _e('AIOHM Knowledge Assistant Settings', 'aiohm-kb-assistant'); ?></h1>
    
    <div class="aiohm-admin-container">
        <div class="aiohm-main-content">
            <form method="post" action="options.php">
                <?php settings_fields('aiohm_kb_settings'); ?>
                
                <!-- API Settings Section -->
                <div class="aiohm-settings-section">
                    <h2><?php _e('API Configuration', 'aiohm-kb-assistant'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="openai_api_key"><?php _e('OpenAI API Key', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="openai_api_key" 
                                       name="aiohm_kb_settings[openai_api_key]" 
                                       value="<?php echo esc_attr($settings['openai_api_key']); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Your OpenAI API key for GPT models.', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="claude_api_key"><?php _e('Claude API Key', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="claude_api_key" 
                                       name="aiohm_kb_settings[claude_api_key]" 
                                       value="<?php echo esc_attr($settings['claude_api_key']); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Your Anthropic Claude API key.', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default_model"><?php _e('Default Model', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <select id="default_model" name="aiohm_kb_settings[default_model]">
                                    <option value="openai" <?php selected($settings['default_model'], 'openai'); ?>>
                                        <?php _e('OpenAI GPT', 'aiohm-kb-assistant'); ?>
                                    </option>
                                    <option value="claude" <?php selected($settings['default_model'], 'claude'); ?>>
                                        <?php _e('Anthropic Claude', 'aiohm-kb-assistant'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Chat Settings Section -->
                <div class="aiohm-settings-section">
                    <h2><?php _e('Chat Configuration', 'aiohm-kb-assistant'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Enable Chat', 'aiohm-kb-assistant'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="aiohm_kb_settings[chat_enabled]" 
                                           value="1" 
                                           <?php checked($settings['chat_enabled'], true); ?>>
                                    <?php _e('Enable chat functionality on frontend', 'aiohm-kb-assistant'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="max_tokens"><?php _e('Max Tokens', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_tokens" 
                                       name="aiohm_kb_settings[max_tokens]" 
                                       value="<?php echo esc_attr($settings['max_tokens']); ?>" 
                                       min="50" 
                                       max="2000">
                                <p class="description">
                                    <?php _e('Maximum tokens for AI responses (50-2000).', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="temperature"><?php _e('Temperature', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="temperature" 
                                       name="aiohm_kb_settings[temperature]" 
                                       value="<?php echo esc_attr($settings['temperature']); ?>" 
                                       min="0" 
                                       max="2" 
                                       step="0.1">
                                <p class="description">
                                    <?php _e('Response creativity (0.0 = focused, 2.0 = creative).', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Processing Settings Section -->
                <div class="aiohm-settings-section">
                    <h2><?php _e('Processing Configuration', 'aiohm-kb-assistant'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="chunk_size"><?php _e('Chunk Size', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="chunk_size" 
                                       name="aiohm_kb_settings[chunk_size]" 
                                       value="<?php echo esc_attr($settings['chunk_size']); ?>" 
                                       min="500" 
                                       max="3000">
                                <p class="description">
                                    <?php _e('Size of content chunks for processing (500-3000 characters).', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="chunk_overlap"><?php _e('Chunk Overlap', 'aiohm-kb-assistant'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="chunk_overlap" 
                                       name="aiohm_kb_settings[chunk_overlap]" 
                                       value="<?php echo esc_attr($settings['chunk_overlap']); ?>" 
                                       min="0" 
                                       max="500">
                                <p class="description">
                                    <?php _e('Overlap between chunks to maintain context (0-500 characters).', 'aiohm-kb-assistant'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="aiohm-sidebar">
            <?php
            // Status widgets will be rendered via AJAX
            ?>
        </div>
    </div>
</div>

<style>
.aiohm-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.aiohm-main-content {
    flex: 2;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.aiohm-sidebar {
    flex: 1;
}

.aiohm-settings-section {
    margin-bottom: 30px;
}

.aiohm-settings-section h2 {
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

@media (max-width: 782px) {
    .aiohm-admin-container {
        flex-direction: column;
    }
}
</style>