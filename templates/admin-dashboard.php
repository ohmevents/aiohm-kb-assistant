<?php
/**
 * Admin Dashboard template.
 * This version redesigns the Welcome tab with the new 4-box layout.
 */
if (!defined('ABSPATH')) exit;

// --- Data Fetching and Status Checks ---
$default_tab = 'welcome';
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

// Check Club access using the new PMPro helper function
$has_club_access = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();
?>

<div class="wrap aiohm-dashboard">

    <div class="aiohm-header" style="text-align: center;">
        <h1 style="text-align: center;"><?php _e('AIOHM Assistant Dashboard', 'aiohm-kb-assistant'); ?></h1>
        <p class="aiohm-tagline" style="margin-left: auto; margin-right: auto;"><?php _e("Welcome! Let's turn your content into an expert AI assistant.", 'aiohm-kb-assistant'); ?></p>
    </div>

    <nav class="nav-tab-wrapper">
        <a href="?page=aiohm-dashboard&tab=welcome" class="nav-tab <?php echo $current_tab == 'welcome' ? 'nav-tab-active' : ''; ?>"><?php _e('Welcome', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=tribe" class="nav-tab <?php echo $current_tab == 'tribe' ? 'nav-tab-active' : ''; ?>"><?php _e('AIOHM Tribe', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=club" class="nav-tab <?php echo $current_tab == 'club' ? 'nav-tab-active' : ''; ?>"><?php _e('AIOHM Club', 'aiohm-kb-assistant'); ?></a>
        <a href="?page=aiohm-dashboard&tab=private" class="nav-tab <?php echo $current_tab == 'private' ? 'nav-tab-active' : ''; ?>"><?php _e('AIOHM Private', 'aiohm-kb-assistant'); ?></a>
    </nav>

    <div class="aiohm-tab-content">

        <?php if ($current_tab === 'welcome'): ?>
            <div class="aiohm-getting-started">
                 <h2 style="text-align: center;"><?php _e('4 Steps to Turn Your Site Into a Living Knowledge Base', 'aiohm-kb-assistant'); ?></h2>
                <div class="aiohm-steps">
                    <div class="aiohm-step">
                        <h3><?php _e('1. Root Your Presence', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Connect your preferred AI provider with AIOHM.<br><br>This is where your structure meets spirit. Add your API key from OpenAI, Claude, or Gemini to activate the intelligence behind your knowledge base. You’re not just powering a system - you’re preparing your voice to be heard clearly.', 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-secondary"><?php _e('→ [Open Settings]', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step">
                        <h3><?php _e('2. Feed the Flame', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Choose which content carries your essence.<br><br>Curate pages and posts that truly represent your mission. Not just information—transmission.', 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-scan-content'); ?>" class="button button-secondary"><?php _e('→ [Go to Scan Page]', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step">
                        <h3><?php _e('3. Clear the Channel', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Refine your knowledge base for resonance.<br><br>Review, edit, and release what no longer aligns. Shape your AI’s voice like a sacred text.', 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-secondary"><?php _e('→ [Manage Knowledge]', 'aiohm-kb-assistant'); ?></a>
                    </div>
                    <div class="aiohm-step">
                        <h3><?php _e('4. Set Your Wisdom Free', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('Download your curated knowledge base and use it anywhere.<br><br>Your brand’s soul—structured and portable. Use it with any chatbot or platform that honors your voice.', 'aiohm-kb-assistant'); ?></p>
                         <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-secondary"><?php _e('→ [Export KB]', 'aiohm-kb-assistant'); ?></a>
                    </div>
                </div>
            </div>

        <?php elseif ($current_tab === 'tribe'): ?>
            <section class="aiohm-sales-page aiohm-tribe-sales">
              <div class="container">
                <h1 class="headline">Join the AIOHM Tribe</h1>
                <p class="intro">A sacred starting point for soulful entrepreneurs and creators. The Tribe is your free invitation to explore the deeper layers of brand resonance and personal AI alignment.</p>
                <div class="benefits-grid">
                  <div class="benefit"><h3>🌱 Free for Life</h3><p>Start your AIOHM journey with zero cost. No credit card. No pressure. Just your voice and your vision.</p></div>
                  <div class="benefit"><h3>🧬 Brand Soul Questionnaire</h3><p>Access the reflective, soulful prompts that help define your tone, mission, and essence—fueling your personal AI with truth, not trends.</p></div>
                  <div class="benefit"><h3>📚 Knowledge Base Management</h3><p>Upload, organize, and edit what your AI assistant learns. Teach it your content, your story, your sacred material.</p></div>
                  <div class="benefit"><h3>🛠️ Plugin Integration</h3><p>Connect the AIOHM WordPress plugin to unlock features inside your site—starting with your Tribe access key and Brand Soul profile.</p></div>
                </div>
                <div class="cta">
                  <h2>You're invited to begin.</h2>
                  <p>Join the Tribe and let your voice be the foundation of everything that follows.</p>
                  <a href="https://www.aiohm.app/register" target="_blank" class="button button-primary">Register Free</a>
                </div>
              </div>
            </section>

        <?php elseif ($current_tab === 'club'): ?>
            <section class="aiohm-sales-page aiohm-club-sales">
              <div class="container">
                <h1 class="headline">AIOHM Club</h1>
                <p class="intro">Designed for creators ready to bring depth and ease into their message. AIOHM Club gives you access to tools that think like you—so your voice leads the way.</p>
                <?php if (!$has_club_access) : // Lock content if no club access ?>
                    <div class="aiohm-settings-locked-overlay" style="position: relative; padding: 40px; margin-bottom: 40px;">
                        <div class="lock-content" style="background: none; box-shadow: none; border: none;">
                            <div class="lock-icon">🔒</div>
                            <h2><?php _e('Unlock Club Features', 'aiohm-kb-assistant'); ?></h2>
                            <p><?php _e('Join the AIOHM Club to access Mirror Mode (Q&A Chatbot) and Muse Mode (Brand Assistant).', 'aiohm-kb-assistant'); ?></p>
                            <a href="https://www.aiohm.app/club" target="_blank" class="button button-primary"><?php _e('Join AIOHM Club', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="benefits-grid <?php echo !$has_club_access ? 'is-locked' : ''; ?>">
                  <div class="benefit"><h3>✨ Mirror Mode (Q&A Chatbot)</h3><p>A sacred space to reflect on your brand. Ask questions. Hear your truth echoed back through the Mirror—powered by your Brand Soul and knowledge base.</p></div>
                  <div class="benefit"><h3>🎨 Muse Mode (Brand Assistant)</h3><p>Create content that feels like you wrote it on your best day. Muse Mode understands your tone, your offers, your audience—and helps shape captions, emails, and ideas.</p></div>
                </div>
                <div class="cta">
                  <h2>Your voice deserves ease.</h2>
                  <p>When you're ready to stop sounding like everyone else, the Club is here.</p>
                  <a href="https://www.aiohm.app/club" class="button button-primary">Join AIOHM Club</a>
                </div>
              </div>
            </section>

        <?php elseif ($current_tab === 'private'): ?>
            <section class="aiohm-sales-page aiohm-private-sales">
              <div class="container">
                <h1 class="headline">AIOHM Private</h1>
                <p class="intro">A private channel for your most sacred work. Built for creators, guides, and visionaries who need more than general AI tools—they need intimacy, integrity, and invisible support.</p>
                <?php if (!$has_club_access) : // Private plan implies club access, so use club access check ?>
                    <div class="aiohm-settings-locked-overlay" style="position: relative; padding: 40px; margin-bottom: 40px;">
                        <div class="lock-content" style="background: none; box-shadow: none; border: none;">
                            <div class="lock-icon">🔒</div>
                            <h2><?php _e('Unlock Private Features', 'aiohm-kb-assistant'); ?></h2>
                            <p><?php _e('Private features are available with an AIOHM Private membership.', 'aiohm-kb-assistant'); ?></p>
                            <a href="https://www.aiohm.app/private" target="_blank" class="button button-primary"><?php _e('Explore Private', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="benefits-grid <?php echo !$has_club_access ? 'is-locked' : ''; ?>">
                  <div class="benefit"><h3>🔐 Full Privacy & Confidentiality</h3><p>Your content never leaves your WordPress site. All AI responses are generated within your protected space.</p></div>
                  <div class="benefit"><h3>🧠 Personalized LLM Connection</h3><p>Connect to a private model endpoint so your AI assistant learns only from your truth, not the internet.</p></div>
                </div>
                <div class="cta">
                  <a href="https://www.aiohm.app/private" class="button button-primary">Join AIOHM Private</a>
                </div>
              </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<style>
    /* OHM Brand Identity */
    .aiohm-dashboard {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-muted-accent: #7d9b76;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', 'Montserrat Alternates', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }

    /* Global Dashboard Styles */
    .aiohm-dashboard .aiohm-header h1,
    .aiohm-dashboard h2,
    .aiohm-dashboard .aiohm-step h3 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
    }
    .aiohm-dashboard .aiohm-tagline,
    .aiohm-dashboard .aiohm-step p {
        font-family: var(--ohm-font-secondary);
        color: var(--ohm-dark);
    }
    .aiohm-dashboard .button-secondary {
        background-color: var(--ohm-light-bg);
        border-color: var(--ohm-muted-accent);
        color: var(--ohm-dark-accent);
        font-family: var(--ohm-font-primary);
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
     .aiohm-dashboard .button-secondary:hover {
        background-color: var(--ohm-light-accent);
        border-color: var(--ohm-primary);
        color: var(--ohm-dark-accent);
     }
    .aiohm-dashboard .nav-tab-wrapper {
        border-bottom-color: var(--ohm-light-accent);
    }
    .aiohm-dashboard .nav-tab {
        font-family: var(--ohm-font-primary);
    }
    .aiohm-dashboard .nav-tab-active {
        background-color: #f9f9f9;
        border-bottom-color: #f9f9f9;
        color: var(--ohm-primary);
        font-weight: bold;
    }

    /* Tab Content Layout */
    .aiohm-dashboard .aiohm-tab-content { margin-top: 20px; }
    .aiohm-getting-started .aiohm-steps { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .aiohm-getting-started .aiohm-step { background: #fff; padding: 25px; border: 1px solid var(--ohm-light-bg); border-left: 4px solid var(--ohm-primary); border-radius: 4px; display: flex; flex-direction: column;}
    .aiohm-getting-started .aiohm-step p { flex-grow: 1; }
    .aiohm-getting-started .aiohm-step .button { margin-top: auto; }

    /* Sales Page General Styles */
    .aiohm-sales-page { 
        padding: 40px 0; 
        background: #fdfdfd; 
        color: var(--ohm-dark); 
        font-family: var(--ohm-font-secondary);
        margin-left: -20px; /* Make full-width */
    }
    .aiohm-sales-page .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .aiohm-sales-page .headline, 
    .aiohm-sales-page .benefit h3, 
    .aiohm-sales-page .how-it-works h2, 
    .aiohm-sales-page .cta h2 { 
        font-family: var(--ohm-font-primary); 
        color: var(--ohm-dark-accent); 
    }
    .aiohm-sales-page .headline { font-size: 36px; text-align: center; font-weight: bold; margin-bottom: 20px; }
    .aiohm-sales-page .intro { font-size: 18px; max-width: 700px; margin: 0 auto 40px auto; text-align: center; color: #555; }
    .aiohm-sales-page .benefits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 60px; }
    .aiohm-sales-page .benefit { background: #fff; padding: 25px; border: 1px solid var(--ohm-light-bg); border-left: 4px solid var(--ohm-light-accent); border-radius: 8px; }
    .aiohm-sales-page .benefit h3 { font-size: 20px; margin-top: 0; margin-bottom: 10px; }
    .aiohm-sales-page .how-it-works { text-align: center; margin-bottom: 60px; }
    .aiohm-sales-page .how-it-works h2 { font-size: 24px; margin-bottom: 20px; }
    .aiohm-sales-page .how-it-works ol { padding-left: 0; list-style: none; display: inline-block; text-align: left; }
    .aiohm-sales-page .how-it-works li { margin-bottom: 15px; padding-left: 25px; position: relative; }
    .aiohm-sales-page .how-it-works li::before { content: '✓'; color: var(--ohm-primary); position: absolute; left: 0; font-weight: bold; }
    .aiohm-sales-page .cta { text-align: center; padding-top: 40px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-sales-page .cta h2 { font-size: 28px; margin-bottom: 10px; }
    .aiohm-sales-page .cta .button-primary { 
        font-size: 18px; 
        padding: 12px 30px; 
        height: auto; 
        background-color: var(--ohm-primary); 
        border-color: var(--ohm-dark-accent);
    }
    .aiohm-sales-page .cta .button-primary:hover {
        background-color: var(--ohm-dark-accent);
        border-color: var(--ohm-dark-accent);
    }
    .aiohm-sales-page .benefits-grid.is-locked {
        opacity: 0.5;
        pointer-events: none;
    }
    .aiohm-settings-locked-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(235, 235, 235, 0.8);
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        border-radius: 8px;
    }
    .aiohm-settings-locked-overlay .lock-content {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        max-width: 400px;
        width: 100%;
    }
    .aiohm-settings-locked-overlay .lock-icon {
        font-size: 4em;
        color: var(--ohm-primary);
        margin-bottom: 15px;
    }
    @media (max-width: 768px) {
      .aiohm-sales-page .benefits-grid, .aiohm-getting-started .aiohm-steps { grid-template-columns: 1fr; }
    }
</style>