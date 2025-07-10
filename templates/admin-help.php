<?php
/**
 * Admin Help page template - Redesigned to be a branded, step-by-step user journey guide.
 * Layout is now a 2x2 grid with a separate row for support links.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aiohm-help-page">
    <h1><?php _e('Your AIOHM Journey', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('From initial setup to a fully realized AI assistant, this is your path to bringing your brand\'s soul to life.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-journey-grid">

        <div class="aiohm-journey-card">
            <div class="aiohm-card-icon"><span class="dashicons dashicons-admin-settings"></span></div>
            <h3><?php _e('Phase 1: Foundation & Onboarding', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Establish the connection between your website and the AIOHM ecosystem. This is the starting point for every user.', 'aiohm-kb-assistant'); ?></p>
            <ul class="feature-list">
                <li><strong>Connect Your Accounts:</strong> Link your free AIOHM Tribe account and your preferred AI provider (OpenAI, etc.) on the settings pages.</li>
                <li><strong>Unlock Core Features:</strong> Connecting your Tribe account is the key to unlocking the AI Brand Core questionnaire.</li>
            </ul>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-settings')); ?>" class="button button-secondary"><?php _e('Go to Settings', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-journey-card">
            <div class="aiohm-card-icon"><span class="dashicons dashicons-edit-page"></span></div>
            <h3><?php _e('Phase 2: Defining Your Brand\'s Voice', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('This is the most important phase for creating an AI that is truly yours. Teach the AI your "why" and how you wish to sound.', 'aiohm-kb-assistant'); ?></p>
            <ul class="feature-list">
                <li><strong>AI Brand Core:</strong> Use the guided questionnaire to define your brand\'s values, mission, and unique voice.</li>
                <li><strong>Add to Private KB:</strong> Save your answers to your private knowledge base, making them available to your personal Brand Assistant.</li>
            </ul>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-brand-soul')); ?>" class="button button-secondary"><?php _e('Define Your Brand Core', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-journey-card">
            <div class="aiohm-card-icon"><span class="dashicons dashicons-database-add"></span></div>
            <h3><?php _e('Phase 3: Building the Knowledge Base', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('With your brand\'s voice defined, you can now teach the AI what to talk about by feeding it your content.', 'aiohm-kb-assistant'); ?></p>
            <ul class="feature-list">
                <li><strong>Scan Content:</strong> Automatically scan your posts, pages, and media files to add them to the AI's knowledge.</li>
                <li><strong>Manage Knowledge:</strong> Review, edit, and manage all learned content. Toggle visibility between public (for chatbots) and private (for your eyes only).</li>
            </ul>
            <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-scan-content')); ?>" class="button button-secondary"><?php _e('Manage Knowledge Base', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-journey-card">
            <div class="aiohm-card-icon"><span class="dashicons dashicons-format-chat"></span></div>
            <h3><?php _e('Phase 4: Deployment & Interaction', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Deploy your soulful AI assistant on your site for your users (or yourself) to interact with. (Requires AIOHM Club)', 'aiohm-kb-assistant'); ?></p>
            <ul class="feature-list">
                <li><strong>Q&A Chatbot:</strong> Use the `[aiohm_chat]` shortcode to deploy a public-facing chatbot that answers questions based on your public knowledge base.</li>
                <li><strong>AI Brand Assistant:</strong> Use a private assistant that accesses your Brand Core answers to help you brainstorm and create content in your authentic voice.</li>
            </ul>
            <a href="https://www.aiohm.app/club" target="_blank" class="button button-primary"><?php _e('Explore Club Features', 'aiohm-kb-assistant'); ?></a>
        </div>
    </div>

    <hr class="aiohm-divider">

    <div class="aiohm-help-grid">
        <div class="aiohm-help-card">
            <div class="aiohm-card-icon-small">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <h3><?php _e('Support with Heart', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Feeling stuck or unsure? Our team honors your vision and is here to help—gently and clearly.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.aiohm.app/contact/" target="_blank" class="button button-primary"><?php _e('→ Reach Out to Support', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon-small">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <h3><?php _e('Documentation', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Detailed documentation to help you understand the functionality of each feature.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/docs" target="_blank" class="button button-secondary"><?php _e('Read Docs', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon-small">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <h3><?php _e('Request a Feature', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Have any special feature in mind? Let us know through the feature request.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.aiohm.app/contact/" target="_blank" class="button button-secondary"><?php _e('Submit Request', 'aiohm-kb-assistant'); ?></a>
        </div>
    </div>
</div>

<style>
    /* OHM Brand Identity */
    .aiohm-help-page {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-muted-accent: #7d9b76;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', 'Montserrat Alternates', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    
    /* General Typography & Colors */
    .aiohm-help-page h1,
    .aiohm-help-page h3 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
    }
    .aiohm-help-page .page-description,
    .aiohm-help-page p,
    .aiohm-help-page .feature-list {
        font-family: var(--ohm-font-secondary);
        color: var(--ohm-dark);
    }
    .aiohm-help-page .page-description {
        font-size: 1.1em;
        padding-bottom: 1em;
        border-bottom: 1px solid var(--ohm-light-bg);
    }

    /* Grid and Card Styles */
    .aiohm-journey-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    @media (min-width: 960px) {
        .aiohm-journey-grid {
             grid-template-columns: 1fr 1fr;
        }
    }

    .aiohm-journey-card {
        background: #fff;
        border: 1px solid var(--ohm-light-bg);
        border-left: 4px solid var(--ohm-light-accent);
        border-radius: 4px;
        padding: 25px;
        display: flex;
        flex-direction: column;
    }
    .aiohm-journey-card:hover {
        border-left-color: var(--ohm-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .aiohm-card-icon {
        font-size: 48px;
        line-height: 1;
        color: var(--ohm-primary);
        margin-bottom: 15px;
    }
    .aiohm-journey-card p {
        flex-grow: 1;
        margin-bottom: 15px;
    }
    .aiohm-journey-card .feature-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 20px;
        flex-grow: 1;
    }
    .aiohm-journey-card .feature-list li {
        padding-left: 20px;
        position: relative;
        margin-bottom: 8px;
    }
    .aiohm-journey-card .feature-list li::before {
        content: '✓';
        color: var(--ohm-primary);
        position: absolute;
        left: 0;
        font-weight: bold;
    }
    .aiohm-journey-card .button {
        width: 100%;
        padding: 10px 15px;
        font-size: 1em;
        line-height: 1.2;
        font-family: var(--ohm-font-primary);
        font-weight: bold;
        text-transform: uppercase;
        margin-top: auto; /* Pushes button to the bottom */
    }

    /* Divider */
    .aiohm-divider {
        margin: 40px auto;
        border: 0;
        height: 1px;
        background-color: var(--ohm-light-bg);
        width: 80%;
    }

    /* Smaller Help Cards */
    .aiohm-help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .aiohm-help-card {
        background: #fdfdfd;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 25px;
        text-align: center;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
    }
    .aiohm-help-card:hover {
        border-color: var(--ohm-light-bg);
        transform: translateY(-2px);
    }
    .aiohm-card-icon-small {
        font-size: 32px;
        line-height: 1;
        color: var(--ohm-muted-accent);
        margin-bottom: 15px;
    }
    .aiohm-help-card p {
        flex-grow: 1;
    }
     .aiohm-help-card .button {
        margin-top: auto;
     }

    /* Button Styles */
    .aiohm-help-page .button-primary {
        background-color: var(--ohm-primary);
        border-color: var(--ohm-dark-accent);
        color: #fff;
    }
    .aiohm-help-page .button-primary:hover {
        background-color: var(--ohm-dark-accent);
        border-color: var(--ohm-dark-accent);
    }
    .aiohm-help-page .button-secondary {
        background-color: transparent;
        border: 2px solid var(--ohm-muted-accent);
        color: var(--ohm-dark-accent);
    }
    .aiohm-help-page .button-secondary:hover {
        border-color: var(--ohm-primary);
        background-color: var(--ohm-light-accent);
    }
</style>