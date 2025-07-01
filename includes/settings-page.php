<?php
// includes/settings-page.php

function aiohm_register_settings_page() {
    add_menu_page(
        'OHM AI Settings',
        'OHM AI Settings',
        'manage_options',
        'aiohm-settings',
        'aiohm_render_settings_page',
        'dashicons-admin-generic'
    );

    add_submenu_page(
        'aiohm-settings',
        'Scan Website',
        'Scan Website',
        'manage_options',
        'aiohm-scan-website',
        'aiohm_render_scan_page'
    );
}
add_action('admin_menu', 'aiohm_register_settings_page');

function aiohm_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>OHM AI Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('aiohm_settings_group');
            do_settings_sections('aiohm-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function aiohm_render_scan_page() {
    if (isset($_POST['aiohm_trigger_scan'])) {
        // Placeholder for scan trigger
        echo '<div class="notice notice-success"><p>Website scan initiated...</p></div>';
        do_action('aiohm_trigger_site_scan');
    }
    ?>
    <div class="wrap">
        <h1>Scan Website Content</h1>
        <form method="post">
            <p>This will crawl all public pages, posts, and menus for AI training.</p>
            <input type="submit" name="aiohm_trigger_scan" class="button-primary" value="Start Scan">
        </form>
    </div>
    <?php
}

function aiohm_register_settings() {
    register_setting('aiohm_settings_group', 'aiohm_openai_key');
    register_setting('aiohm_settings_group', 'aiohm_claude_key');
    register_setting('aiohm_settings_group', 'aiohm_scan_website');
    register_setting('aiohm_settings_group', 'aiohm_scan_uploads');
    register_setting('aiohm_settings_group', 'aiohm_use_semantic');
    register_setting('aiohm_settings_group', 'aiohm_vector_db');

    add_settings_section('aiohm_main_section', 'AI Configuration', null, 'aiohm-settings');

    add_settings_field('aiohm_openai_key', 'OpenAI API Key', 'aiohm_text_field_cb', 'aiohm-settings', 'aiohm_main_section', ['id' => 'aiohm_openai_key']);
    add_settings_field('aiohm_claude_key', 'Claude API Key', 'aiohm_text_field_cb', 'aiohm-settings', 'aiohm_main_section', ['id' => 'aiohm_claude_key']);
    add_settings_field('aiohm_scan_website', 'Enable Website Scan', 'aiohm_checkbox_field_cb', 'aiohm-settings', 'aiohm_main_section', ['id' => 'aiohm_scan_website']);
    add_settings_field('aiohm_scan_uploads', 'Enable Upload Folder Scan', 'aiohm_checkbox_field_cb', 'aiohm-settings', 'aiohm_main_section', ['id' => 'aiohm_scan_uploads']);
    add_settings_field('aiohm_use_semantic', 'Enable Semantic Search', 'aiohm_checkbox_field_cb', 'aiohm-settings', 'aiohm_main_section', ['id' => 'aiohm_use_semantic']);
    add_settings_field('aiohm_vector_db', 'Vector DB Provider', 'aiohm_dropdown_field_cb', 'aiohm-settings', 'aiohm_main_section');
}
add_action('admin_init', 'aiohm_register_settings');

function aiohm_text_field_cb($args) {
    $value = esc_attr(get_option($args['id'], ''));
    echo "<input type='text' id='{$args['id']}' name='{$args['id']}' value='{$value}' class='regular-text' />";
}

function aiohm_checkbox_field_cb($args) {
    $checked = checked(1, get_option($args['id'], 0), false);
    echo "<input type='checkbox' id='{$args['id']}' name='{$args['id']}' value='1' {$checked} />";
}

function aiohm_dropdown_field_cb() {
    $value = get_option('aiohm_vector_db', 'local');
    echo "<select id='aiohm_vector_db' name='aiohm_vector_db'>
            <option value='local'" . selected($value, 'local', false) . ">Local</option>
            <option value='pinecone'" . selected($value, 'pinecone', false) . ">Pinecone</option>
            <option value='supabase'" . selected($value, 'supabase', false) . ">Supabase</option>
        </select>";
}
