<?php
if (defined('AI_BLOG_WRITER_LOADED')) {
    return; // Prevent double load if another copy exists
}
define('AI_BLOG_WRITER_LOADED', 1);
/*
Plugin Name: AI Blog Writer
Text Domain: ai-blog-writer
Domain Path: /languages
Description: GPT-4 ve DALL·E ile otomatik/manuel özgün blog içerikleri + görsel üretir. WebP desteği içerir.
Version: 1.1
Author: Osman Doğan
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants for better organization
define('AI_BLOG_WRITER_VERSION', '1.1');
define('AI_BLOG_WRITER_PATH', plugin_dir_path(__FILE__));
define('AI_BLOG_WRITER_URL', plugin_dir_url(__FILE__));

// Load required files
require_once AI_BLOG_WRITER_PATH . 'includes/functions.php';
require_once AI_BLOG_WRITER_PATH . 'includes/settings.php';
require_once AI_BLOG_WRITER_PATH . 'includes/admin-pages.php';
require_once AI_BLOG_WRITER_PATH . 'includes/cron.php';
require_once AI_BLOG_WRITER_PATH . 'includes/ajax.php';

// Initialize the plugin
add_action('init', 'ai_blog_writer_init');

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'ai_blog_writer_activation');
register_deactivation_hook(__FILE__, 'ai_blog_writer_deactivation');

function ai_blog_writer_init() {
    // Load text domain for translations
    load_plugin_textdomain('ai-blog-writer', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Include necessary admin files only in admin area
    if (is_admin()) {
        // Media functions (requires)
        if (!function_exists('download_url'))           require_once(ABSPATH . 'wp-admin/includes/file.php');
        if (!function_exists('media_handle_sideload'))  require_once(ABSPATH . 'wp-admin/includes/media.php');
        if (!function_exists('wp_generate_attachment_metadata')) require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
}

function ai_blog_writer_activation() {
    ai_blog_schedule_cron();
}

function ai_blog_writer_deactivation() {
    wp_clear_scheduled_hook('ai_blog_saatlik_uretim');
}

// Yeni ek: Admin CSS'i enqueue et
add_action('admin_enqueue_scripts', 'ai_blog_enqueue_admin_styles');
function ai_blog_enqueue_admin_styles($hook) {
    // Sadece eklenti sayfalarında yükle
    if (strpos($hook, 'ai-blog') !== false) {
        wp_enqueue_style('ai-blog-admin-css', AI_BLOG_WRITER_URL . 'assets/css/admin-style.css', [], AI_BLOG_WRITER_VERSION);
    }
}

// Inject FAQ schema to Rank Math JSON-LD if our post meta exists
add_filter('rank_math/json_ld', 'ai_blog_rankmath_jsonld', 20, 2);
function ai_blog_rankmath_jsonld($data, $jsonld) {
    if (!is_singular('post')) return $data;
    $post_id = get_queried_object_id();
    $faq = get_post_meta($post_id, '_ai_blog_faq', true);
    if (!$faq || !is_array($faq) || empty($faq)) return $data;
    $faq_items = [];
    foreach ($faq as $row) {
        if (empty($row['q']) || empty($row['a'])) continue;
        $faq_items[] = [
            '@type' => 'Question',
            'name' => wp_strip_all_tags($row['q']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => wp_kses_post($row['a'])
            ]
        ];
    }
    if (empty($faq_items)) return $data;
    $data['FAQPage'] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faq_items
    ];
    return $data;
}


// Auto-assign Rank Math focus keyword after post creation
add_action('save_post_post', 'ai_blog_auto_focus_keyword', 20, 3);
function ai_blog_auto_focus_keyword($post_ID, $post, $update) {
    // Only run on initial insert or if meta is empty
    if (wp_is_post_revision($post_ID)) return;
    if (get_post_meta($post_ID, 'rank_math_focus_keyword', true)) return;
    $title = get_the_title($post_ID);
    if ($title) {
        update_post_meta($post_ID, 'rank_math_focus_keyword', $title);
    }
}
