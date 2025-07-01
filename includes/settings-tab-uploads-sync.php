<?php
// File: includes/settings-tab-uploads-sync.php

if (!defined('ABSPATH')) exit;

// Fetch saved settings
$uploads_enabled = get_option('aiohm_enable_uploads', 'yes');
$allowed_extensions = get_option('aiohm_allowed_extensions', 'pdf,jpg,jpeg,png,docx');
$ocr_enabled = get_option('aiohm_enable_ocr', 'no');
$pdf_enabled = get_option('aiohm_enable_pdf_extract', 'yes');
?>
<div class="wrap">
  <h2>Uploads & Documents Sync</h2>
  <form method="post" action="options.php">
    <?php settings_fields('aiohm_uploads_settings'); ?>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">Enable AI to Learn from Uploads Folder</th>
        <td>
          <select name="aiohm_enable_uploads">
            <option value="yes" <?php selected($uploads_enabled, 'yes'); ?>>Yes</option>
            <option value="no" <?php selected($uploads_enabled, 'no'); ?>>No</option>
          </select>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Allowed File Extensions</th>
        <td>
          <input type="text" name="aiohm_allowed_extensions" value="<?php echo esc_attr($allowed_extensions); ?>" placeholder="e.g. pdf,jpg,png,docx" size="40">
          <p class="description">Separate multiple extensions with commas.</p>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Enable OCR for Images</th>
        <td>
          <select name="aiohm_enable_ocr">
            <option value="yes" <?php selected($ocr_enabled, 'yes'); ?>>Yes</option>
            <option value="no" <?php selected($ocr_enabled, 'no'); ?>>No</option>
          </select>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Extract Text from PDFs</th>
        <td>
          <select name="aiohm_enable_pdf_extract">
            <option value="yes" <?php selected($pdf_enabled, 'yes'); ?>>Yes</option>
            <option value="no" <?php selected($pdf_enabled, 'no'); ?>>No</option>
          </select>
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form>
</div>
