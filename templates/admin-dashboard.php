<?php
/**
 * Admin Dashboard template.
 * This is the complete and final version with all UI improvements and the Brand Assistant chat interface.
 */
if (!defined('ABSPATH')) exit;

// Setup variables
$settings = AIOHM_KB_Assistant::get_settings();
$is_user_linked = !empty($settings['personal_api_key'] ?? '');

$default_tab = 'welcome';
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

$brand_soul_questions = [
    "Tell Your Story. What was life like before you cracked the code?",
    "What was the inciting event that changed your life?",
    "What is life like today, for you?",
    "What is the primary goal of your brand?",
    "What drives your passion for your industry or niche?",
    "What qualities make you a trustworthy expert?",
    "What range of products or services do you offer?",
    "Who is your target market?",
    "Describe your target audience in detail.",
    "What challenges do your customers typically face?",
    "How does your company solve these challenges?",
    "What are the most common objections potential customers raise?",
    "What outcomes or benefits can customers anticipate?",
];
?>
<div class="wrap aiohm-dashboard">
    <div class="aiohm-header">
        <h1><?php _e('AIOHM Assistant Dashboard', 'aiohm-kb-assistant'); ?></h1>
        <p class="aiohm-tagline"><?php _e("Welcome! Let's turn your content into an expert AI assistant.", 'aiohm-kb-assistant'); ?></p>
    </div>

    <nav class="nav-tab-wrapper">
        <a href="?page=aiohm-dashboard&tab=welcome" class="nav-tab <?php echo $current_tab == 'welcome' ? 'nav-tab-active' : ''; ?>"><?php _e('Welcome', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=tribe" class="nav-tab <?php echo $current_tab == 'tribe' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Brand Soul', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=club" class="nav-tab <?php echo $current_tab == 'club' ? 'nav-tab-active' : ''; ?>"><?php _e('Brand Assistant', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=private" class="nav-tab <?php echo $current_tab == 'private' ? 'nav-tab-active' : ''; ?>"><?php _e('AIOHM Private', 'aiohm-kb-assistant'); ?></a>
    </nav>

    <div class="aiohm-tab-content">
        <?php if ($current_tab === 'welcome'): ?>
            <div class="aiohm-getting-started">
                <h2><?php _e('How to Build Your AI', 'aiohm-kb-assistant'); ?></h2>
                <div class="aiohm-steps">
                    <div class="aiohm-step">
                        <h3><?php _e('1. Teach Your AI', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("Go to the 'Scan Content' page to find all the posts, pages, and files on your site that your AI can learn from.", 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-scan-content'); ?>" class="button button-secondary"><?php _e('Go to Scan Page', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step">
                        <h3><?php _e("2. Manage Your AI's Knowledge", 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("View everything your AI knows, remove items, or export your knowledge base for backup from the 'Manage KB' page.", 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-secondary"><?php _e('Manage Knowledge', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step">
                        <h3><?php _e('3. Deploy Your AI', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("Add the `[aiohm_chat]` shortcode to any page, or enable the floating chat in 'Settings' to let visitors start talking to your AI.", 'aiohm-kb-assistant'); ?></p>
                         <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary"><?php _e('Go to Settings', 'aiohm-kb-assistant'); ?></a>
                    </div>
                </div>
            </div>

        <?php elseif ($current_tab === 'tribe'): ?>
            <?php if ($is_user_linked): ?>
                <h2><?php _e('Your AI Brand Soul', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e('Answer these questions to infuse your AI with your unique brand identity. Your answers will create your personal knowledge base.', 'aiohm-kb-assistant'); ?></p>
                
                <form id="brand-soul-form">
                    <?php foreach ($brand_soul_questions as $index => $question): ?>
                        <div class="form-group">
                            <label for="q-<?php echo $index; ?>"><strong><?php echo esc_html($question); ?></strong></label>
                            <textarea id="q-<?php echo $index; ?>" name="answers[<?php echo esc_attr($question); ?>]" rows="4" class="large-text"></textarea>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="button button-primary"><?php _e('Save to My Personal KB', 'aiohm-kb-assistant'); ?></button>
                    <span class="spinner"></span>
                </form>
                <div id="form-status" style="margin-top: 15px;"></div>

            <?php else: ?>
                <div class="aiohm-getting-started">
                    <h2><?php _e('Join the AIOHM Tribe to Unlock Your AI Brand Soul', 'aiohm-kb-assistant'); ?></h2>
                    <p><?php _e('The first step on your journey is to create your free AIOHM Tribe account. This will give you access to the "Brand Soul" questionnaire, which forms the core of your personal AI.', 'aiohm-kb-assistant'); ?></p>
                    <div class="aiohm-steps">
                        <div class="aiohm-step">
                            <h3><?php _e('1. Join the Tribe', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('Click the button below to register for your free account on our main website.', 'aiohm-kb-assistant'); ?></p>
                            <a href="https://www.aiohm.app/register" target="_blank" class="button button-primary"><?php _e('Register on aiohm.app', 'aiohm-kb-assistant'); ?></a>
                        </div>
                        <div class="aiohm-step">
                            <h3><?php _e('2. Link Your Account', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('After registering, find the "Personal API Key" in your account dashboard and paste it into this plugin\'s settings.', 'aiohm-kb-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary"><?php _e('Go to Settings', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($current_tab === 'club'): ?>
            <div class="aiohm-brand-assistant-wrapper">
                <h2><?php _e('AI Brand Assistant', 'aiohm-kb-assistant'); ?></h2>
                <p><?php _e("This is your private AI assistant. It has access to your public site content and your personal 'AI Brand Soul' answers. Ask it for content ideas, brand strategy, or to clarify your story.", 'aiohm-kb-assistant'); ?></p>
                <div id="brand-assistant-chat-container" class="aiohm-chat-container">
                    <div class="aiohm-chat-messages" style="height: 400px;">
                        <div class="aiohm-message aiohm-message-bot"><div class="aiohm-message-content">Hello! How can I help you build your brand today?</div></div>
                    </div>
                    <div class="aiohm-chat-input-container">
                        <textarea id="brand-assistant-input" placeholder="Ask a question about your brand..." rows="1"></textarea>
                        <button id="brand-assistant-send-btn" class="button button-primary">Send</button>
                    </div>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'private'): ?>
            <h3><?php _e('AIOHM Private Member Area', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Features for Private members will be built here, including the settings to connect to a private LLM.', 'aiohm-kb-assistant'); ?></p>
        <?php endif; ?>
    </div>
</div>
<style>
.aiohm-dashboard .aiohm-header { background: #fff; padding: 20px 30px; border-bottom: 1px solid #dcdcde; margin: -20px -20px 0 -20px; }
.aiohm-dashboard h1 { font-size: 28px; }
.aiohm-tagline { font-size: 16px; color: #50575e; }
.nav-tab-wrapper { margin-bottom: 0; padding: 15px 15px 0 15px; }
.aiohm-tab-content { background: #fff; padding: 25px; border: 1px solid #dcdcde; }
.aiohm-steps { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
.aiohm-step { background: #f8f9fa; border-radius: 4px; padding: 25px; flex: 1; min-width: 280px; }
#brand-soul-form .form-group { margin-bottom: 20px; }
#form-status.success { color: #28a745; font-weight: bold; }
#form-status.error { color: #dc3545; }
.aiohm-chat-container { border: 1px solid #dcdcde; background: #fff; max-width: 800px; margin-top: 15px; }
.aiohm-chat-messages { padding: 15px; overflow-y: auto; }
.aiohm-message { display: flex; margin-bottom: 12px; }
.aiohm-message-content { padding: 8px 12px; border-radius: 6px; line-height: 1.5; max-width: 85%; }
.aiohm-message-bot .aiohm-message-content { background: #f0f0f1; }
.aiohm-message-user { justify-content: flex-end; }
.aiohm-message-user .aiohm-message-content { background: #007cba; color: #fff; }
.aiohm-chat-input-container { display: flex; padding: 10px; border-top: 1px solid #dcdcde; gap: 10px; }
.aiohm-chat-input-container textarea { width: 100%; border-radius: 4px; border-color: #dcdcde; resize: vertical; }
</style>
<script>
jQuery(document).ready(function($){
    $('#brand-soul-form').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        const $spinner = $form.find('.spinner');
        const $status = $('#form-status');
        const formData = $form.serialize();

        $spinner.addClass('is-active');
        $status.text('Saving to your Personal Knowledge Base...').removeClass('success error');

        $.post(ajaxurl, {
            action: 'aiohm_save_personal_kb',
            nonce: '<?php echo wp_create_nonce("aiohm_personal_kb_nonce"); ?>',
            data: formData
        }).done(function(response){
            if (response.success) {
                $status.text(response.data.message).addClass('success');
                $form.find('textarea').val('');
            } else {
                $status.text(response.data.message).addClass('error');
            }
        }).fail(function(){
            $status.text('An unknown server error occurred.');
        }).always(function(){
            $spinner.removeClass('is-active');
        });
    });

    // Brand Assistant Chat Logic
    const $messagesContainer = $('#brand-assistant-chat-container .aiohm-chat-messages');
    const $input = $('#brand-assistant-input');
    const $sendBtn = $('#brand-assistant-send-btn');

    function addMessage(content, sender) {
        const messageClass = sender === 'user' ? 'aiohm-message-user' : 'aiohm-message-bot';
        const message = `<div class="aiohm-message ${messageClass}"><div class="aiohm-message-content">${content}</div></div>`;
        $messagesContainer.append(message);
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
    }

    $sendBtn.on('click', function(){
        const query = $input.val().trim();
        if (!query) return;

        addMessage(query, 'user');
        $input.val('');
        $sendBtn.prop('disabled', true);
        addMessage('<i>Thinking...</i>', 'bot');

        $.post(ajaxurl, {
            action: 'aiohm_brand_assistant_chat',
            nonce: '<?php echo wp_create_nonce("aiohm_admin_nonce"); ?>',
            query: query
        }).done(function(response){
            $messagesContainer.find('.aiohm-message-bot:last-child').remove();
            if (response.success) {
                addMessage(response.data.response.replace(/\n/g, '<br>'), 'bot');
            } else {
                addMessage(`<strong>Error:</strong> ${response.data.message}`, 'bot');
            }
        }).fail(function(){
             $messagesContainer.find('.aiohm-message-bot:last-child').remove();
            addMessage('<strong>Error:</strong> An unknown server error occurred.', 'bot');
        }).always(function(){
            $sendBtn.prop('disabled', false);
        });
    });
    
    $input.on('keypress', function(e){
        if(e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $sendBtn.click();
        }
    });
});
</script>