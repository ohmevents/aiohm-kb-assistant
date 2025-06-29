<?php
/**
 * Plugin Name: AIOHM KB Assistant
 * Description: Brand-aligned AI assistant powered by WP content. Features: KB scan, AI setup, analytics, and upgrade tab.
 * Version: 1.5
 * Author: a.adrian
 * Text Domain: aiohm-kb
 */

defined('ABSPATH') or die;

class AIOHM_KB_Assistant {
    private $version = '1.5';
    private $opt = 'aiohm_kb_options';
    private $conv = 'aiohm_kb_conversations';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'load_frontend_assets']);
        add_shortcode('aiohm_assistant', [$this, 'render_chat_shortcode']);

        add_action('wp_ajax_aiohm_scan_kb', [$this, 'ajax_scan_kb']);
        add_action('wp_ajax_aiohm_generate_kb', [$this, 'ajax_generate_kb']);
        add_action('wp_ajax_aiohm_update_kb_title', [$this, 'ajax_update_kb_title']);
        add_action('wp_ajax_aiohm_delete_kb_entry', [$this, 'ajax_delete_kb_entry']);
        add_action('wp_ajax_aiohm_query_kb', [$this, 'ajax_query_kb']);
        add_action('wp_ajax_nopriv_aiohm_query_kb', [$this, 'ajax_query_kb']);

        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        load_plugin_textdomain('aiohm-kb', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate() {
        $d = wp_upload_dir()['basedir'] . '/aiohm-kb/';
        if (!file_exists($d)) wp_mkdir_p($d);
        file_put_contents($d . '.htaccess', "deny from all\n");
    }

    public function admin_menu() {
        add_menu_page('AIOHM KB Assistant', 'AIOHM Assistant', 'manage_options', 'aiohm-kb', [$this, 'render_admin_page'], 'dashicons-format-chat', 30);
        add_submenu_page('aiohm-kb', 'Analytics', 'Analytics', 'manage_options', 'aiohm-kb-analytics', [$this, 'render_analytics_page']);
    }

    public function admin_init() {
        register_setting('aiohm_kb_settings', $this->opt, [$this, 'sanitize_options']);

        add_settings_section('kb_section', __('Knowledge Base', 'aiohm-kb'), null, 'aiohm_kb_content');
        add_settings_section('ai_section', __('AI Configuration', 'aiohm-kb'), null, 'aiohm_kb_ai');

        add_settings_field('post_types', __('Content Types', 'aiohm-kb'), [$this, 'field_post_types'], 'aiohm_kb_content', 'kb_section');
        add_settings_field('enable_streaming', __('Enable Streaming', 'aiohm-kb'), [$this, 'field_enable_streaming'], 'aiohm_kb_ai', 'ai_section');
        add_settings_field('chat_model', __('Chat Model', 'aiohm-kb'), [$this, 'field_chat_model'], 'aiohm_kb_ai', 'ai_section');
        add_settings_field('embedding_model', __('Embedding Model', 'aiohm-kb'), [$this, 'field_embedding_model'], 'aiohm_kb_ai', 'ai_section');
        add_settings_field('ai_instructions', __('AI Instructions', 'aiohm-kb'), [$this, 'field_ai_instructions'], 'aiohm_kb_ai', 'ai_section');
        add_settings_field('openai_key', __('OpenAI API Key', 'aiohm-kb'), [$this, 'field_api_key'], 'aiohm_kb_ai', 'ai_section');
    }

    public function load_admin_assets($hook) {
        if (strpos($hook, 'aiohm-kb') === false) return;
        wp_enqueue_style('aiohm-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], $this->version);
        wp_enqueue_script('aiohm-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], $this->version, true);
        wp_localize_script('aiohm-admin-js', 'aiohm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aiohm_nonce'),
        ]);
    }

    public function load_frontend_assets() {
        wp_enqueue_style('aiohm-frontend-css', plugin_dir_url(__FILE__) . 'assets/css/frontend.css', [], $this->version);
        wp_enqueue_script('aiohm-frontend-js', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', ['jquery'], $this->version, true);
        wp_localize_script('aiohm-frontend-js', 'aiohm_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aiohm_frontend_nonce'),
            'thinking'=> __('Thinking...', 'aiohm-kb'),
            'error'   => __('Sorry, something went wrong.', 'aiohm-kb'),
        ]);
    }

    public function sanitize_options($in) {
        $o = [];
        $o['post_types'] = array_intersect(['post','page','attachment'], array_map('sanitize_text_field', $in['post_types'] ?? []));
        $o['enable_streaming'] = !empty($in['enable_streaming']) ? 1 : 0;
        $o['chat_model'] = sanitize_text_field($in['chat_model'] ?? 'gpt-3.5-turbo');
        $o['embedding_model'] = sanitize_text_field($in['embedding_model'] ?? 'text-embedding-ada-002');
        $o['ai_instructions'] = sanitize_textarea_field($in['ai_instructions'] ?? '');
        $o['openai_key'] = sanitize_text_field($in['openai_key'] ?? '');
        return $o;
    }

    public function render_admin_page() {
        $tab = $_GET['tab'] ?? 'kb'; ?>
        <div class="wrap">
            <h1><?php _e('AIOHM KB Assistant Settings', 'aiohm-kb') ?></h1>
            <h2 class="nav-tab-wrapper">
                <a class="nav-tab<?php if ($tab == 'kb') echo ' nav-tab-active'; ?>" href="?page=aiohm-kb&tab=kb"><?php _e('Knowledge Base', 'aiohm-kb') ?></a>
                <a class="nav-tab<?php if ($tab == 'ai') echo ' nav-tab-active'; ?>" href="?page=aiohm-kb&tab=ai"><?php _e('AI Setup', 'aiohm-kb') ?></a>
                <a class="nav-tab<?php if ($tab == 'upgrade') echo ' nav-tab-active'; ?>" href="?page=aiohm-kb&tab=upgrade"><?php _e('Upgrade to Pro', 'aiohm-kb') ?></a>
            </h2>

            <?php if ($tab === 'upgrade'): ?>
                <div class="aiohm-pro-upgrade-tab">
                    <h2><?php _e('Upgrade to AIOHM Pro', 'aiohm-kb') ?></h2>
                    <p><?php _e('Unlock advanced features: custom fields, export options, more themes, analytics, and full control.', 'aiohm-kb') ?></p>
                    <a href="https://aiohm.app/free-download" class="button button-primary button-hero"><?php _e('Upgrade Now', 'aiohm-kb') ?></a>
                </div>

            <?php else: ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('aiohm_kb_settings');
                    if ($tab === 'kb') {
                        do_settings_sections('aiohm_kb_content');
                        submit_button(__('Save Settings', 'aiohm-kb'), 'primary', 'save', false);
                        echo '<button type="button" id="aiohm-scan-kb" class="button">' . __('Scan Website', 'aiohm-kb') . '</button>';
                        echo '<div id="aiohm-kb-scan-result" style="margin-top:20px;"></div>';
                    } else {
                        do_settings_sections('aiohm_kb_ai');
                        submit_button(__('Save Settings', 'aiohm-kb'));
                    }
                    ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_analytics_page() {
        $convos = get_option($this->conv, []);
        $total = count($convos);
        $week = 0;
        $now = time();
        foreach ($convos as $c) {
            if (strtotime($c['timestamp']) > $now - WEEK_IN_SECONDS) $week++;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AIOHM Assistant Analytics', 'aiohm-kb') ?></h1>
            <p><?php printf(__('Total Chats: %d', 'aiohm-kb'), $total); ?></p>
            <p><?php printf(__('Chats This Week: %d', 'aiohm-kb'), $week); ?></p>
        </div>
        <?php
    }

    // Field methods
    public function field_post_types() {
        $o = get_option($this->opt, []);
        $sel = $o['post_types'] ?? [];
        foreach (['post'=>'Post','page'=>'Page','attachment'=>'Media'] as $k=>$l) {
            $c = in_array($k, $sel) ? 'checked' : '';
            echo "<label style='margin-right:15px;'><input type='checkbox' name='{$this->opt}[post_types][]' value='$k' $c> $l</label>";
        }
    }
    public function field_enable_streaming() {
        $o = get_option($this->opt, []);
        $c = !empty($o['enable_streaming']) ? 'checked' : '';
        echo "<label><input type='checkbox' name='{$this->opt}[enable_streaming]' value='1' $c> " . __('Enable streaming responses', 'aiohm-kb') . "</label>";
    }
    public function field_chat_model() {
        $o = get_option($this->opt, []);
        $v = $o['chat_model'] ?? 'gpt-3.5-turbo';
        echo "<select name='{$this->opt}[chat_model]'>
            <option value='gpt-3.5-turbo'".selected($v,'gpt-3.5-turbo',false).">GPT‑3.5 Turbo</option>
            <option value='gpt-4'".selected($v,'gpt-4',false).">GPT‑4</option>
        </select>";
    }
    public function field_embedding_model() {
        $o = get_option($this->opt, []);
        $v = $o['embedding_model'] ?? 'text-embedding-ada-002';
        echo "<select name='{$this->opt}[embedding_model]'>
            <option value='text-embedding-ada-002'".selected($v,'text-embedding-ada-002',false).">Ada 2 (1536)</option>
        </select>";
    }
    public function field_ai_instructions() {
        $o = get_option($this->opt, []);
        $txt = esc_textarea($o['ai_instructions'] ?? '');
        echo "<textarea name='{$this->opt}[ai_instructions]' rows='6' cols='60'>$txt</textarea>";
    }
    public function field_api_key() {
        $o = get_option($this->opt, []);
        echo "<input type='password' name='{$this->opt}[openai_key]' value='".esc_attr($o['openai_key'] ?? '')."' class='regular-text' placeholder='sk-...' />";
    }

    public function ajax_scan_kb() {
        check_ajax_referer('aiohm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Access denied.', 'aiohm-kb'));
        $types = ['post','page','attachment'];
        $rows = [];
        foreach ($types as $t) {
            $count = wp_count_posts($t)->publish ?? 0;
            $rows[] = "<tr><td>$t</td><td>$count</td></tr>";
        }
        $html = '<table class="widefat"><thead><tr><th>' . __('Type','aiohm-kb') . '</th><th>' . __('Count','aiohm-kb') . '</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table>';
        wp_send_json_success(['html'=>$html]);
    }

    // Note: ajax_generate_kb and ajax_query_kb etc. are assumed implemented previously.

    public function render_chat_shortcode($atts) {
        $atts = shortcode_atts(['theme'=>'light','height'=>'400px'], $atts);
        ob_start();
        ?>
        <div id="aiohm-chat-wrapper" class="theme-<?php echo esc_attr($atts['theme']); ?>" style="height:<?php echo esc_attr($atts['height']); ?>">
            <div id="aiohm-chat-box"></div>
            <div id="aiohm-input-area">
                <textarea id="aiohm-user-input" placeholder="<?php _e('Ask me anything...', 'aiohm-kb'); ?>"></textarea>
                <button id="aiohm-send-btn"><?php _e('Send', 'aiohm-kb'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AIOHM_KB_Assistant();
