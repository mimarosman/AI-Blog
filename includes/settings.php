<?php
// includes/settings.php
if (!defined('ABSPATH')) exit;

// Register settings
add_action('admin_init', 'ai_blog_register_settings');

function ai_blog_register_settings() {
    register_setting('ai_blog_settings_group', 'ai_blog_openai_key', 'sanitize_text_field'); // Sanitize API key
    register_setting('ai_blog_settings_group', 'ai_blog_konu_listesi', 'ai_blog_sanitize_konu_listesi');
    register_setting('ai_blog_settings_group', 'ai_blog_otomatik_acik', 'intval');
    register_setting('ai_blog_settings_group', 'ai_blog_otomatik_sure', 'sanitize_text_field');
    register_setting('ai_blog_settings_group', 'ai_blog_min_kelime', 'intval');
    register_setting('ai_blog_settings_group', 'ai_blog_model', 'sanitize_text_field');
    register_setting('ai_blog_settings_group', 'ai_blog_prompt', 'sanitize_textarea_field');
    register_setting('ai_blog_settings_group', 'ai_blog_webp_aktif', 'intval'); // WP Rocket/Smush/EWWW kullanıyorsanız kapalı tutun
    register_setting('ai_blog_settings_group', 'ai_blog_moderation_on', 'intval');
    register_setting('ai_blog_settings_group', 'ai_blog_gorsel_istege_bagli', 'intval');

    // Yeni: Görsel ayarları
    register_setting('ai_blog_settings_group', 'ai_blog_gorsel_stil', 'sanitize_text_field');
    register_setting('ai_blog_settings_group', 'ai_blog_gorsel_boyut', 'sanitize_text_field');
    register_setting('ai_blog_settings_group', 'ai_blog_gorsel_prompt', 'sanitize_textarea_field');
}

// Sanitize topic list
function ai_blog_sanitize_konu_listesi($input) {
    $lines = explode("\n", $input);
    $sanitized = array_map('sanitize_text_field', $lines);
    return implode("\n", $sanitized);
}

// Reset index when topic list changes
add_action('update_option_ai_blog_konu_listesi', 'ai_blog_reset_index_on_change', 10, 2);

function ai_blog_reset_index_on_change($old, $new) {
    if ($old !== $new) {
        update_option('ai_blog_konu_index', 0);
    }
}

// Cron schedules
add_filter('cron_schedules', 'ai_blog_add_cron_schedules');

function ai_blog_add_cron_schedules($schedules) {
    $schedules['five_minutes']     = ['interval' => 300,   'display' => __('Her 5 Dakika', 'ai-blog-writer')];
    $schedules['fifteen_minutes']  = ['interval' => 900,   'display' => __('Her 15 Dakika', 'ai-blog-writer')];
    $schedules['thirty_minutes']   = ['interval' => 1800,  'display' => __('Her 30 Dakika', 'ai-blog-writer')];
    $schedules['hourly']           = ['interval' => 3600,  'display' => __('Her 1 Saat', 'ai-blog-writer')];
    $schedules['six_hours']        = ['interval' => 21600, 'display' => __('Her 6 Saat', 'ai-blog-writer')];
    $schedules['daily']            = ['interval' => 86400, 'display' => __('Günde 1', 'ai-blog-writer')];
    return $schedules;
}