<?php
defined('ABSPATH') || exit;

// Core constants
if (!defined('AIOHM_PLUGIN_VERSION')) {
    define('AIOHM_PLUGIN_VERSION', '1.0.0');
}

if (!defined('AIOHM_PLUGIN_PATH')) {
    define('AIOHM_PLUGIN_PATH', plugin_dir_path(__FILE__) . '../');
}

if (!defined('AIOHM_PLUGIN_URL')) {
    define('AIOHM_PLUGIN_URL', plugin_dir_url(__FILE__) . '../');
}

// Default options key
if (!defined('AIOHM_OPTIONS_KEY')) {
    define('AIOHM_OPTIONS_KEY', 'aiohm_kb_assistant_options');
}
