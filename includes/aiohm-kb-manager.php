<?php
// includes/kb-manager.php

add_action('admin_menu', function() {
    add_submenu_page(
        'aiohm-settings',
        'Knowledge Base',
        'Knowledge Base',
        'manage_options',
        'aiohm-kb-manager',
        'aiohm_render_kb_manager'
    );
});

function aiohm_render_kb_manager() {
    echo '<div class="wrap"><h1>🧠 Knowledge Base Manager</h1>';
    echo '<p>Upload JSON or PDF files, or manage existing embedded entries below.</p>';

    // Upload Form
    echo '<form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
            <input type="file" name="aiohm_kb_upload" accept=".json,.pdf" required>
            ' . get_submit_button('📤 Upload to Knowledge Base', 'primary', 'upload_kb_file', false) . '
          </form>';

    // Handle Upload
    if (!empty($_FILES['aiohm_kb_upload']['name']) && current_user_can('manage_options')) {
        $uploaded = wp_handle_upload($_FILES['aiohm_kb_upload'], ['test_form' => false]);
        if (!isset($uploaded['error'])) {
            echo '<div class="notice notice-success is-dismissible" style="margin-top:20px;"><p>✅ File uploaded: ' . esc_html(basename($uploaded['file'])) . '</p></div>';
            // Optional: trigger RAG vector embed hook here
        } else {
            echo '<div class="notice notice-error is-dismissible" style="margin-top:20px;"><p>❌ Upload failed: ' . esc_html($uploaded['error']) . '</p></div>';
        }
    }

    // Handle Delete
    if (!empty($_POST['delete_kb_key']) && current_user_can('manage_options')) {
        $key = sanitize_text_field($_POST['delete_kb_key']);
        $kb_entries = get_option('aiohm_vector_entries', []);
        if (isset($kb_entries[$key])) {
            unset($kb_entries[$key]);
            update_option('aiohm_vector_entries', $kb_entries);
            echo '<div class="notice notice-success is-dismissible" style="margin-top:20px;"><p>🗑️ Entry deleted.</p></div>';
        }
    }

    // Display Indexed Items
    $kb_entries = get_option('aiohm_vector_entries', []);

    if (!empty($kb_entries)) {
        echo '<h2 style="margin-top: 40px;">📚 Existing Knowledge Items</h2>';
        echo '<table class="widefat fixed striped" style="width: 100%; max-width: 1000px; margin-top: 10px;">
                <thead><tr><th>Source</th><th>Preview</th><th>Actions</th></tr></thead><tbody>';
        foreach ($kb_entries as $key => $entry) {
            echo '<tr><td>' . esc_html($entry['source'] ?? 'unknown') . '</td>';
            echo '<td>' . esc_html(wp_trim_words($entry['text'], 40)) . '</td>';
            echo '<td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_kb_key" value="' . esc_attr($key) . '">
                    <button type="submit" class="button">🗑️ Delete</button>
                </form>
                </td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p style="margin-top: 30px;">No knowledge base entries yet.</p>';
    }

    echo '</div>';
}
