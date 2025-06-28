<?php
/**
 * Plugin Name: AIOHM KB Assistant
 * Description: A brand-trained AI assistant powered by your WordPress content.
 * Version: 0.1
 * Author: Your Name
 */

defined('ABSPATH') or die('No script kiddies please!');

// Admin menu
add_action('admin_menu', 'aiohm_kb_menu');

function aiohm_kb_menu() {
    add_menu_page('AIOHM KB', 'AIOHM Assistant', 'manage_options', 'aiohm-kb', 'aiohm_kb_settings');
}

function aiohm_kb_settings() {
    echo '<h1>AIOHM KB Assistant Settings</h1>';
}
