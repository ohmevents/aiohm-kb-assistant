<?php
defined('ABSPATH') || exit;

/**
 * Check if Pro version is active.
 * In the future, you can link this with a license manager or paid API key.
 */
function aiohm_kb_is_pro_user() {
    $is_pro = get_option('aiohm_kb_pro_enabled', false);
    return apply_filters('aiohm_kb_is_pro_user', (bool) $is_pro);
}

/**
 * Show upgrade notice in admin if not Pro
 */
function aiohm_kb_show_pro_notice() {
    if (aiohm_kb_is_pro_user()) return;

    echo '<div class="notice notice-warning is-dismissible">
        <p><strong>Aiohm Assistant:</strong> Unlock training on PDFs, images, and advanced models by <a href="https://aiohm.app/pro" target="_blank">upgrading to Pro</a>.</p>
    </div>';
}
add_action('admin_notices', 'aiohm_kb_show_pro_notice');
