<?php
// includes/shortcode-chat.php

// Register shortcode: [aiohm_chat]
add_shortcode('aiohm_chat', 'aiohm_render_chat_box');

function aiohm_render_chat_box($atts) {
    wp_enqueue_script('aiohm-chat');
    wp_enqueue_style('aiohm-chat');

    ob_start();
    ?>
    <div id="aiohm-chat-container">
        <div id="aiohm-chat-box" style="border:1px solid #ccc; padding:10px; max-width:400px;">
            <div id="aiohm-chat-messages" style="height:200px; overflow-y:auto; background:#f9f9f9; padding:5px; margin-bottom:10px;"></div>
            <input type="text" id="aiohm-chat-input" placeholder="Ask something..." style="width:80%;">
            <button onclick="aiohmSendMessage()">Send</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_action('wp_footer', function() {
    ?>
    <script>
    function aiohmSendMessage() {
        const input = document.getElementById('aiohm-chat-input');
        const messages = document.getElementById('aiohm-chat-messages');
        const text = input.value;
        if (!text) return;

        messages.innerHTML += '<div><strong>You:</strong> ' + text + '</div>';
        input.value = '';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=aiohm_chat_query&message=' + encodeURIComponent(text)
        })
        .then(res => res.json())
        .then(data => {
            messages.innerHTML += '<div><strong>AI:</strong> ' + data.reply + '</div>';
            messages.scrollTop = messages.scrollHeight;
        });
    }
    </script>
    <?php
});

add_action('wp_ajax_aiohm_chat_query', 'aiohm_handle_chat_query');
add_action('wp_ajax_nopriv_aiohm_chat_query', 'aiohm_handle_chat_query');

function aiohm_handle_chat_query() {
    $message = sanitize_text_field($_POST['message'] ?? '');
    $response = 'Let me reflect on that...';

    if ($message) {
        $response = apply_filters('aiohm_chat_response', $message);
    }

    wp_send_json(['reply' => $response]);
}
