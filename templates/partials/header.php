<?php
/**
 * AIOHM Plugin Admin Header
 */
if (!defined('ABSPATH')) exit;
?>
<div class="aiohm-admin-header">
    <div class="aiohm-admin-header__logo">
        <img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="AIOHM Logo">
    </div>
    <div class="aiohm-admin-header__nav">
        <a href="https://aiohm.app/support" target="_blank">Support</a>
    </div>
</div>
<div class="aiohm-admin-wrap">

<style>
    /* Header & Footer Styles */
    .aiohm-admin-header {
        background-color: #457d58; /* OHM Green */
        padding: 10px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-left: -20px; /* Overcome default WP padding */
        border-bottom: 5px solid #1f5014; /* OHM Dark Green */
    }
    .aiohm-admin-header__logo img {
        height: 40px;
        width: auto;
    }
    .aiohm-admin-header__nav a {
        color: #fff;
        text-decoration: none;
        font-family: 'Montserrat', sans-serif;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    .aiohm-admin-header__nav a:hover {
        background-color: rgba(255,255,255,0.1);
    }
    /* This wrap contains all page content below the header */
    .aiohm-admin-wrap {
        margin-top: 15px;
    }
    /* Footer */
    .aiohm-admin-footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        text-align: center;
        color: #777;
        font-family: 'PT Sans', sans-serif;
    }
</style>