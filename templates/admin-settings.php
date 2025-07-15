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
    
    <div id="aiohm-admin-notice" class="notice is-dismissible" style="display:none; margin-top: 10px;" tabindex="-1" role="alert" aria-live="polite"><p></p></div>

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
                            <option value="claude" <?php selected($settings['default_ai_provider'] ?? '', 'claude'); ?>>Claude</option>
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

        <div class="aiohm-settings-section aiohm-usage-overview">
            <h2><?php _e('AI Usage Overview', 'aiohm-kb-assistant'); ?></h2>
            <div class="aiohm-usage-stats-grid">
                <div class="usage-stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php _e('Total Tokens (30 Days)', 'aiohm-kb-assistant'); ?></h3>
                        <div class="stat-value" id="total-tokens-30d">-</div>
                        <div class="stat-subtext"><?php _e('All providers combined', 'aiohm-kb-assistant'); ?></div>
                    </div>
                </div>
                
                <div class="usage-stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <h3><?php _e('Today\'s Usage', 'aiohm-kb-assistant'); ?></h3>
                        <div class="stat-value" id="tokens-today">-</div>
                        <div class="stat-subtext"><?php _e('Current day total', 'aiohm-kb-assistant'); ?></div>
                    </div>
                </div>
                
                <div class="usage-stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3><?php _e('Estimated Cost', 'aiohm-kb-assistant'); ?></h3>
                        <div class="stat-value" id="estimated-cost">-</div>
                        <div class="stat-subtext"><?php _e('Last 30 days (USD)', 'aiohm-kb-assistant'); ?></div>
                    </div>
                </div>
                
                <div class="usage-stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <h3><?php _e('Active Provider', 'aiohm-kb-assistant'); ?></h3>
                        <div class="stat-value" id="active-provider"><?php echo esc_html(ucfirst($settings['default_ai_provider'] ?? 'OpenAI')); ?></div>
                        <div class="stat-subtext"><?php _e('Primary AI service', 'aiohm-kb-assistant'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="aiohm-usage-breakdown">
                <h3><?php _e('Usage Breakdown by Provider', 'aiohm-kb-assistant'); ?></h3>
                <div class="usage-breakdown-table">
                    <div class="breakdown-header">
                        <span><?php _e('Provider', 'aiohm-kb-assistant'); ?></span>
                        <span><?php _e('Tokens (30d)', 'aiohm-kb-assistant'); ?></span>
                        <span><?php _e('Requests', 'aiohm-kb-assistant'); ?></span>
                        <span><?php _e('Est. Cost', 'aiohm-kb-assistant'); ?></span>
                    </div>
                    <div class="breakdown-row" data-provider="openai">
                        <span class="provider-name">
                            <span class="provider-icon">🤖</span>
                            OpenAI
                        </span>
                        <span class="tokens-count" id="openai-tokens">-</span>
                        <span class="requests-count" id="openai-requests">-</span>
                        <span class="cost-estimate" id="openai-cost">-</span>
                    </div>
                    <div class="breakdown-row" data-provider="gemini">
                        <span class="provider-name">
                            <span class="provider-icon">💎</span>
                            Gemini
                        </span>
                        <span class="tokens-count" id="gemini-tokens">-</span>
                        <span class="requests-count" id="gemini-requests">-</span>
                        <span class="cost-estimate" id="gemini-cost">-</span>
                    </div>
                    <div class="breakdown-row" data-provider="claude">
                        <span class="provider-name">
                            <span class="provider-icon">🧠</span>
                            Claude
                        </span>
                        <span class="tokens-count" id="claude-tokens">-</span>
                        <span class="requests-count" id="claude-requests">-</span>
                        <span class="cost-estimate" id="claude-cost">-</span>
                    </div>
                </div>
                <button type="button" id="refresh-usage-stats" class="button button-secondary">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Stats', 'aiohm-kb-assistant'); ?>
                </button>
            </div>
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

    /* AI Usage Overview Styles */
    .aiohm-usage-overview {
        background: linear-gradient(135deg, #f8fbf9 0%, #f3f9f5 100%);
        border: 2px solid var(--ohm-light-accent);
        border-radius: 8px;
        position: relative;
        overflow: hidden;
    }

    .aiohm-usage-overview::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--ohm-dark-accent) 0%, var(--ohm-primary) 100%);
    }

    .aiohm-usage-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .usage-stat-card {
        background: white;
        border: 1px solid var(--ohm-light-accent);
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .usage-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(69, 125, 88, 0.15);
        border-color: var(--ohm-primary);
    }

    .stat-icon {
        font-size: 2.5em;
        line-height: 1;
        opacity: 0.8;
    }

    .stat-content h3 {
        margin: 0 0 5px 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--ohm-dark);
        font-family: var(--ohm-font-primary);
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: var(--ohm-dark-accent);
        margin-bottom: 5px;
        font-family: var(--ohm-font-primary);
    }

    .stat-subtext {
        font-size: 12px;
        color: #666;
        font-family: var(--ohm-font-secondary);
    }

    .aiohm-usage-breakdown h3 {
        margin-bottom: 15px;
        color: var(--ohm-dark-accent);
        font-family: var(--ohm-font-primary);
    }

    .usage-breakdown-table {
        background: white;
        border: 1px solid var(--ohm-light-accent);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .breakdown-header,
    .breakdown-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 15px;
        padding: 15px 20px;
        align-items: center;
    }

    .breakdown-header {
        background: var(--ohm-light-bg);
        font-weight: 600;
        font-size: 13px;
        color: var(--ohm-dark);
        font-family: var(--ohm-font-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .breakdown-row {
        border-bottom: 1px solid var(--ohm-light-bg);
        transition: background-color 0.2s ease;
    }

    .breakdown-row:last-child {
        border-bottom: none;
    }

    .breakdown-row:hover {
        background: #f8fbf9;
    }

    .provider-name {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: var(--ohm-dark);
        font-family: var(--ohm-font-secondary);
    }

    .provider-icon {
        font-size: 16px;
    }

    .tokens-count,
    .requests-count,
    .cost-estimate {
        font-family: 'Monaco', 'Menlo', monospace;
        font-size: 13px;
        color: var(--ohm-dark);
        text-align: right;
    }

    .cost-estimate {
        font-weight: 600;
        color: var(--ohm-primary);
    }

    #refresh-usage-stats {
        background: var(--ohm-primary);
        border-color: var(--ohm-primary);
        color: white;
        font-family: var(--ohm-font-primary);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #refresh-usage-stats:hover {
        background: var(--ohm-dark-accent);
        border-color: var(--ohm-dark-accent);
        transform: translateY(-1px);
    }

    #refresh-usage-stats .dashicons {
        margin-right: 5px;
        vertical-align: middle;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .aiohm-usage-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .breakdown-header,
        .breakdown-row {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .breakdown-header span,
        .breakdown-row span {
            text-align: left;
        }
        
        .usage-stat-card {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<script>
jQuery(document).ready(function($){
    let noticeTimer;
    
    // Enhanced admin notice function with accessibility features
    function showAdminNotice(message, type = 'success', persistent = false) {
        clearTimeout(noticeTimer);
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
            // Auto-focus for accessibility after fade in completes
            $noticeDiv.focus();
            
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
            noticeTimer = setTimeout(() => {
                if ($noticeDiv.is(':visible')) {
                    $noticeDiv.fadeOut(300, function() {
                        // Return focus to main content when auto-hiding
                        $('h1').focus();
                    });
                }
            }, 7000); // Increased to 7 seconds for better UX
        }
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

    // AI Usage Statistics functionality
    function loadUsageStats() {
        $.post(ajaxurl, {
            action: 'aiohm_get_usage_stats',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>'
        })
        .done(function(response) {
            if (response.success && response.data) {
                updateUsageDisplay(response.data);
            } else {
                console.warn('Failed to load usage stats:', response.data?.message || 'Unknown error');
                showUsageError();
            }
        })
        .fail(function() {
            console.error('Server error while loading usage stats');
            showUsageError();
        });
    }

    function updateUsageDisplay(data) {
        // Update main stats cards
        $('#total-tokens-30d').text(formatNumber(data.total_tokens_30d || 0));
        $('#tokens-today').text(formatNumber(data.tokens_today || 0));
        $('#estimated-cost').text('$' + (data.estimated_cost || '0.00'));
        
        // Update breakdown table
        const providers = ['openai', 'gemini', 'claude'];
        providers.forEach(provider => {
            const providerData = data.providers?.[provider] || {};
            $('#' + provider + '-tokens').text(formatNumber(providerData.tokens || 0));
            $('#' + provider + '-requests').text(formatNumber(providerData.requests || 0));
            $('#' + provider + '-cost').text('$' + (providerData.cost || '0.00'));
        });
    }

    function showUsageError() {
        $('.stat-value, .tokens-count, .requests-count, .cost-estimate').text('Error');
    }

    function formatNumber(num) {
        if (num === 0) return '0';
        if (num < 1000) return num.toString();
        if (num < 1000000) return (num / 1000).toFixed(1) + 'K';
        return (num / 1000000).toFixed(1) + 'M';
    }

    // Refresh usage stats button
    $('#refresh-usage-stats').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin-top:0; vertical-align:middle;"></span> Loading...');
        
        loadUsageStats();
        
        setTimeout(() => {
            $btn.prop('disabled', false).html(originalHtml);
        }, 2000);
    });

    // Load usage stats on page load
    loadUsageStats();
});
</script>