<?php
/**
 * Admin Dashboard template.
 * This version includes the new mini sales page for the AIOHM Private tier.
 */
if (!defined('ABSPATH')) exit;

// Setup variables
$settings = AIOHM_KB_Assistant::get_settings();
$is_user_linked = !empty($settings['personal_api_key'] ?? '');

$default_tab = 'welcome';
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

$plans = get_user_meta(get_current_user_id(), '_aiohm_user_plans', true);
if (!is_array($plans)) {
    $plans = [];
}

function aiohm_dashboard_has_plan($slug, $plans_array) {
    foreach ($plans_array as $plan) {
        if (isset($plan['name']) && stripos($plan['name'], $slug) !== false) {
            return true;
        }
    }
    return false;
}

$has_tribe_plan = aiohm_dashboard_has_plan('Tribe', $plans);
$has_club_plan = aiohm_dashboard_has_plan('Club', $plans);
$has_private_plan = aiohm_dashboard_has_plan('Private', $plans);

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
        <?php if ($has_tribe_plan) : ?>
            <a href="?page=aiohm-dashboard&tab=tribe" class="nav-tab <?php echo $current_tab == 'tribe' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Brand Soul', 'aiohm-kb-assistant'); ?></a>
        <?php endif; ?>
        <?php if ($has_club_plan) : ?>
            <a href="?page=aiohm-dashboard&tab=club" class="nav-tab <?php echo $current_tab == 'club' ? 'nav-tab-active' : ''; ?>"><?php _e('Brand Assistant', 'aiohm-kb-assistant'); ?></a>
        <?php endif; ?>
        <?php if ($has_private_plan || true) : // Temporarily set to true to always show the tab for easy access. Change to just $has_private_plan when live. ?>
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

        <?php elseif ($current_tab === 'tribe' && $has_tribe_plan): ?>
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

        <?php elseif ($current_tab === 'club' && $has_club_plan): ?>
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

        <?php elseif ($current_tab === 'private'): ?>
            
            <section class="aiohm-private-sales">
              <div class="container">
                <h1 class="headline">AIOHM Private</h1>
                <p class="intro">A private channel for your most sacred work. Built for creators, guides, and visionaries who need more than general AI tools—they need intimacy, integrity, and invisible support.</p>

                <div class="benefits-grid">
                  <div class="benefit">
                    <h3>🔐 Full Privacy & Confidentiality</h3>
                    <p>Your content never leaves your WordPress site. All AI responses are generated within your protected space, using your private knowledge base only.</p>
                  </div>
                  <div class="benefit">
                    <h3>🧠 Personalized LLM Connection</h3>
                    <p>Connect to a private model endpoint via OpenRouter, Ollama, or your own infrastructure—so your AI assistant learns only from your truth, not the internet.</p>
                  </div>
                  <div class="benefit">
                    <h3>🪞 Private Scope Curation</h3>
                    <p>Create secret sources and unseen archives only available to your Private AI Assistant. Think: VIP content, retreat material, or sacred client casework.</p>
                  </div>
                  <div class="benefit">
                    <h3>🌐 Silent Intelligence</h3>
                    <p>Your assistant doesn’t just talk—it listens. The Private tier includes embedded prompts designed to mirror your tone, style, and intention with care.</p>
                  </div>
                </div>

                <div class="how-it-works">
                  <h2>How It Works</h2>
                  <ol>
                    <li><strong>Activate Private Membership</strong> – Join through your aiohm.app dashboard.</li>
                    <li><strong>Connect Your Private LLM</strong> – Use OpenRouter or your own local LLM instance.</li>
                    <li><strong>Define Your Secret Scope</strong> – Upload sensitive PDFs, hidden posts, or private notes.</li>
                    <li><strong>Use Private AI Chat</strong> – Ask questions, receive guidance, or generate client-facing insights—all inside WordPress.</li>
                  </ol>
                </div>

                <div class="cta">
                  <h2>Ready to Build in the Sacred?</h2>
                  <p>The Private tier is for creators who honor trust and nuance. If that’s you—</p>
                  <a href="https://www.aiohm.app/private" target="_blank" class="button button-primary">Join AIOHM Private</a>
                </div>
              </div>
            </section>

            <style>
                /* Remove admin page content margins to make it "full screen" */
                .aiohm-dashboard .aiohm-tab-content {
                    margin-left: -20px;
                    margin-top: 0;
                }
                .aiohm-private-sales { padding: 60px 30px; background: #f9f9f9; color: #2c2c2c; }
                .aiohm-private-sales .container { max-width: 900px; margin: 0 auto; }
                .aiohm-private-sales .headline { font-size: 36px; font-weight: bold; margin-top: 0; margin-bottom: 20px; text-align: center; }
                .aiohm-private-sales .intro { font-size: 18px; max-width: 700px; margin: 0 auto 40px auto; text-align: center; color: #555; }
                .benefits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 60px; }
                .benefit { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .benefit h3 { font-size: 20px; margin-top: 0; margin-bottom: 10px; }
                .how-it-works { text-align: center; margin-bottom: 60px; }
                .how-it-works h2 { font-size: 24px; margin-bottom: 20px; }
                .how-it-works ol { padding-left: 0; list-style: none; display: inline-block; text-align: left; }
                .how-it-works li { margin-bottom: 15px; padding-left: 25px; position: relative; }
                .how-it-works li::before { content: '✓'; color: #4CAF50; position: absolute; left: 0; font-weight: bold; }
                .cta { text-align: center; padding-top: 40px; border-top: 1px solid #e0e0e0; }
                .cta h2 { font-size: 28px; margin-bottom: 10px; }
                .cta .button { font-size: 18px; padding: 12px 30px; height: auto; }
                @media (max-width: 768px) {
                  .benefits-grid { grid-template-columns: 1fr; }
                }
            </style>
            <?php else: ?>
             <div class="aiohm-getting-started" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <p><?php _e('This tab requires an active membership. Please check your license status or visit aiohm.app to learn more.', 'aiohm-kb-assistant'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=aiohm-license'); ?>" class="button button-secondary"><?php _e('Check License Status', 'aiohm-kb-assistant'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>