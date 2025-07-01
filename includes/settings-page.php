<?php
function aiohm_kb_assistant_settings_page() {
    $tabs = array(
        'overview' => 'Assistant Overview',
        'ai-keys' => 'AI Keys',
        'content-sync' => 'Website Content',
        'uploads-sync' => 'Uploads & Documents',
        'rag-engine' => 'RAG Engine',
        'branding' => 'Brand Profile',
        'pro' => 'Pro Features'
    );

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    echo '<div class="wrap"><h1>AIOHM Assistant Settings</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $label) {
        $active = ($tab == $active_tab) ? ' nav-tab-active' : '';
        echo '<a href="?page=aiohm-kb-assistant&tab=' . $tab . '" class="nav-tab' . $active . '">' . esc_html($label) . '</a>';
    }
    echo '</h2>';

    echo '<form method="post">';
    $tab_file = plugin_dir_path(__FILE__) . 'settings-tab-' . $active_tab . '.php';
    if (file_exists($tab_file)) {
        include $tab_file;
    } else {
        echo '<p>Tab not found.</p>';
    }
    echo '</form></div>';
}
?>
