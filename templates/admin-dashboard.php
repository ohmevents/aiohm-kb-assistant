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

// Correctly initialize $plans as an array to prevent the foreach warning.
$plans = get_user_meta(get_current_user_id(), '_aiohm_user_plans', true);
if (!is_array($plans)) {
    $plans = []; // Ensure $plans is always an array
}

/**
 * Checks if a user has a plan containing a specific slug.
 * @param string $slug The keyword to look for in the plan name (e.g., 'Tribe', 'Club').
 * @param array  $plans_array The array of plans to check.
 * @return bool True if a matching plan is found, false otherwise.
 */
function aiohm_has_plan($slug, $plans_array) {
    // The function now receives the $plans_array directly.
    // The foreach loop is now safe because $plans_array is guaranteed to be an array.
    foreach ($plans_array as $plan) {
        if (isset($plan['name']) && stripos($plan['name'], $slug) !== false) {
            return true;
        }
    }
    return false;
}

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
        
        <?php // We now pass the $plans array directly to the function call ?>
        <?php if (aiohm_has_plan('Tribe', $plans)) : ?>
            <a href="?page=aiohm-dashboard&tab=tribe" class="nav-tab <?php echo $current_tab == 'tribe' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Brand Soul', 'aiohm-kb-assistant'); ?></a>
        <?php endif; ?>
        
        <?php if (aiohm_has_plan('Club', $plans)) : ?>
            <a href="?page=aiohm-dashboard&tab=club" class="nav-tab <?php echo $current_tab == 'club' ? 'nav-tab-active' : ''; ?>"><?php _e('Brand Assistant', 'aiohm-kb-assistant'); ?></a>
        <?php endif; ?>

        <?php if (aiohm_has_plan('Private', $plans)) : ?>
            <a href="?page=aiohm-dashboard&tab=private" class="nav-tab <?php echo $current_tab == 'private' ? 'nav-tab-active' : ''; ?>"><?php _e('AIOHM Private', 'aiohm-kb-assistant'); ?></a>
        <?php endif; ?>
    </nav>

    <div class="aiohm-tab-content" style="margin-top: 20px;">
        <?php if ($current_tab === 'welcome'): ?>
            <div class="aiohm-getting-started">
                <h2><?php _e('How to Build Your AI', 'aiohm-kb-assistant'); ?></h2>
                <div class="aiohm-steps" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <div class="aiohm-step" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3><?php _e('1. Teach Your AI', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("Go to the 'Scan Content' page to find all the posts, pages, and files on your site that your AI can learn from.", 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-scan-content'); ?>" class="button button-secondary"><?php _e('Go to Scan Page', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3><?php _e("2. Manage Your AI's Knowledge", 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("View everything your AI knows, remove items, or export your knowledge base for backup from the 'Manage KB' page.", 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-secondary"><?php _e('Manage Knowledge', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3><?php _e('3. Deploy Your AI', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e("Add the `[aiohm_chat]` shortcode to any page, or enable the floating chat in 'Settings' to let visitors start talking to your AI.", 'aiohm-kb-assistant'); ?></p>
                         <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary"><?php _e('Go to Settings', 'aiohm-kb-assistant'); ?></a>
                    </div>
                </div>
            </div>

        <?php elseif ($current_tab === 'tribe' && aiohm_has_plan('Tribe', $plans)): ?>
            <h2><?php _e('Your AI Brand Soul', 'aiohm-kb-assistant'); ?></h2>
            <form id="brand-soul-form">
                <?php wp_nonce_field('aiohm_personal_kb_nonce'); ?>
                <?php foreach ($brand_soul_questions as $index => $question): ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="q-<?php echo $index; ?>" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo esc_html($question); ?></label>
                        <textarea id="q-<?php echo $index; ?>" name="answers[<?php echo esc_attr($question); ?>]" rows="4" class="large-text"></textarea>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="button button-primary"><?php _e('Save to My Personal KB', 'aiohm-kb-assistant'); ?></button>
            </form>

        <?php elseif ($current_tab === 'club' && aiohm_has_plan('Club', $plans)): ?>
            <h2><?php _e('AI Brand Assistant', 'aiohm-kb-assistant'); ?></h2>
            <div id="brand-assistant-chat-container" class="aiohm-chat-container" style="max-width: 800px; margin-top: 20px;">
                <div class="aiohm-chat-messages" style="height: 400px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9; border-radius: 4px; overflow-y: auto;">
                    <div class="aiohm-message aiohm-message-bot"><div class="aiohm-message-content">Hello! How can I help you build your brand today?</div></div>
                </div>
                <div class="aiohm-chat-input-container" style="margin-top: 15px; display: flex; gap: 10px;">
                    <textarea id="brand-assistant-input" placeholder="Ask a question about your brand..." rows="1" style="flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    <button id="brand-assistant-send-btn" class="button button-primary"><?php _e('Send', 'aiohm-kb-assistant'); ?></button>
                </div>
            </div>

        <?php elseif ($current_tab === 'private' && aiohm_has_plan('Private', $plans)): ?>
            <h3><?php _e('AIOHM Private Member Area', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Features for Private members will be built here, including the settings to connect to a private LLM.', 'aiohm-kb-assistant'); ?></p>
        
        <?php else: // This block handles cases where a tab is accessed without the required plan ?>
             <div class="aiohm-getting-started" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <p><?php _e('This tab requires an active membership. Please check your license status or visit aiohm.app to learn more.', 'aiohm-kb-assistant'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=aiohm-license'); ?>" class="button button-secondary"><?php _e('Check License Status', 'aiohm-kb-assistant'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>