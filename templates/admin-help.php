<?php
/**
 * Admin Help page template.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Get Help & Resources', 'aiohm-kb-assistant'); ?></h1>
    <p class="description"><?php _e('Find support, community, and learning materials to make the most of your AIOHM Knowledge Assistant.', 'aiohm-kb-assistant'); ?></p>

    <div class="aiohm-help-grid">

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-headset"></span>
            </div>
            <h3><?php _e('Support Center', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Our experienced support team is ready to resolve your issues any time.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/support" target="_blank" class="button button-primary"><?php _e('Visit Support', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <h3><?php _e('Join the Community', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Join our Facebook group to get 20% discount coupon on premium products. Follow us to get more exciting offers.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://www.facebook.com/groups/aiohm" target="_blank" class="button button-secondary"><?php _e('Join Group', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-video-alt3"></span>
            </div>
            <h3><?php _e('Video Tutorials', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Learn the step by step process for developing your site easily from video tutorials.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/tutorials" target="_blank" class="button button-secondary"><?php _e('Watch Tutorials', 'aiohm-kb-assistant'); ?></a>
        </div>

        <div class="aiohm-help-card">
            <div class="aiohm-card-icon">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <h3><?php _e('Request a Feature', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Have any special feature in mind? Let us know through the feature request.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/feature-request" target="_blank" class="button button-secondary"><?php _e('Submit Request', 'aiohm-kb-assistant'); ?></a>
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
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <h3><?php _e('Public Roadmap', 'aiohm-kb-assistant'); ?></h3>
            <p><?php _e('Check our upcoming new features, detailed development stories and tasks.', 'aiohm-kb-assistant'); ?></p>
            <a href="https://aiohm.app/roadmap" target="_blank" class="button button-secondary"><?php _e('View Roadmap', 'aiohm-kb-assistant'); ?></a>
        </div>

    </div></div><style>
    .aiohm-help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .aiohm-help-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease-in-out;
    }
    .aiohm-help-card:hover {
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .aiohm-card-icon {
        font-size: 48px;
        line-height: 1;
        color: #007cba; /* Primary AIOHM color */
        margin-bottom: 15px;
    }
    .aiohm-help-card h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 1.3em;
        color: #333;
    }
    .aiohm-help-card p {
        font-size: 0.9em;
        color: #666;
        margin-bottom: 20px;
        flex-grow: 1; /* Allows paragraph to take up space */
    }
    .aiohm-help-card .button {
        width: 100%; /* Make buttons full width within the card */
        padding: 10px 15px;
        font-size: 1em;
        line-height: 1.2;
    }
    .aiohm-help-card .button-primary {
        background-color: #007cba;
        border-color: #007cba;
        color: #fff;
    }
    .aiohm-help-card .button-primary:hover {
        background-color: #005a87;
        border-color: #005a87;
    }
    .aiohm-help-card .button-secondary {
        background-color: #f0f0f0;
        border-color: #ccc;
        color: #333;
    }
    .aiohm-help-card .button-secondary:hover {
        background-color: #e0e0e0;
        border-color: #bbb;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .aiohm-help-grid {
            grid-template-columns: 1fr; /* Single column on smaller screens */
        }
    }
</style>