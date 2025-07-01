<?php
/*
Plugin Name: AIOHM Knowledge Assistant
Description: Modular plugin to scan, manage, and embed knowledge from site and uploads.
Version: 0.2
Author: AIOHM
*/

// Core initialization
require_once plugin_dir_path(__FILE__) . 'includes/core-init.php';

// Admin settings page
require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';

// Website crawler module
require_once plugin_dir_path(__FILE__) . 'includes/crawler-site.php';

// Uploads folder scanner
require_once plugin_dir_path(__FILE__) . 'includes/crawler-uploads.php';

// GPT & Claude integration
require_once plugin_dir_path(__FILE__) . 'includes/ai-gpt-client.php';

// Embedding engine
require_once plugin_dir_path(__FILE__) . 'includes/rag-engine.php';

// Shortcode chat UI
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-chat.php';

// Frontend widget assets
require_once plugin_dir_path(__FILE__) . 'includes/frontend-widget.php';

// Knowledge Base Manager UI
require_once plugin_dir_path(__FILE__) . 'includes/aiohm-kb-manager.php';

// Chat box template (optional display components)
require_once plugin_dir_path(__FILE__) . 'includes/chat-box.php';

// Optional semantic search shortcode
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-search.php';
