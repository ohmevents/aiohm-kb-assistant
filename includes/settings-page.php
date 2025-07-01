<?php
// includes/settings-page.php

add_action('admin_menu', function () {
    add_menu_page(
        'AIOHM Settings',
        'AIOHM Settings',
        'manage_options',
        'aiohm-settings',
        'aiohm_render_settings_page',
        'dashicons-art',
        80
    );
});

function aiohm_render_settings_page() {
    echo '<div class="wrap"><h1>AIOHM Settings</h1>';
    echo '<form method="post">';

    // API Key Inputs
    echo '<h2>🔑 API Keys</h2>';
    echo '<table class="form-table"><tr><th>OpenAI Key:</th><td><input type="text" name="aiohm_openai_key" value="' . esc_attr(get_option('aiohm_openai_key')) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>Claude Key:</th><td><input type="text" name="aiohm_claude_key" value="' . esc_attr(get_option('aiohm_claude_key')) . '" class="regular-text" /></td></tr></table>';

    // Save and Generate Buttons
    submit_button('💾 Save Settings');
    echo '<button name="generate_qa_dataset" class="button button-secondary" style="margin-left:10px;">⚙️ Generate Q&A Dataset</button>';
    echo '</form>';

    // Save logic
    if (!empty($_POST['aiohm_openai_key']) || !empty($_POST['aiohm_claude_key'])) {
        update_option('aiohm_openai_key', sanitize_text_field($_POST['aiohm_openai_key']));
        update_option('aiohm_claude_key', sanitize_text_field($_POST['aiohm_claude_key']));
        echo '<div class="notice notice-success is-dismissible"><p>✅ Keys saved.</p></div>';
    }

    // Generate Q&A logic
    if (!empty($_POST['generate_qa_dataset'])) {
        $entries = get_option('aiohm_vector_entries', []);
        $qa = [];
        foreach ($entries as $entry) {
            $text = $entry['text'] ?? '';
            $lines = preg_split('/\r\n|\r|\n/', $text);
            foreach ($lines as $line) {
                if (strpos($line, '?') !== false && strlen($line) < 160) {
                    $qa[] = [
                        'question' => trim($line),
                        'answer' => wp_trim_words($text, 80)
                    ];
                    break;
                }
            }
        }
        update_option('aiohm_qa_dataset', $qa);
        echo '<div class="notice notice-info is-dismissible"><p>✅ ' . count($qa) . ' Q&A pairs generated.</p></div>';
    }

    echo '</div>';
}
