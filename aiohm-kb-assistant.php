<?php
/**
 * Plugin Name: AIOHM KB Assistant
 * Description: A brand-trained AI assistant powered by your WordPress content.
 * Version: 0.5
 * Author: Your Name
 */

defined('ABSPATH') or die('No script kiddies please!');

// Admin Menu
add_action('admin_menu', 'aiohm_kb_menu');

function aiohm_kb_menu() {
    add_menu_page('AIOHM KB', 'AIOHM Assistant', 'manage_options', 'aiohm-kb', 'aiohm_kb_settings');
}

// Admin Settings Page
function aiohm_kb_settings() {
    ?>
    <div class="wrap">
        <h1>AIOHM KB Assistant Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('aiohm_kb_settings_group');
                do_settings_sections('aiohm-kb');
                submit_button(); // Save Settings
                submit_button('Generate Knowledge Base', 'secondary', 'aiohm_generate_kb'); // KB Generator
            ?>
        </form>
    </div>
    <?php
}

// Register Settings and Fields
add_action('admin_init', 'aiohm_kb_register_settings');

function aiohm_kb_register_settings() {
    register_setting('aiohm_kb_settings_group', 'aiohm_kb_post_types');
    register_setting('aiohm_kb_settings_group', 'aiohm_openai_api_key');

    add_settings_section(
        'aiohm_kb_main_section',
        'Knowledge Base Source Settings',
        null,
        'aiohm-kb'
    );

    add_settings_field(
        'aiohm_kb_post_types',
        'Select Post Types to Include:',
        'aiohm_kb_post_types_field',
        'aiohm-kb',
        'aiohm_kb_main_section'
    );

    add_settings_field(
        'aiohm_openai_api_key',
        'OpenAI API Key:',
        'aiohm_openai_api_key_field',
        'aiohm-kb',
        'aiohm_kb_main_section'
    );
}

// Post Types Checkbox Field
function aiohm_kb_post_types_field() {
    $selected = (array) get_option('aiohm_kb_post_types', []);
    $post_types = get_post_types(['public' => true], 'objects');

    foreach ($post_types as $type) {
        $checked = in_array($type->name, $selected) ? 'checked' : '';
        echo "<label><input type='checkbox' name='aiohm_kb_post_types[]' value='{$type->name}' $checked> {$type->labels->singular_name}</label><br>";
    }
}

// OpenAI API Key Field
function aiohm_openai_api_key_field() {
    $api_key = esc_attr(get_option('aiohm_openai_api_key', ''));
    echo "<input type='text' name='aiohm_openai_api_key' value='$api_key' style='width:100%;' placeholder='sk-...'>";
}

// Generate JSON Knowledge Base File
add_action('admin_init', 'aiohm_kb_generate_file');

function aiohm_kb_generate_file() {
    if (isset($_POST['aiohm_generate_kb'])) {
        $selected = (array) get_option('aiohm_kb_post_types', []);
        $kb = [];

        foreach ($selected as $type) {
            $posts = get_posts(['post_type' => $type, 'numberposts' => -1]);
            foreach ($posts as $post) {
                $kb[] = [
                    'type' => $type,
                    'title' => get_the_title($post),
                    'content' => wp_strip_all_tags($post->post_content)
                ];
            }
        }

        $file = plugin_dir_path(__FILE__) . 'kb.json';
        file_put_contents($file, json_encode($kb, JSON_PRETTY_PRINT));
    }
}

// Shortcode UI for Assistant
add_shortcode('aiohm_kb_assistant', 'aiohm_kb_assistant_ui');

function aiohm_kb_assistant_ui() {
    ob_start();
    ?>
    <div id="aiohm-kb-chat">
        <input type="text" id="aiohm-user-prompt" placeholder="Ask me something..." style="width:100%; padding:10px;" />
        <button onclick="aiohmAsk()" style="margin-top:10px;">Ask</button>
        <div id="aiohm-kb-response" style="margin-top:15px; background:#f1f1f1; padding:10px;"></div>
    </div>

    <script>
    function aiohmAsk() {
        const prompt = document.getElementById('aiohm-user-prompt').value;
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=aiohm_query_kb&prompt=' + encodeURIComponent(prompt))
        .then(response => response.text())
        .then(data => {
            document.getElementById('aiohm-kb-response').innerHTML = data;
        });
    }
    </script>
    <?php
    return ob_get_clean();
}

// AJAX: GPT + Smart KB Scoring
add_action('wp_ajax_aiohm_query_kb', 'aiohm_query_kb');
add_action('wp_ajax_nopriv_aiohm_query_kb', 'aiohm_query_kb');

function aiohm_query_kb() {
    $prompt = sanitize_text_field($_GET['prompt']);
    $kb_file = plugin_dir_path(__FILE__) . 'kb.json';
    $api_key = get_option('aiohm_openai_api_key');

    if (!$api_key) {
        echo "OpenAI API key not set.";
        wp_die();
    }

    if (!file_exists($kb_file)) {
        echo "No Knowledge Base found.";
        wp_die();
    }

    $kb = json_decode(file_get_contents($kb_file), true);
    $prompt_tokens = explode(' ', strtolower($prompt));
    $best_score = 0;
    $best_entry = null;

    foreach ($kb as $entry) {
        $entry_tokens = explode(' ', strtolower($entry['content']));
        $match_count = count(array_intersect($prompt_tokens, $entry_tokens));

        if ($match_count > $best_score) {
            $best_score = $match_count;
            $best_entry = $entry;
        }
    }

    if ($best_entry) {
        $context = "Based on this brand content:\n\n" . $best_entry['content'] . "\n\nUser asked: $prompt";
    } else {
        $context = "User prompt: $prompt";
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a soulful brand assistant. Be concise, emotionally intelligent, and brand-aligned.'],
                ['role' => 'user', 'content' => $context],
            ],
            'temperature' => 0.7,
        ])
    ]);

    if (is_wp_error($response)) {
        echo "Error calling OpenAI: " . $response->get_error_message();
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        echo $body['choices'][0]['message']['content'] ?? "No response from AI.";
    }

    wp_die();
}
