<?php
/**
 * Plugin Name: AIOHM KB Assistant Pro
 * Description: Brand-aligned AI assistant with semantic search using OpenAI embeddings, similarity threshold, KB scan, analytics, and upgrade prompt.
 * Version: 2.0
 * Author: a.adrian
 * Text Domain: aiohm-kb
 */

defined('ABSPATH') || exit;

class AIOHM_KB_Assistant_Pro {
  private $opt = 'aiohm_kb_options';
  private $conv = 'aiohm_kb_conversations';

  public function __construct() {
    add_action('init', [$this, 'init']);
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('admin_init', [$this, 'admin_init']);
    add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
    add_shortcode('aiohm_assistant', [$this, 'chat_shortcode']);

    add_action('wp_ajax_aiohm_scan_kb', [$this, 'ajax_scan_kb']);
    add_action('wp_ajax_aiohm_generate_kb', [$this, 'ajax_generate_kb']);
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
    file_put_contents($d . '.htaccess', "deny from all");
  }

  public function admin_menu() {
    add_menu_page('AIOHM Assistant', 'AIOHM Assistant', 'manage_options', 'aiohm-kb', [$this, 'settings_page'], 'dashicons-format-chat', 30);
    add_submenu_page('aiohm-kb', 'Analytics', 'Analytics', 'manage_options', 'aiohm-analytics', [$this, 'analytics_page']);
  }

  public function admin_init() {
    register_setting('aiohm_kb', $this->opt, [$this, 'sanitize']);

    add_settings_section('sec_ai', __('AI Settings', 'aiohm-kb'), null, 'aiohm-ai');
    add_settings_field('openai_key', __('OpenAI API Key', 'aiohm-kb'), [$this, 'field_api'], 'aiohm-ai', 'sec_ai');
    add_settings_field('threshold', __('Similarity Threshold', 'aiohm-kb'), [$this, 'field_threshold'], 'aiohm-ai', 'sec_ai');
    add_settings_field('model', __('Embedding Model', 'aiohm-kb'), [$this, 'field_model'], 'aiohm-ai', 'sec_ai');
    add_settings_field('instructions', __('AI Instructions', 'aiohm-kb'), [$this, 'field_instructions'], 'aiohm-ai', 'sec_ai');
  }

  public function admin_assets($hook) {
    if (strpos($hook, 'aiohm-kb') === false) return;
    wp_enqueue_script('aiohm-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '2.0', true);
    wp_enqueue_style('aiohm-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '2.0');
    wp_localize_script('aiohm-admin', 'aiohm', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('aiohm_nonce'),
    ]);
  }

  public function frontend_assets() {
    wp_enqueue_script('aiohm-frontend', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', ['jquery'], '2.0', true);
    wp_enqueue_style('aiohm-frontend', plugin_dir_url(__FILE__) . 'assets/css/frontend.css', [], '2.0');
    wp_localize_script('aiohm-frontend', 'aiohm', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('aiohm_nonce'),
      'thinking' => __('Thinking...', 'aiohm-kb'),
      'error' => __('Something went wrong.', 'aiohm-kb'),
    ]);
  }

  public function sanitize($in) {
    return [
      'openai_key' => sanitize_text_field($in['openai_key'] ?? ''),
      'threshold' => clamp(floatval($in['threshold'] ?? 0.75), 0, 1),
      'model' => sanitize_text_field($in['model'] ?? 'text-embedding-3-small'),
      'instructions' => sanitize_textarea_field($in['instructions'] ?? ''),
    ];
  }

  public function field_api() {
    $o = get_option($this->opt, []);
    echo "<input type='password' name='{$this->opt}[openai_key]' value='".esc_attr($o['openai_key'] ?? '')."' style='width:300px'>";
  }
  public function field_threshold() {
    $o = get_option($this->opt, []);
    $v = $o['threshold'] ?? 0.75;
    echo "<input type='number' min='0' max='1' step='0.01' name='{$this->opt}[threshold]' value='".esc_attr($v)."'>";
  }
  public function field_model() {
    $o = get_option($this->opt, []);
    $v = $o['model'] ?? 'text-embedding-3-small';
    echo "<input type='text' name='{$this->opt}[model]' value='".esc_attr($v)."' style='width:300px'>";
  }
  public function field_instructions() {
    $o = get_option($this->opt, []);
    echo "<textarea name='{$this->opt}[instructions]' rows='4' cols='60'>".esc_textarea($o['instructions'] ?? '')."</textarea>";
  }

  public function settings_page() {
    ?>
    <div class="wrap"><h1><?php _e('AIOHM Assistant Settings', 'aiohm-kb') ?></h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('aiohm_kb');
        do_settings_sections('aiohm-ai');
        submit_button();
        ?>
      </form>
      <button id="aiohm-scan">Scan Content</button><div id="aiohm-scan-result"></div>
      <script>jQuery('#aiohm-scan').on('click',()=>ajaxScan())</script>
    </div>
    <?php
  }

  public function analytics_page() {
    $c = get_option($this->conv, []);
    $tot = count($c);
    $w = count(array_filter($c, fn($e)=>strtotime($e['t'])>time()-WEEK_IN_SECONDS));
    echo "<div class='wrap'><h1>Analytics</h1><p>Total: $tot chats</p><p>Last week: $w chats</p></div>";
  }

  public function ajax_scan_kb() {
    check_ajax_referer('aiohm_nonce','nonce');
    if(!current_user_can('manage_options')) wp_send_json_error();
    $ct = array_map(fn($t)=>wp_count_posts($t)->publish ?? 0, ['post','page','attachment']);
    $out = "<ul>";
    foreach(['Posts','Pages','Media'] as $i=>$label) $out .= "<li>$label: {$ct[$i]}</li>";
    $out .= "</ul>";
    wp_send_json_success($out);
  }

  public function ajax_generate_kb() { /* left for future */ }

  public function ajax_query_kb() {
    check_ajax_referer('aiohm_nonce','nonce');
    $q = sanitize_text_field($_POST['prompt'] ?? '');
    if(!$q) return wp_send_json_error('Empty prompt');
    $o = get_option($this->opt, []);
    $k = $o['openai_key'] ?? ''; if(!$k) return wp_send_json_error('Missing API Key');

    $payload = [
      'model'=>$o['model'],
      'input'=>$q
    ];
    $r = wp_remote_post('https://api.openai.com/v1/embeddings', [
      'headers'=>['Authorization'=>'Bearer ' . $k,'Content-Type'=>'application/json'],
      'body'=>wp_json_encode($payload)
    ]);
    if(is_wp_error($r)) return wp_send_json_error($r->get_error_message());
    $vec = json_decode(wp_remote_retrieve_body($r),true)['data'][0]['embedding'] ?? [];

    // Load KB embeddings from JSON file
    $kb = json_decode(file_get_contents(wp_upload_dir()['basedir'] . '/aiohm-kb/knowledge-base.json'),true);
    $scores=[];
    foreach($kb as $e) {
      if(empty($e['embedding'])) continue;
      $scores[]=['entry'=>$e,'score'=>$this->cosine($vec,$e['embedding'])];
    }
    usort($scores, fn($a,$b)=>$b['score']<=>$a['score']);
    $best = array_filter($scores, fn($i)=>$i['score'] >= ($o['threshold'] ?? 0.75));
    $text = array_map(fn($i)=>$i['entry']['content'], array_slice($best,0,3));
    $ctx = implode("\n---\n",$text);

    // Send prompt + context to Chat API
    $chat = wp_remote_post('https://api.openai.com/v1/chat/completions', [
      'headers'=>['Authorization'=>'Bearer ' . $k,'Content-Type'=>'application/json'],
      'body'=>wp_json_encode([
        'model'=>'gpt-3.5-turbo',
        'messages'=>[
          ['role'=>'system','content'=>$o['instructions'] ?? 'You are a helpful assistant.'],
          ['role'=>'user','content'=>$ctx . "\n\nQ: $q"]
        ]
      ])
    ]);
    if(is_wp_error($chat)) return wp_send_json_error('Chat failed');
    $resp = json_decode(wp_remote_retrieve_body($chat),true)['choices'][0]['message']['content'] ?? '';
    wp_send_json_success($resp);
  }

  private function cosine($a,$b) {
    $dot=array_sum(array_map(fn($i,$j)=>$i*$j,$a,$b));
    $ma=sqrt(array_sum(array_map(fn($i)=>$i*$i,$a)));
    $mb=sqrt(array_sum(array_map(fn($i)=>$i*$i,$b)));
    return $ma*$mb?($dot/($ma*$mb)):0;
  }

  public function chat_shortcode($at) {
    $a=shortcode_atts(['theme'=>'light','height'=>'400px'],$at);
    ob_start(); ?>
    <div id="aiohm-chat" class="theme-<?php echo esc_attr($a['theme']); ?>" style="height:<?php echo esc_attr($a['height']); ?>;">
      <div id="aiohm-messages"></div><textarea id="aiohm-input" placeholder="Ask..."></textarea><button id="aiohm-send">Send</button>
    </div>
    <?php return ob_get_clean();
  }
}

new AIOHM_KB_Assistant_Pro();
