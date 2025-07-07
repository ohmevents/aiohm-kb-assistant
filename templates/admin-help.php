<?php
/**
 * Admin Help page template - Redesigned and Branded.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aiohm-help-page">
    <h1><?php _e('Get Help & Resources', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Find support, documentation, and a space to share your ideas.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-help-grid">

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <h3><?php _e('Support with Heart', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Feeling stuck or unsure? Our team honors your vision and is here to help—gently and clearly.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.aiohm.app/contact/" target="_blank" class="button button-primary"><?php _e('→ Reach Out to Support', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <h3><?php _e('Documentation', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Detailed documentation to help you understand the functionality of each feature.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/docs" target="_blank" class="button button-secondary"><?php _e('Read Docs', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
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
    .aiohm-help-page p {
        font-family: var(--ohm-font-secondary);
        color: var(--ohm-dark);
    }
    .aiohm-help-page .page-description {
        font-size: 1.1em;
        padding-bottom: 1em;
        border-bottom: 1px solid var(--ohm-light-bg);
    }

    /* Grid and Card Styles */
    .aiohm-help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .aiohm-help-card {
        background: #fff;
        border: 1px solid var(--ohm-light-bg);
        border-left: 4px solid var(--ohm-light-accent);
        border-radius: 4px;
        padding: 25px;
        text-align: center;
        display: flex;
        flex-direction: column;
    }
    .aiohm-help-card:hover {
        border-left-color: var(--ohm-primary);
    }
    .aiohm-card-icon {
        font-size: 48px;
        line-height: 1;
        color: var(--ohm-primary);
        margin-bottom: 15px;
    }
    .aiohm-help-card p {
        flex-grow: 1; /* Pushes button to the bottom */
        margin-bottom: 20px;
    }
    .aiohm-help-card .button {
        width: 100%;
        padding: 10px 15px;
        font-size: 1em;
        line-height: 1.2;
        font-family: var(--ohm-font-primary);
        font-weight: bold;
        text-transform: uppercase;
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