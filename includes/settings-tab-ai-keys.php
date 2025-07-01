<?php
// Save settings if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aiohm_ai_keys_nonce']) && wp_verify_nonce($_POST['aiohm_ai_keys_nonce'], 'save_ai_keys')) {
    update_option('aiohm_openai_key', sanitize_text_field($_POST['aiohm_openai_key']));
    update_option('aiohm_claude_key', sanitize_text_field($_POST['aiohm_claude_key']));
    echo '<div class="updated"><p>API Keys saved successfully.</p></div>';
}

// Get saved keys
$openai_key = get_option('aiohm_openai_key', '');
$claude_key = get_option('aiohm_claude_key', '');

echo '<h2>AI Providers</h2>';
echo '<p>Store your API keys securely to connect with OpenAI or Claude.</p>';
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">OpenAI API Key</th>
        <td>
            <input type="password" id="aiohm_openai_key" name="aiohm_openai_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" />
            <label><input type="checkbox" onclick="document.getElementById('aiohm_openai_key').type = this.checked ? 'text' : 'password'"> Show Key</label>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">Claude API Key</th>
        <td>
            <input type="password" id="aiohm_claude_key" name="aiohm_claude_key" value="<?php echo esc_attr($claude_key); ?>" class="regular-text" />
            <label><input type="checkbox" onclick="document.getElementById('aiohm_claude_key').type = this.checked ? 'text' : 'password'"> Show Key</label>
        </td>
    </tr>
</table>

<?php wp_nonce_field('save_ai_keys', 'aiohm_ai_keys_nonce'); ?>
<p class="submit"><input type="submit" class="button-primary" value="Save Keys"></p>
