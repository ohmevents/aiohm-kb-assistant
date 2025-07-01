<?php
/**
 * Frontend widget functionality - enqueue scripts and styles
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Frontend_Widget {
    
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        add_action('wp_head', array(__CLASS__, 'add_inline_styles'));
        add_action('wp_footer', array(__CLASS__, 'add_chat_init_script'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Only enqueue on pages that might use chat
        if (!self::should_load_assets()) {
            return;
        }
        
        // Enqueue chat script
        wp_enqueue_script(
            'aiohm-chat',
            AIOHM_KB_ASSETS_URL . 'js/aiohm-chat.js',
            array('jquery'),
            AIOHM_KB_VERSION,
            true
        );
        
        // Enqueue chat styles
        wp_enqueue_style(
            'aiohm-chat',
            AIOHM_KB_ASSETS_URL . 'css/aiohm-chat.css',
            array(),
            AIOHM_KB_VERSION
        );
        
        // Localize script with configuration
        $settings = AIOHM_KB_Core_Init::get_settings();
        
        wp_localize_script('aiohm-chat', 'aiohm_config', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'search_nonce' => wp_create_nonce('aiohm_search_nonce'),
            'chat_enabled' => $settings['chat_enabled'],
            'site_name' => get_bloginfo('name'),
            'strings' => array(
                'error' => __('Sorry, something went wrong. Please try again.', 'aiohm-kb-assistant'),
                'thinking' => __('Thinking...', 'aiohm-kb-assistant'),
                'send' => __('Send', 'aiohm-kb-assistant'),
                'ready' => __('Ready', 'aiohm-kb-assistant'),
                'typing' => __('Typing...', 'aiohm-kb-assistant'),
                'connecting' => __('Connecting...', 'aiohm-kb-assistant'),
                'you' => __('You', 'aiohm-kb-assistant'),
                'assistant' => __('Assistant', 'aiohm-kb-assistant'),
                'retry' => __('Retry', 'aiohm-kb-assistant'),
                'copy' => __('Copy', 'aiohm-kb-assistant'),
                'copied' => __('Copied!', 'aiohm-kb-assistant'),
                'sources' => __('Sources', 'aiohm-kb-assistant'),
                'no_results' => __('No results found.', 'aiohm-kb-assistant'),
                'searching' => __('Searching...', 'aiohm-kb-assistant'),
                'clear_chat' => __('Clear chat', 'aiohm-kb-assistant'),
                'confirm_clear' => __('Are you sure you want to clear the chat?', 'aiohm-kb-assistant')
            ),
            'features' => array(
                'auto_scroll' => true,
                'show_timestamps' => true,
                'enable_sound' => false,
                'typing_indicator' => true,
                'markdown_support' => true,
                'copy_messages' => true,
                'persist_chat' => true
            )
        ));
    }
    
    /**
     * Check if assets should be loaded on current page
     */
    private static function should_load_assets() {
        // Always load in admin
        if (is_admin()) {
            return true;
        }
        
        // Check if any shortcode is used on the current page
        global $post;
        if ($post && (has_shortcode($post->post_content, 'aiohm_chat') || has_shortcode($post->post_content, 'aiohm_search'))) {
            return true;
        }
        
        // Check if floating chat is enabled
        $settings = AIOHM_KB_Core_Init::get_settings();
        if (!empty($settings['show_floating_chat'])) {
            return true;
        }
        
        // Check if current page type should have chat
        $load_on_pages = apply_filters('aiohm_load_assets_on_pages', array(
            'is_front_page',
            'is_home',
            'is_single',
            'is_page'
        ));
        
        foreach ($load_on_pages as $page_check) {
            if (function_exists($page_check) && call_user_func($page_check)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add inline styles for customization
     */
    public static function add_inline_styles() {
        if (!self::should_load_assets()) {
            return;
        }
        
        $settings = AIOHM_KB_Core_Init::get_settings();
        
        // Get theme customization options
        $primary_color = get_theme_mod('aiohm_chat_primary_color', '#007cba');
        $secondary_color = get_theme_mod('aiohm_chat_secondary_color', '#f8f9fa');
        $text_color = get_theme_mod('aiohm_chat_text_color', '#333333');
        $border_radius = get_theme_mod('aiohm_chat_border_radius', '8');
        $font_size = get_theme_mod('aiohm_chat_font_size', '14');
        
        $custom_css = "
        <style type='text/css' id='aiohm-chat-custom-styles'>
        :root {
            --aiohm-primary-color: {$primary_color};
            --aiohm-secondary-color: {$secondary_color};
            --aiohm-text-color: {$text_color};
            --aiohm-border-radius: {$border_radius}px;
            --aiohm-font-size: {$font_size}px;
            --aiohm-chat-width: " . get_theme_mod('aiohm_chat_width', '100%') . ";
            --aiohm-chat-max-width: " . get_theme_mod('aiohm_chat_max_width', '600px') . ";
        }
        </style>";
        
        echo $custom_css;
    }
    
    /**
     * Add chat initialization script
     */
    public static function add_chat_init_script() {
        if (!self::should_load_assets()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        // Initialize AIOHM Chat when document is ready
        jQuery(document).ready(function($) {
            if (typeof window.AIOHM_Chat !== 'undefined') {
                // Initialize all chat instances
                $('.aiohm-chat-container').each(function() {
                    var chatId = $(this).attr('id');
                    if (chatId && window.aiohm_chat_configs && window.aiohm_chat_configs[chatId]) {
                        window.AIOHM_Chat.init(chatId, window.aiohm_chat_configs[chatId]);
                    }
                });
                
                // Initialize search instances
                $('.aiohm-search-container').each(function() {
                    var searchId = $(this).attr('id');
                    if (searchId && window.aiohm_search_configs && window.aiohm_search_configs[searchId]) {
                        window.AIOHM_Search.init(searchId, window.aiohm_search_configs[searchId]);
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add theme customizer options
     */
    public static function add_customizer_options($wp_customize) {
        // Add AIOHM Chat section
        $wp_customize->add_section('aiohm_chat_styling', array(
            'title' => __('AIOHM Chat Styling', 'aiohm-kb-assistant'),
            'description' => __('Customize the appearance of the chat widget.', 'aiohm-kb-assistant'),
            'priority' => 160
        ));
        
        // Primary color
        $wp_customize->add_setting('aiohm_chat_primary_color', array(
            'default' => '#007cba',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'aiohm_chat_primary_color', array(
            'label' => __('Primary Color', 'aiohm-kb-assistant'),
            'section' => 'aiohm_chat_styling',
            'settings' => 'aiohm_chat_primary_color'
        )));
        
        // Secondary color
        $wp_customize->add_setting('aiohm_chat_secondary_color', array(
            'default' => '#f8f9fa',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'aiohm_chat_secondary_color', array(
            'label' => __('Secondary Color', 'aiohm-kb-assistant'),
            'section' => 'aiohm_chat_styling',
            'settings' => 'aiohm_chat_secondary_color'
        )));
        
        // Text color
        $wp_customize->add_setting('aiohm_chat_text_color', array(
            'default' => '#333333',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'aiohm_chat_text_color', array(
            'label' => __('Text Color', 'aiohm-kb-assistant'),
            'section' => 'aiohm_chat_styling',
            'settings' => 'aiohm_chat_text_color'
        )));
        
        // Border radius
        $wp_customize->add_setting('aiohm_chat_border_radius', array(
            'default' => '8',
            'sanitize_callback' => 'absint'
        ));
        
        $wp_customize->add_control('aiohm_chat_border_radius', array(
            'label' => __('Border Radius (px)', 'aiohm-kb-assistant'),
            'section' => 'aiohm_chat_styling',
            'type' => 'number',
            'input_attrs' => array(
                'min' => 0,
                'max' => 50
            )
        ));
        
        // Font size
        $wp_customize->add_setting('aiohm_chat_font_size', array(
            'default' => '14',
            'sanitize_callback' => 'absint'
        ));
        
        $wp_customize->add_control('aiohm_chat_font_size', array(
            'label' => __('Font Size (px)', 'aiohm-kb-assistant'),
            'section' => 'aiohm_chat_styling',
            'type' => 'number',
            'input_attrs' => array(
                'min' => 10,
                'max' => 24
            )
        ));
    }
    
    /**
     * Register widget areas for chat placement
     */
    public static function register_widget_areas() {
        register_sidebar(array(
            'name' => __('AIOHM Chat Widget Area', 'aiohm-kb-assistant'),
            'id' => 'aiohm-chat-sidebar',
            'description' => __('Area for placing AIOHM chat widgets.', 'aiohm-kb-assistant'),
            'before_widget' => '<div class="aiohm-widget-wrapper">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="aiohm-widget-title">',
            'after_title' => '</h3>'
        ));
    }
    
    /**
     * Add body classes for styling
     */
    public static function add_body_classes($classes) {
        $settings = AIOHM_KB_Core_Init::get_settings();
        
        if ($settings['chat_enabled']) {
            $classes[] = 'aiohm-chat-enabled';
        }
        
        if (!empty($settings['show_floating_chat'])) {
            $classes[] = 'aiohm-floating-chat-enabled';
        }
        
        return $classes;
    }
    
    /**
     * Add preload hints for better performance
     */
    public static function add_preload_hints() {
        if (!self::should_load_assets()) {
            return;
        }
        
        // Preload chat assets
        echo '<link rel="preload" href="' . AIOHM_KB_ASSETS_URL . 'css/aiohm-chat.css" as="style">';
        echo '<link rel="preload" href="' . AIOHM_KB_ASSETS_URL . 'js/aiohm-chat.js" as="script">';
    }
}

// Initialize customizer integration
add_action('customize_register', array('AIOHM_KB_Frontend_Widget', 'add_customizer_options'));

// Register widget areas
add_action('widgets_init', array('AIOHM_KB_Frontend_Widget', 'register_widget_areas'));

// Add body classes
add_filter('body_class', array('AIOHM_KB_Frontend_Widget', 'add_body_classes'));

// Add preload hints
add_action('wp_head', array('AIOHM_KB_Frontend_Widget', 'add_preload_hints'), 1);
