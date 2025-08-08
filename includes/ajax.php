<?php
// includes/ajax.php
if (!defined('ABSPATH')) exit;

// AJAX for resetting index
add_action('wp_ajax_ai_blog_reset_index', 'ai_blog_ajax_reset_index');

function ai_blog_ajax_reset_index() {
    check_ajax_referer('ai_blog_reset_index_nonce', 'nonce');
    update_option('ai_blog_konu_index', 0);
    wp_send_json_success('OK');
}