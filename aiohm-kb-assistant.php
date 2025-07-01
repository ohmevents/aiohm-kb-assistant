<?php
/*
Plugin Name: AIOHM KB Assistant
Description: Trains your AI assistant on your website content, uploads, and brand data.
Version: 1.0
Author: AIOHM
*/

defined('ABSPATH') || exit;

// Include core modules
require_once plugin_dir_path(__FILE__) . 'includes/core-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';

// Load other modules
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-chat.php';
require_once plugin_dir_path(__FILE__) . 'includes/crawler-site.php';
require_once plugin_dir_path(__FILE__) . 'includes/crawler-uploads.php';
require_once plugin_dir_path(__FILE__) . 'includes/rag-engine.php';
require_once plugin_dir_path(__FILE__) . 'includes/ai-gpt-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend-widget.php';
require_once plugin_dir_path(__FILE__) . 'includes/pro-check.php';

// Add top-level admin menu
add_action('admin_menu', 'aiohm_kb_assistant_add_menu');

function aiohm_kb_assistant_add_menu() {
    add_menu_page(
        'AIOHM Assistant Settings',        // Page title
        'AIOHM Assistant',                 // Menu title
        'manage_options',                 // Capability
        'aiohm-kb-assistant',              // Menu slug
        'aiohm_kb_assistant_settings_page', // Callback function
        'dashicons-format-chat',          // Menu icon
        60                                // Menu position
    );
}

// Add Settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aiohm_kb_assistant_add_settings_link');

function aiohm_kb_assistant_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=aiohm-kb-assistant">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
?>
