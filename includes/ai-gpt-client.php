<?php
// includes/ai-gpt-client.php

function aiohm_send_to_openai($json_path) {
    $api_key = get_option('aiohm_openai_key');
    if (!$api_key || !file_exists($json_path)) return false;

    $data = json_decode(file_get_contents($json_path), true);
    $chunks = array_map(function($entry) {
        return [
            'role' => 'user',
            'content' => "Title: {$entry['title']}\n\nContent: {$entry['content']}"
        ];
    }, $data);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => array_merge([
                ["role" => "system", "content" => "Summarize the following website content for training in a knowledge assistant."]
            ], $chunks),
            'temperature' => 0.5
        ])
    ]);

    if (is_wp_error($response)) return false;
    return wp_remote_retrieve_body($response);
}

add_action('aiohm_scan_completed', 'aiohm_process_scan_with_gpt');
function aiohm_process_scan_with_gpt($json_path) {
    $summary = aiohm_send_to_openai($json_path);
    if ($summary) {
        $out_path = str_replace('site-scan', 'summary', $json_path);
        file_put_contents($out_path, $summary);

        do_action('aiohm_summary_ready', $out_path);
    }
}

add_action('aiohm_summary_ready', 'aiohm_embed_summary_into_vector_db');
function aiohm_embed_summary_into_vector_db($summary_path) {
    $raw = file_get_contents($summary_path);
    $api_key = get_option('aiohm_openai_key');
    if (!$api_key || !$raw) return;

    $chunks = explode("\n\n", strip_tags($raw));
    $vector_data = [];

    foreach ($chunks as $chunk) {
        $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode([
                'model' => 'text-embedding-ada-002',
                'input' => $chunk
            ])
        ]);

        if (!is_wp_error($response)) {
            $embedding = json_decode(wp_remote_retrieve_body($response), true);
            $vector_data[] = [
                'text' => $chunk,
                'embedding' => $embedding['data'][0]['embedding'] ?? []
            ];
        }
    }

    $vector_path = str_replace('summary', 'vector', $summary_path);
    file_put_contents($vector_path, json_encode($vector_data, JSON_PRETTY_PRINT));

    // Optional retrieval logic
    update_option('aiohm_last_vector_path', $vector_path);
}

function aiohm_search_vectors($query) {
    $vector_path = get_option('aiohm_last_vector_path');
    if (!$vector_path || !file_exists($vector_path)) return [];

    $vector_data = json_decode(file_get_contents($vector_path), true);
    $api_key = get_option('aiohm_openai_key');
    if (!$api_key || !$query) return [];

    // Get embedding for query
    $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'text-embedding-ada-002',
            'input' => $query
        ])
    ]);

    if (is_wp_error($response)) return [];
    $query_embedding = json_decode(wp_remote_retrieve_body($response), true)['data'][0]['embedding'] ?? [];

    // Cosine similarity
    $scores = [];
    foreach ($vector_data as $item) {
        $dot = $norm_a = $norm_b = 0;
        for ($i = 0; $i < count($query_embedding); $i++) {
            $dot += $query_embedding[$i] * $item['embedding'][$i];
            $norm_a += pow($query_embedding[$i], 2);
            $norm_b += pow($item['embedding'][$i], 2);
        }
        $cos_sim = $dot / (sqrt($norm_a) * sqrt($norm_b));
        $scores[] = ['text' => $item['text'], 'score' => $cos_sim];
    }

    usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($scores, 0, 5);
}
