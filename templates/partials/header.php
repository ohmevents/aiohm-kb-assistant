<?php
/**
 * AIOHM Plugin Admin Header - Redesigned with new menu and branding.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="aiohm-admin-header">
    <div class="aiohm-admin-header__logo">
        <a href="https://www.aiohm.app" target="_blank">
            <img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png'); ?>" alt="AIOHM Logo">
        </a>
    </div>
    <div class="aiohm-admin-header__nav">
        <nav class="aiohm-nav">
          <ul class="aiohm-menu">
            <li class="has-submenu">
              <a href="https://www.aiohm.app/pricing/">Shop</a>
              <ul class="submenu">
                <li><a href="https://www.aiohm.app/club/">Club</a></li>
                <li><a href="https://www.aiohm.app/private/">Private</a></li>
                <li><a href="https://www.aiohm.app/affiliate/">Affiliate</a></li>
              </ul>
            </li>
            <li class="has-submenu">
              <a href="https://www.aiohm.app/members/">Members</a>
              <ul class="submenu">
                <li><a href="https://www.aiohm.app/ohm-brand-voice-discovery/">Voice Discovery</a></li>
                <li><a href="https://www.aiohm.app/knowledge-base/">Knowledge Base</a></li>
                <li><a href="https://www.aiohm.app/test/">Test</a></li>
                <li><a href="https://www.aiohm.app/install/">Install</a></li>
                <li><a href="https://www.aiohm.app/edit-profile/">Edit Profile</a></li>
                <li><a href="https://www.aiohm.app/change-password/">Change Password</a></li>
                <li><a href="https://www.aiohm.app/forgot-password/">Forgot Password</a></li>
                <li><a href="https://www.aiohm.app?arm_action=logout">Logout</a></li>
              </ul>
            </li>
            <li class="has-submenu">
              <a href="https://www.aiohm.app/about/">About</a>
              <ul class="submenu">
                <li><a href="https://www.aiohm.app/our-mission/">Our Mission</a></li>
              </ul>
            </li>
            <li class="has-submenu">
              <a href="https://www.aiohm.app/contact/">Contact</a>
              <ul class="submenu">
                <li><a href="https://www.aiohm.app/privacy-policy/">Privacy Policy</a></li>
              </ul>
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
        padding: 5px 25px; /* Made smaller */
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-left: -20px;
        border-bottom: 2px solid #EBEBEB;
    }
    .aiohm-admin-header__logo img {
        height: 40px; /* Made smaller */
        width: auto;
        display: block;
    }
    .aiohm-admin-header__nav {
        font-family: 'Montserrat', sans-serif;
    }
    
    /* New Menu Styles (Branded) */
    .aiohm-nav ul { 
        list-style: none; 
        margin: 0; 
        padding: 0; 
    }
    .aiohm-menu > li { 
        display: inline-block; 
        position: relative; 
        margin-left: 15px; 
    }
    .aiohm-menu > li > a { 
        text-decoration: none; 
        color: #fff; /* White text for top-level items */
        font-weight: bold; 
        font-size: 14px;
        padding: 10px 15px;
        display: block;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    .aiohm-menu > li > a:hover {
        background-color: rgba(255,255,255,0.1);
    }
    .has-submenu:hover .submenu { 
        display: block; 
    }
    .submenu {
        display: none;
        position: absolute;
        top: 100%; 
        left: 0;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 1000;
        min-width: 200px;
        padding: 5px 0;
    }
    .submenu li { 
        display: block; 
        margin: 0; 
    }
    .submenu a { 
        padding: 10px 15px; 
        display: block; 
        white-space: nowrap; 
        color: #272727; /* OHM Dark for submenu text */
        font-size: 13px;
        font-weight: normal;
    }
    .submenu a:hover { 
        background: #EBEBEB; /* OHM Light BG for hover */
    }

    /* General Wrapper */
    .aiohm-admin-wrap {
        margin-top: 10px;
    }
</style>