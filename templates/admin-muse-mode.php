<?php
/**
 * Admin Muse Mode Settings page template for Club members.
 * Evolved into the "Digital Doula" experience with advanced, intuitive settings.
 * This version includes the new element order and dynamic archetype prompts.
 *
 * *** UPDATED: Includes new top bar for "Download PDF", "Research Online", and "Audio" buttons. ***
 */

if (!defined('ABSPATH')) exit;

// Fetch all settings and then get the specific part for Muse Mode
$all_settings = AIOHM_KB_Assistant::get_settings();
$settings = $all_settings['muse_mode'] ?? [];
$global_settings = $all_settings;

// Check if user has private access for Ollama
$has_private_access = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_private_access();


// --- START: Archetype Prompts ---
$archetype_prompts = [
    'the_creator' => "You are The Creator, an innovative and imaginative brand assistant. Your purpose is to help build things of enduring value. You speak with authenticity and a visionary spirit, inspiring new ideas and artistic expression. You avoid generic language and focus on originality and the creative process.",
    'the_sage' => "You are The Sage, a wise and knowledgeable brand assistant. Your goal is to seek the truth and share it with others. You communicate with clarity, accuracy, and thoughtful insight. You avoid hype and superficiality, instead focusing on providing well-researched, objective information and wisdom.",
    'the_innocent' => "You are The Innocent, an optimistic and pure brand assistant. Your purpose is to spread happiness and see the good in everything. You speak with simple, honest, and positive language. You avoid cynicism and complexity, focusing on straightforward, wholesome, and uplifting messages.",
    'the_explorer' => "You are The Explorer, an adventurous and independent brand assistant. Your mission is to help others experience a more authentic and fulfilling life by pushing boundaries. You speak with a rugged, open-minded, and daring tone. You avoid conformity and rigid rules, focusing on freedom, discovery, and the journey.",
    'the_ruler' => "You are The Ruler, an authoritative and confident brand assistant. Your purpose is to create order and build a prosperous community. You speak with a commanding, polished, and articulate voice. You avoid chaos and mediocrity, focusing on leadership, quality, and control.",
    'the_hero' => "You are The Hero, a courageous and determined brand assistant. Your mission is to inspire others to triumph over adversity. You speak with a bold, confident, and motivational tone. You avoid negativity and weakness, focusing on mastery, ambition, and overcoming challenges.",
    'the_lover' => "You are The Lover, an intimate and empathetic brand assistant. Your goal is to help people feel appreciated and connected. You speak with a warm, sensual, and passionate voice. You avoid conflict and isolation, focusing on relationships, intimacy, and creating blissful experiences.",
    'the_jester' => "You are The Jester, a playful and fun-loving brand assistant. Your purpose is to bring joy to the world and live in the moment. You speak with a witty, humorous, and lighthearted tone. You avoid boredom and seriousness, focusing on entertainment, cleverness, and seeing the funny side of life.",
    'the_everyman' => "You are The Everyman, a relatable and down-to-earth brand assistant. Your goal is to belong and connect with others on a human level. You speak with a friendly, humble, and authentic voice. You avoid elitism and pretense, focusing on empathy, realism, and shared values.",
    'the_caregiver' => "You are The Caregiver, a compassionate and nurturing brand assistant. Your purpose is to protect and care for others. You speak with a warm, reassuring, and supportive tone. You avoid selfishness and trouble, focusing on generosity, empathy, and providing a sense of security.",
    'the_magician' => "You are The Magician, a visionary and charismatic brand assistant. Your purpose is to make dreams come true and create something special. You speak with a mystical, inspiring, and transformative voice. You avoid the mundane and doubt, focusing on moments of wonder, vision, and the power of belief.",
    'the_outlaw' => "You are The Outlaw, a rebellious and revolutionary brand assistant. Your mission is to challenge the status quo and break the rules. You speak with a raw, disruptive, and unapologetic voice. You avoid conformity and powerlessness, focusing on liberation, revolution, and radical freedom.",
];
$default_prompt = "You are Muse, a private brand assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.";
$system_prompt = !empty($settings['system_prompt']) ? $settings['system_prompt'] : $default_prompt;

// Archetypes for the dropdown
$brand_archetypes = [
    'the_creator' => 'The Creator', 'the_sage' => 'The Sage', 'the_innocent' => 'The Innocent', 'the_explorer' => 'The Explorer', 'the_ruler' => 'The Ruler', 'the_hero' => 'The Hero', 'the_lover' => 'The Lover', 'the_jester' => 'The Jester', 'the_everyman' => 'The Everyman', 'the_caregiver' => 'The Caregiver', 'the_magician' => 'The Magician', 'the_outlaw' => 'The Outlaw',
];
// --- END: Archetype Prompts ---
?>

<div class="wrap aiohm-settings-page aiohm-muse-mode-page">
    <h1><?php esc_html_e('Muse Mode Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php esc_html_e('Here, you attune your AI to be a true creative partner. Define its energetic signature and workflow to transform your brand dialogue.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice is-dismissible" style="display:none; margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>

    <div class="aiohm-muse-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="muse-mode-settings-form">
                <?php wp_nonce_field('aiohm_muse_mode_nonce', 'aiohm_muse_mode_nonce_field'); ?>
                
                <div class="aiohm-settings-section">

                    <div class="aiohm-setting-block">
                        <label for="assistant_name"><?php esc_html_e('1. Brand Assistant Name', 'aiohm-kb-assistant'); ?></label>
                        <input type="text" id="assistant_name" name="aiohm_kb_settings[muse_mode][assistant_name]" value="<?php echo esc_attr($settings['assistant_name'] ?? 'Muse'); ?>">
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="brand_archetype"><?php esc_html_e('2. Brand Archetype', 'aiohm-kb-assistant'); ?></label>
                        <select id="brand_archetype" name="aiohm_kb_settings[muse_mode][brand_archetype]">
                            <option value=""><?php esc_html_e('-- Select an Archetype --', 'aiohm-kb-assistant'); ?></option>
                            <?php foreach ($brand_archetypes as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['brand_archetype'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select an archetype to give your Muse a foundational personality.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <div class="aiohm-setting-header">
                            <label for="system_prompt"><?php esc_html_e('3. Soul Signature Brand Assistant', 'aiohm-kb-assistant'); ?></label>
                            <button type="button" id="reset-prompt-btn" class="button-link"><?php esc_html_e('Reset to Default', 'aiohm-kb-assistant'); ?></button>
                        </div>
                        <textarea id="system_prompt" name="aiohm_kb_settings[muse_mode][system_prompt]" rows="10"><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description"><?php esc_html_e('This is the core instruction set for your AI. Selecting an archetype will provide a starting template.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <div class="aiohm-setting-header">
                            <label for="ai_model_selector"><?php esc_html_e('4. AI Model', 'aiohm-kb-assistant'); ?></label>
                        </div>
                        <select id="ai_model_selector" name="aiohm_kb_settings[muse_mode][ai_model]">
                            <?php if (!empty($global_settings['openai_api_key'])): ?>
                                <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo'); ?>>OpenAI: GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected($settings['ai_model'] ?? '', 'gpt-4'); ?>>OpenAI: GPT-4</option>
                            <?php endif; ?>
                            <?php if (!empty($global_settings['gemini_api_key'])): ?>
                                <option value="gemini-pro" <?php selected($settings['ai_model'] ?? '', 'gemini-pro'); ?>>Google: Gemini Pro</option>
                            <?php endif; ?>
                            <?php if (!empty($global_settings['claude_api_key'])): ?>
                                <option value="claude-3-sonnet" <?php selected($settings['ai_model'] ?? '', 'claude-3-sonnet'); ?>>Anthropic: Claude 3 Sonnet</option>
                            <?php endif; ?>
                            <?php if ($has_private_access && !empty($global_settings['private_llm_server_url'])): ?>
                                <option value="ollama" <?php selected($settings['ai_model'] ?? '', 'ollama'); ?>>Ollama: <?php echo esc_html($global_settings['private_llm_model'] ?? 'Private Server'); ?></option>
                            <?php endif; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Choose which AI model powers your brand assistant.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="temperature"><?php esc_html_e('5. Temperature:', 'aiohm-kb-assistant'); ?> <span class="temp-value"><?php echo esc_attr($settings['temperature'] ?? '0.7'); ?></span></label>
                        <input type="range" id="temperature" name="aiohm_kb_settings[muse_mode][temperature]" value="<?php echo esc_attr($settings['temperature'] ?? '0.7'); ?>" min="0" max="1" step="0.1">
                        <p class="description"><?php esc_html_e('Lower is more predictable; higher is more creative.', 'aiohm-kb-assistant'); ?></p>
                    </div>
                    
                    <div class="aiohm-setting-block">
                        <label for="start_fullscreen">
                            <input type="checkbox" id="start_fullscreen" name="aiohm_kb_settings[muse_mode][start_fullscreen]" value="1" <?php checked($settings['start_fullscreen'] ?? false); ?>>
                            <?php esc_html_e('Start in Fullscreen Mode', 'aiohm-kb-assistant'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Check this to make the private assistant always open in fullscreen.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                </div>
                <div class="form-actions">
                    <button type="button" id="save-muse-mode-settings" class="button button-primary"><?php esc_html_e('Save Muse Settings', 'aiohm-kb-assistant'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiohm-test-column">
            <h3><?php esc_html_e('Test Your Muse Assistant', 'aiohm-kb-assistant'); ?></h3>
            <p class="description"><?php 
                // translators: %s is the shortcode to display the private assistant
                printf(esc_html__('Test your assistant here. For the full experience, use the shortcode: %s on a new page', 'aiohm-kb-assistant'), '<code>[aiohm_private_assistant]</code>'); ?></p>
            <div id="aiohm-test-chat" class="aiohm-chat-container">
                <div class="aiohm-chat-header">
                    <div class="aiohm-chat-title-preview"><?php echo esc_html($settings['assistant_name'] ?? 'Muse'); ?></div>
                </div>
                <div class="aiohm-chat-messages">
                    <div class="aiohm-message aiohm-message-bot">
                        <div class="aiohm-message-avatar">
                            <?php
                            echo wp_kses_post(AIOHM_KB_Core_Init::render_image(
                                AIOHM_KB_PLUGIN_URL . 'assets/images/OHM-logo.png',
                                esc_attr__('AI Avatar', 'aiohm-kb-assistant'),
                                ['class' => 'aiohm-avatar-preview']
                            ));
                            ?>
                        </div>
                        <div class="aiohm-message-bubble"><div class="aiohm-message-content">I'm your private brand assistant. Ask me to help you create content or brainstorm ideas.</div></div>
                    </div>
                </div>
                <div class="aiohm-chat-input-container">
                    <div class="aiohm-chat-input-wrapper">
                        <textarea class="aiohm-chat-input" placeholder="Ask your question here..." rows="1"></textarea>
                        <button type="button" class="aiohm-chat-send-btn" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>
                    </div>
                </div>
            </div>
            <div class="aiohm-search-container-wrapper">
                <h3><?php esc_html_e('Test Knowledge Base Context', 'aiohm-kb-assistant'); ?></h3>
                <p class="description">Check what information the AI can find in your knowledge base for a given query.</p>
                <div class="aiohm-search-controls">
                    <div class="aiohm-search-form">
                        <div class="aiohm-search-input-wrapper">
                            <input type="text" class="aiohm-search-input" placeholder="Search knowledge base...">
                            <button type="button" class="aiohm-search-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="aiohm-search-results"></div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Enhanced admin notice function with accessibility features
    function showAdminNotice(message, type = 'success', persistent = false) {
        let $noticeDiv = $('#aiohm-admin-notice');
        
        // Create notice div if it doesn't exist
        if ($noticeDiv.length === 0) {
            $('<div id="aiohm-admin-notice" class="notice is-dismissible" style="margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>').insertAfter('h1');
            $noticeDiv = $('#aiohm-admin-notice');
        }
        
        // Clear existing classes and add new type
        $noticeDiv.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type);
        
        // Set message content
        $noticeDiv.find('p').html(message);
        
        // Show notice with fade in effect
        $noticeDiv.fadeIn(300, function() {
            // Auto-focus for accessibility and scroll to notice
            $noticeDiv.focus();
            $('html, body').animate({
                scrollTop: $noticeDiv.offset().top - 100
            }, 300);
            
            // Announce to screen readers
            if (type === 'error') {
                $noticeDiv.attr('aria-live', 'assertive');
            } else {
                $noticeDiv.attr('aria-live', 'polite');
            }
        });
        
        // Handle dismiss button
        $noticeDiv.off('click.notice-dismiss').on('click.notice-dismiss', '.notice-dismiss', function() {
            $noticeDiv.fadeOut(300);
            // Return focus to the previously focused element or main content
            $('h1').focus();
        });
        
        // Auto-hide after timeout (unless persistent)
        if (!persistent) {
            setTimeout(() => {
                if ($noticeDiv.is(':visible')) {
                    $noticeDiv.fadeOut(300, function() {
                        // Return focus to main content when auto-hiding
                        $('h1').focus();
                    });
                }
            }, 7000); // Increased to 7 seconds for better UX
        }
    }

    // If there are any AJAX calls or form submissions in this page,
    // they should use showAdminNotice instead of alerts
});
</script>