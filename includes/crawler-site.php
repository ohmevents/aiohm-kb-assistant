<?php
defined('ABSPATH') || exit;

/**
 * Extracts content from WordPress posts, pages, and menus.
 * Returns array of [ 'source' => title_or_url, 'content' => text ]
 */
function aiohm_kb_crawl_site_content() {
    $content = [];

    // 1. Pages & Posts
    $args = [
        'post_type'      => ['page', 'post'],
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $posts = get_posts($args);

    foreach ($posts as $post) {
        $text = strip_tags(apply_filters('the_content', $post->post_content));
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (!empty($text)) {
            $content[] = [
                'source'  => get_permalink($post),
                'title'   => get_the_title($post),
                'content' => mb_substr($text, 0, 5000),
            ];
        }
    }

    // 2. Menus (optional, experimental)
    $locations = get_nav_menu_locations();
    foreach ($locations as $location => $menu_id) {
        $items = wp_get_nav_menu_items($menu_id);
        foreach ($items as $item) {
            if ($item->type === 'post_type' && $item->object === 'page') {
                $page = get_post($item->object_id);
                if ($page) {
                    $text = strip_tags(apply_filters('the_content', $page->post_content));
                    $text = trim(preg_replace('/\s+/', ' ', $text));
                    if (!empty($text)) {
                        $content[] = [
                            'source'  => $item->url,
                            'title'   => $item->title,
                            'content' => mb_substr($text, 0, 5000),
                        ];
                    }
                }
            }
        }
    }

    return $content;
}
