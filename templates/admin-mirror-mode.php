<?php
/**
 * Admin Q&A Settings page template for Club members.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Data Fetching and Status Checks ---
$settings = AIOHM_KB_Assistant::get_settings();
?>

<div class="wrap aiohm-qa-settings-page">
    <h1><?php _e('Q&A Chatbot Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Fine-tune your AI\'s personality, behavior, and appearance. These settings apply to the public-facing chatbot.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <form id="qa-settings-form">
        <?php wp_nonce_field('aiohm_qa_settings_nonce'); ?>

        <div class="aiohm-settings-section">
            <h2><?php _e('AI Personality & Behavior', 'aiohm-kb-assistant'); ?></h2>
            <div class="form-table">
                <div class="form-row">
                    <label for="qa_system_message">
                        <strong><?php _e('Q&A - AI System Message', 'aiohm-kb-assistant'); ?></strong>
                        <p class="description"><?php _e("If you're unsure, leave the default! It works well for most cases. If you tweak it, keep the {context} tag and others intact, as they are replaced in real-time.", 'aiohm-kb-assistant'); ?></p>
                    </label>
                    <textarea id="qa_system_message" name="aiohm_kb_settings[qa_system_message]" rows="15"><?php echo esc_textarea($settings['qa_system_message']); ?></textarea>
                </div>
                <div class="form-row">
                     <label for="qa_temperature">
                        <strong><?php _e('Temperature', 'aiohm-kb-assistant'); ?></strong>
                        <p class="description"><?php _e("Controls randomness. Lower values (e.g., 0.2) make the AI more focused and deterministic. Higher values (e.g., 0.8) make it more creative and diverse.", 'aiohm-kb-assistant'); ?></p>
                    </label>
                    <input type="number" id="qa_temperature" name="aiohm_kb_settings[qa_temperature]" value="<?php echo esc_attr($settings['qa_temperature']); ?>" min="0" max="1" step="0.1" class="small-text">
                </div>
            </div>
        </div>

        <div class="aiohm-settings-section">
            <h2><?php _e('Inline Embeddable Bot Configurations', 'aiohm-kb-assistant'); ?></h2>
             <p class="description" style="margin-bottom: 20px;"><?php _e('You can set values in pixels (e.g., 50px) or percentages (e.g., 50%). These settings apply when using the `[aiohm_chat]` shortcode.', 'aiohm-kb-assistant'); ?></p>
            <div class="form-table">
                <div class="form-row">
                    <label for="qa_desktop_width"><strong><?php _e('Desktop Width', 'aiohm-kb-assistant'); ?></strong></label>
                    <input type="text" id="qa_desktop_width" name="aiohm_kb_settings[qa_desktop_width]" value="<?php echo esc_attr($settings['qa_desktop_width']); ?>" placeholder="100%">
                </div>
                <div class="form-row">
                    <label for="qa_desktop_height"><strong><?php _e('Desktop Height', 'aiohm-kb-assistant'); ?></strong></label>
                    <input type="text" id="qa_desktop_height" name="aiohm_kb_settings[qa_desktop_height]" value="<?php echo esc_attr($settings['qa_desktop_height']); ?>" placeholder="500px">
                </div>
            </div>
        </div>

        <?php submit_button(__('Save Chatbot Settings', 'aiohm-kb-assistant')); ?>
    </form>
    
    <div class="aiohm-settings-section">
        <h2><?php _e('Test Your Chatbot\'s Responses', 'aiohm-kb-assistant'); ?></h2>
        <p class="description" style="margin-bottom: 20px;"><?php _e("Generate sample questions based on your knowledge base to see how your AI would respond. This helps you test its accuracy and tone.", 'aiohm-kb-assistant'); ?></p>
        <button type="button" id="generate-q-and-a" class="button button-secondary"><?php _e('Generate 10 Sample Q&A Pairs', 'aiohm-kb-assistant'); ?></button>
        <div id="q-and-a-results" class="q-and-a-container">
            </div>
    </div>
</div>

<style>
    /* Add your CSS styles here */
</style>

<script>
    // Add your JavaScript here
</script>