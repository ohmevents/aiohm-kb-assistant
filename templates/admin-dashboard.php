<?php
/**
 * Admin Dashboard template.
 * Final version with a redesigned Welcome tab that matches the Tribe tab's box style,
 * a new header, dynamic content for the Tribe tab, and removal of redundant text.
 */
if (!defined('ABSPATH')) exit;

// --- Data Fetching and Status Checks ---
$default_tab = 'welcome';
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

// Check if user is connected by seeing if their AIOHM email is saved
$settings = AIOHM_KB_Assistant::get_settings();
$is_tribe_member_connected = !empty($settings['aiohm_app_email']);

// Check Club access using the PMPro helper function
$has_club_access = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();

// Check Private access (membership ID 12)
$has_private_access = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_private_access();
?>

<div class="wrap aiohm-dashboard">

    <div class="aiohm-header" style="text-align: left;">
        <h1 style="text-align: left;"><?php _e('AIOHM Assistant Dashboard', 'aiohm-kb-assistant'); ?></h1>
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
            <section class="aiohm-sales-page aiohm-welcome-tab">
                <div class="container">
                    <h2 class="headline"><?php _e('4 Steps to Turn Your Site Into a Living Knowledge Base', 'aiohm-kb-assistant'); ?></h2>
                    <div class="benefits-grid">
                        <div class="benefit">
                            <h3><?php _e('1. Root Your Presence', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('Connect your preferred AI provider. This is where your structure meets spirit. Add your API key from OpenAI, Claude, or Gemini to activate the intelligence behind your knowledge base.', 'aiohm-kb-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=aiohm-settings'); ?>" class="button button-primary"><?php _e('Open Settings', 'aiohm-kb-assistant'); ?></a>
                        </div>
                        <div class="benefit">
                            <h3><?php _e('2. Feed the Flame', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('Choose which content carries your essence. Curate pages, posts, and files that truly represent your mission. Not just information—transmission.', 'aiohm-kb-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=aiohm-scan-content'); ?>" class="button button-primary"><?php _e('Scan Content', 'aiohm-kb-assistant'); ?></a>
                        </div>
                        <div class="benefit">
                            <h3><?php _e('3. Clear the Channel', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('Refine your knowledge base for resonance. Review, edit, and release what no longer aligns. Shape your AI’s voice like a sacred text.', 'aiohm-kb-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-primary"><?php _e('Manage Knowledge', 'aiohm-kb-assistant'); ?></a>
                        </div>
                        <div class="benefit">
                            <h3><?php _e('4. Set Your Wisdom Free', 'aiohm-kb-assistant'); ?></h3>
                            <p><?php _e('Download your curated knowledge base and use it anywhere. Your brand’s soul—structured and portable for any platform that honors your voice.', 'aiohm-kb-assistant'); ?></p>
                             <a href="<?php echo admin_url('admin.php?page=aiohm-manage-kb'); ?>" class="button button-primary"><?php _e('Export Your KB', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif ($current_tab === 'tribe'): ?>
            <?php if ($is_tribe_member_connected): ?>
                <section class="aiohm-sales-page aiohm-tribe-connected">
                  <div class="container">
                    <h1 class="headline"><?php _e('Welcome to the Tribe', 'aiohm-kb-assistant'); ?></h1>
                    <p class="intro"><?php printf(__('Your account is connected via %s.', 'aiohm-kb-assistant'), '<strong>' . esc_html($settings['aiohm_app_email']) . '</strong>'); ?></p>
                    <div class="benefits-grid">
                      <div class="benefit">
                        <h3><?php _e('Your Next Step: The AI Brand Core', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('You now have access to the AI Brand Core questionnaire. This is where you define the heart of your brand, so your AI can learn to speak with your authentic voice. It\'s the most crucial step in creating an assistant that truly represents you.', 'aiohm-kb-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aiohm-brand-soul'); ?>" class="button button-primary"><?php _e('Go to my AI Brand Core', 'aiohm-kb-assistant'); ?></a>
                      </div>
                       <div class="benefit">
                        <h3><?php _e('Manage Your Profile', 'aiohm-kb-assistant'); ?></h3>
                        <p><?php _e('You can manage your AIOHM Tribe account, view your Brand Soul map, and explore other member resources directly on the AIOHM app website.', 'aiohm-kb-assistant'); ?></p>
                        <a href="https://www.aiohm.app/members/" target="_blank" class="button button-secondary"><?php _e('View My AIOHM Account', 'aiohm-kb-assistant'); ?></a>
                      </div>
                    </div>
                  </div>
                </section>
            <?php else: ?>
                <section class="aiohm-sales-page aiohm-tribe-sales">
                  <div class="container">
                    <div class="aiohm-settings-locked-overlay is-active">
                        <div class="lock-content">
                            <div class="lock-icon">🔒</div>
                            <h2><?php _e('Unlock Tribe Features', 'aiohm-kb-assistant'); ?></h2>
                            <p><?php _e('To access the AI Brand Core questionnaire, please connect your free AIOHM Tribe account.', 'aiohm-kb-assistant'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-license')); ?>" class="button button-primary"><?php _e('Connect Your Account', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                    <h1 class="headline" style="filter: blur(4px);"><?php _e('Join the AIOHM Tribe', 'aiohm-kb-assistant'); ?></h1>
                    <p class="intro" style="filter: blur(4px);"><?php _e('A sacred starting point for soulful entrepreneurs and creators. The Tribe is your free invitation to explore the deeper layers of brand resonance and personal AI alignment.', 'aiohm-kb-assistant'); ?></p>
                    <div class="benefits-grid" style="filter: blur(4px);">
                      <div class="benefit">
                          <h3><?php _e('Access the AI Brand Core', 'aiohm-kb-assistant'); ?></h3>
                          <p><?php _e('Join for free to unlock the AI Brand Core questionnaire. This is the foundation for teaching the AI your unique voice, mission, and brand essence.', 'aiohm-kb-assistant'); ?></p>
                      </div>
                      <div class="benefit">
                          <h3><?php _e('Knowledge Base Management', 'aiohm-kb-assistant'); ?></h3>
                          <p><?php _e('Upload, organize, and edit what your AI assistant learns. Teach it your content, your story, your sacred material.', 'aiohm-kb-assistant'); ?></p>
                      </div>
                    </div>
                  </div>
                </section>
            <?php endif; ?>

        <?php elseif ($current_tab === 'club'): ?>
            <section class="aiohm-sales-page aiohm-club-sales">
              <div class="container">
                <h1 class="headline">AIOHM Club</h1>
                <p class="intro">Designed for creators ready to bring depth and ease into their message. AIOHM Club gives you access to tools that think like you—so your voice leads the way.</p>
                <?php if (!$has_club_access) : // Lock content if no club access ?>
                    <div class="aiohm-settings-locked-overlay is-active">
                        <div class="lock-content">
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
              </div>
            </section>

        <?php elseif ($current_tab === 'private'): ?>
            <section class="aiohm-sales-page aiohm-private-sales">
              <div class="container">
                <h1 class="headline">AIOHM Private</h1>
                <p class="intro">A private channel for your most sacred work. Built for creators, guides, and visionaries who need more than general AI tools—they need intimacy, integrity, and invisible support.</p>
                <?php if (!$has_private_access) : ?>
                    <div class="aiohm-settings-locked-overlay is-active">
                        <div class="lock-content">
                            <div class="lock-icon">🔒</div>
                            <h2><?php _e('Unlock Private Features', 'aiohm-kb-assistant'); ?></h2>
                            <p><?php _e('Private features are available with an AIOHM Private membership (ID 12).', 'aiohm-kb-assistant'); ?></p>
                            <a href="https://www.aiohm.app/private" target="_blank" class="button button-primary"><?php _e('Explore Private', 'aiohm-kb-assistant'); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="benefits-grid <?php echo !$has_private_access ? 'is-locked' : ''; ?>">
                  <div class="benefit"><h3>🔐 Full Privacy & Confidentiality</h3><p>Your content never leaves your WordPress site. All AI responses are generated within your protected space.</p></div>
                  <div class="benefit"><h3>🧠 Personalized LLM Connection</h3><p>Connect to a private model endpoint so your AI assistant learns only from your truth, not the internet.</p></div>
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
    .aiohm-dashboard .aiohm-step h3,
    .aiohm-sales-page .headline, 
    .aiohm-sales-page .benefit h3, 
    .aiohm-sales-page .cta h2 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
        line-height: 1.4;
    }
    .aiohm-dashboard .aiohm-tagline,
    .aiohm-dashboard .aiohm-step p,
    .aiohm-sales-page {
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
     .aiohm-dashboard .button-primary {
        background-color: var(--ohm-primary);
        border-color: var(--ohm-dark-accent);
        color: #fff;
        font-family: var(--ohm-font-primary);
        font-weight: bold;
     }
     .aiohm-dashboard .button-primary:hover {
        background-color: var(--ohm-dark-accent);
        border-color: var(--ohm-dark-accent);
     }
    .aiohm-dashboard .nav-tab-wrapper { border-bottom-color: var(--ohm-light-accent); }
    .aiohm-dashboard .nav-tab { font-family: var(--ohm-font-primary); }
    .aiohm-dashboard .nav-tab-active { background-color: #f9f9f9; border-bottom-color: #f9f9f9; color: var(--ohm-primary); font-weight: bold; }

    /* Tab Content Layout */
    .aiohm-dashboard .aiohm-tab-content { margin-top: 20px; }
    
    /* Sales Page & Locking Mechanism */
    .aiohm-sales-page { 
        position: relative;
        padding: 40px 0; 
        background: #fdfdfd; 
        margin-left: -20px; /* Make full-width */
    }
    .aiohm-sales-page .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .aiohm-sales-page .headline { font-size: 36px; text-align: center; font-weight: bold; margin-bottom: 20px; }
    .aiohm-sales-page .intro { font-size: 18px; max-width: 700px; margin: 0 auto 40px auto; text-align: center; color: #555; }
    .aiohm-sales-page .benefits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 60px; }
    .aiohm-sales-page .benefit { background: #fff; padding: 25px; border: 1px solid var(--ohm-light-bg); border-left: 4px solid var(--ohm-light-accent); border-radius: 8px; display: flex; flex-direction: column; }
    .aiohm-sales-page .benefit h3 { font-size: 20px; margin-top: 0; margin-bottom: 10px; }
    .aiohm-sales-page .benefit p { flex-grow: 1; }
    .aiohm-sales-page .benefit .button { margin-top: auto; align-self: flex-start; }
    .aiohm-sales-page .cta { text-align: center; padding-top: 40px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-sales-page .cta h2 { font-size: 28px; margin-bottom: 10px; }
    .aiohm-sales-page .cta .button-primary { font-size: 18px; padding: 12px 30px; height: auto; }
    
    .benefits-grid.is-locked { 
        filter: blur(4px);
        opacity: 0.5;
        pointer-events: none;
    }
    .aiohm-settings-locked-overlay.is-active { 
        position: absolute; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(249, 249, 249, 0.85); 
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
    .aiohm-settings-locked-overlay .lock-icon { font-size: 4em; color: var(--ohm-primary); margin-bottom: 15px; }
    
    @media (max-width: 768px) {
      .aiohm-sales-page .benefits-grid { grid-template-columns: 1fr; }
    }
</style>