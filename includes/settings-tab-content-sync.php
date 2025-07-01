<?php
// Save crawler options
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aiohm_content_sync_nonce']) && wp_verify_nonce($_POST['aiohm_content_sync_nonce'], 'save_content_sync')) {
    update_option('aiohm_sync_pages', isset($_POST['aiohm_sync_pages']) ? 1 : 0);
    update_option('aiohm_sync_posts', isset($_POST['aiohm_sync_posts']) ? 1 : 0);
    update_option('aiohm_sync_menus', isset($_POST['aiohm_sync_menus']) ? 1 : 0);
    echo '<div class="updated"><p>Content sync settings saved.</p></div>';
}

// Get options
$sync_pages = get_option('aiohm_sync_pages', 1);
$sync_posts = get_option('aiohm_sync_posts', 1);
$sync_menus = get_option('aiohm_sync_menus', 1);

echo '<h2>Website Content Sync</h2>';
echo '<p>Select which content types you want to sync and train into the assistant.</p>';
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">Pages</th>
        <td><input type="checkbox" name="aiohm_sync_pages" <?php checked($sync_pages, 1); ?> /> Include all public Pages</td>
    </tr>
    <tr valign="top">
        <th scope="row">Posts</th>
        <td><input type="checkbox" name="aiohm_sync_posts" <?php checked($sync_posts, 1); ?> /> Include all public Blog Posts</td>
    </tr>
    <tr valign="top">
        <th scope="row">Menus</th>
        <td><input type="checkbox" name="aiohm_sync_menus" <?php checked($sync_menus, 1); ?> /> Include Navigation Menus (text only)</td>
    </tr>
</table>

<?php wp_nonce_field('save_content_sync', 'aiohm_content_sync_nonce'); ?>
<p class="submit"><input type="submit" class="button-primary" value="Save Settings"></p>
