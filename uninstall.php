<?php
// Exit if accessed directly or uninstall not triggered
defined('WP_UNINSTALL_PLUGIN') || exit;

// Delete plugin options
delete_option('aiohm_kb_api_key');
delete_option('aiohm_kb_pro_enabled');

// Delete any transients, user meta, or custom db tables (if added)
// Example:
// delete_transient('aiohm_cached_vector_data');
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}aiohm_vectors");

// Done
