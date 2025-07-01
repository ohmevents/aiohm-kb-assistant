<?php
// includes/shortcode-search.php

function aiohm_vector_search_shortcode() {
    ob_start();
    ?>
    <div class="aiohm-search-box">
        <input type="text" id="aiohm_query" placeholder="Ask your assistant..." style="width: 100%; padding: 8px;">
        <button onclick="aiohmRunVectorSearch()" style="margin-top: 8px;">Search</button>
        <div id="aiohm_search_results" style="margin-top: 16px;"></div>
    </div>
    <script>
    function aiohmRunVectorSearch() {
        const query = document.getElementById('aiohm_query').value;
        const resultsDiv = document.getElementById('aiohm_search_results');
        resultsDiv.innerHTML = 'Thinking...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=aiohm_vector_search&query=' + encodeURIComponent(query)
        })
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = data.length
                ? '<ul>' + data.map(d => '<li>' + d.text + '</li>').join('') + '</ul>'
                : 'No matching content found.';
        });
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('aiohm_vector_search', 'aiohm_vector_search_shortcode');

add_action('wp_ajax_aiohm_vector_search', 'aiohm_vector_search_ajax');
add_action('wp_ajax_nopriv_aiohm_vector_search', 'aiohm_vector_search_ajax');
function aiohm_vector_search_ajax() {
    $query = sanitize_text_field($_POST['query'] ?? '');
    $results = function_exists('aiohm_search_vectors') ? aiohm_search_vectors($query) : [];
    wp_send_json($results);
}
