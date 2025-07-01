<?php
// includes/crawler-site.php

add_action('aiohm_trigger_site_scan', 'aiohm_run_site_scan');

function aiohm_run_site_scan() {
    $output = [];
    $message = '';

    try {
        $args = [
            'post_type' => ['page', 'post'],
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        $posts = get_posts($args);
        $total = count($posts);
        $count = 0;

        foreach ($posts as $post) {
            $output[] = [
                'title' => get_the_title($post->ID),
                'url' => get_permalink($post->ID),
                'content' => wp_strip_all_tags($post->post_content)
            ];
            $count++;
            update_option('aiohm_scan_progress', intval(($count / $total) * 100));
        }

        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            $items = wp_get_nav_menu_items($menu);
            foreach ($items as $item) {
                if ($item->type === 'post_type') {
                    $output[] = [
                        'title' => $item->title,
                        'url' => $item->url,
                        'content' => ''
                    ];
                }
            }
        }

        set_transient('aiohm_last_scan_data', $output, 60 * 10);
        delete_option('aiohm_scan_progress');
        $message = '✅ Scan completed: ' . count($output) . ' items found.';
    } catch (Exception $e) {
        $message = '❌ Scan error: ' . $e->getMessage();
    }

    update_option('aiohm_scan_notice', $message);
}

add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'aiohm-scan-website') {
        $notice = get_option('aiohm_scan_notice');
        if ($notice) {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($notice) . '</p></div>';
            delete_option('aiohm_scan_notice');
        }
    }
});

add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'aiohm-scan-website') {
        $data = get_transient('aiohm_last_scan_data');
        $progress = get_option('aiohm_scan_progress');

        echo '<div class="wrap" style="margin-top: 30px; max-width: 100%;">';

        if ($progress) {
            echo '<div style="background:#f0f0f0;border:1px solid #ccc;border-radius:5px;height:20px;width:100%;margin-bottom:20px;">
                    <div style="width:' . intval($progress) . '%;background:#4caf50;height:100%;text-align:center;color:white;font-size:12px;line-height:20px;">' . intval($progress) . '%</div>
                  </div>';
        }

        if ($data && is_array($data)) {
            echo '<h2 style="margin-top: 30px;">Scanned Items</h2>';
            echo '<div style="overflow-x: auto; max-width: 100%; margin-top: 20px; margin-bottom: 20px;"><table class="widefat fixed striped" style="width: 100%; min-width: 960px;">
                    <thead><tr><th style="width:25%">Title</th><th style="width:25%">URL</th><th style="width:50%">Content Excerpt</th></tr></thead><tbody>';
            foreach ($data as $item) {
                echo '<tr><td>' . esc_html($item['title']) . '</td>';
                echo '<td><a href="' . esc_url($item['url']) . '" target="_blank">View</a></td>';
                echo '<td style="word-break: break-word;">' . esc_html(wp_trim_words($item['content'], 40)) . '</td></tr>';
            }
            echo '</tbody></table></div>';

            echo '<form method="post" style="margin-top:20px;">';
            echo '<input type="hidden" name="aiohm_save_to_kb" value="1">';
            submit_button('📥 Save to Knowledge Base');
            echo '</form>';

            if (!empty($_POST['aiohm_save_to_kb'])) {
                do_action('aiohm_summary_ready', $data);
                echo '<div class="notice notice-success is-dismissible" style="margin-top:20px;"><p>✅ Data sent to Knowledge Base embedding process.</p></div>';
            }
        }

        echo '</div>';
    }
});
