<?php
defined('ABSPATH') || exit;

function aiohm_kb_chat_shortcode($atts) {
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/chat-box.php';
    return ob_get_clean();
}
add_shortcode('aiohm_chat', 'aiohm_kb_chat_shortcode');
