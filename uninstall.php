<?php
/**
 * Uninstall AIOHM Knowledge Assistant
 * 
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It removes all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check
if (!current_user_can('activate_plugins')) {
    exit;
}

// Remove plugin options
delete_option('aiohm_kb_settings');
delete_option('aiohm_kb_version');

// Remove plugin database tables
global $wpdb;

$tables_to_drop = array(
    $wpdb->prefix . 'aiohm_knowledge_base',
    $wpdb->prefix . 'aiohm_conversations',
    $wpdb->prefix . 'aiohm_messages',
    $wpdb->prefix . 'aiohm_projects',
    $wpdb->prefix . 'aiohm_project_notes',
    $wpdb->prefix . 'aiohm_usage_tracking'
);

foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Remove scheduled hooks
wp_clear_scheduled_hook('aiohm_scheduled_scan');

// Remove user meta data
delete_metadata('user', 0, 'aiohm_user_settings', '', true);

// Clear any cached data
wp_cache_flush();