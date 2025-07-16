<?php
/**
 * AIOHM Plugin Admin Header - Redesigned with internal plugin page navigation.
 */
if (!defined('ABSPATH')) exit;

// Get the current page to set the 'active' class on the menu item
$current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
?>
<div class="aiohm-admin-header">
    <div class="aiohm-admin-header__logo">
        <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-dashboard')); ?>">
            <?php
            echo wp_kses_post(AIOHM_KB_Core_Init::render_image(
                AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png',
                esc_attr__('AIOHM Logo', 'aiohm-kb-assistant')
            ));
            ?>
        </a>
    </div>
    <div class="aiohm-admin-header__nav">
        <nav class="aiohm-nav">
          <ul class="aiohm-menu">
            <li class="<?php echo ($current_page === 'aiohm-dashboard') ? 'active' : ''; ?>">
              <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-dashboard')); ?>">Dashboard</a>
            </li>
            <li class="<?php echo ($current_page === 'aiohm-brand-soul') ? 'active' : ''; ?>">
              <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-brand-soul')); ?>">AI Brand Core</a>
            </li>
             <li class="has-submenu">
                <a href="#" class="<?php echo in_array($current_page, ['aiohm-scan-content', 'aiohm-manage-kb']) ? 'active' : ''; ?>">Knowledge Base</a>
                <ul class="submenu">
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-scan-content')); ?>">Scan Content</a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-manage-kb')); ?>">Manage KB</a></li>
                </ul>
            </li>
            <li class="has-submenu">
              <a href="#" class="<?php echo in_array($current_page, ['aiohm-settings', 'aiohm-license']) ? 'active' : ''; ?>">Settings</a>
              <ul class="submenu">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-settings')); ?>">API Settings</a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-license')); ?>">License</a></li>
              </ul>
            </li>
            <li>
              <a href="https://www.aiohm.app/contact/" target="_blank">Contact</a>
            </li>
          </ul>
        </nav>
    </div>
</div>
<div class="aiohm-admin-wrap">

<style>
    /* Header & Menu Styles */
    .aiohm-admin-header {
        background-color: #1f5014; /* OHM Dark Green */
        padding: 5px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-left: -20px;
        border-bottom: 2px solid #EBEBEB;
    }
    .aiohm-admin-header__logo img {
        height: 40px;
        width: auto;
        display: block;
    }
    .aiohm-admin-header__nav {
        font-family: 'Montserrat', sans-serif;
    }
    .aiohm-nav ul { list-style: none; margin: 0; padding: 0; }
    .aiohm-menu > li { display: inline-block; position: relative; margin-left: 15px; }
    .aiohm-menu > li > a { 
        text-decoration: none; 
        color: #fff; 
        font-weight: bold; 
        font-size: 14px; 
        padding: 10px 15px; 
        display: block; 
        border-radius: 4px; 
        transition: background-color 0.2s ease; 
    }
    .aiohm-menu > li > a:hover,
    .aiohm-menu > li.active > a { 
        background-color: rgba(255,255,255,0.1); 
    }
    .has-submenu:hover .submenu { display: block; }
    .submenu { display: none; position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #ddd; border-radius: 0 0 4px 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 1000; min-width: 200px; padding: 5px 0; }
    .submenu li { display: block; margin: 0; }
    .submenu a { padding: 10px 15px; display: block; white-space: nowrap; color: #272727; font-size: 13px; font-weight: normal; }
    .submenu a:hover { background: #EBEBEB; }

    /* General Wrapper */
    .aiohm-admin-wrap {
        margin-top: 10px;
    }

    /* Footer */
    .aiohm-admin-footer {
        margin-top: 80px;
        padding: 20px 0;
        border-top: 1px solid #ddd;
        text-align: center;
        color: #777;
        font-family: 'PT Sans', sans-serif;
    }
</style>