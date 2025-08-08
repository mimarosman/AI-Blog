<?php
// includes/cron.php
if (!defined('ABSPATH')) exit;

// Cron bağlamında gerekli admin dosyalarını dahil et (download_url hatasını önlemek için)
if (!function_exists('download_url')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
}
if (!function_exists('media_handle_sideload')) {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
}
if (!function_exists('wp_generate_attachment_metadata')) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
}

// Schedule cron on option updates
add_action('update_option_ai_blog_otomatik_acik', 'ai_blog_schedule_cron');
add_action('update_option_ai_blog_otomatik_sure', 'ai_blog_schedule_cron');

// Schedule cron function
function ai_blog_schedule_cron() {
    $acik = get_option('ai_blog_otomatik_acik', 0);
    $sure = get_option('ai_blog_otomatik_sure', 'thirty_minutes');
    wp_clear_scheduled_hook('ai_blog_saatlik_uretim');
    if ($acik && !wp_next_scheduled('ai_blog_saatlik_uretim')) {
        wp_schedule_event(time(), $sure, 'ai_blog_saatlik_uretim');
    }
    // Instant Indexing entegrasyonu
    if (function_exists('instant_indexing_submit')) {
        try { instant_indexing_submit([$postId]); } catch (Throwable $e) { ai_blog_log('warn', 'Instant Indexing hatası', ['e' => $e->getMessage()]); }
    }
}

// Automatic content generation
add_action('ai_blog_saatlik_uretim', 'ai_blog_otomatik_uretim');

function ai_blog_otomatik_uretim() {
    // Rate limit: dakikada 1
    $last = (int) get_option('ai_blog_last_run', 0);
    if ($last && (time() - $last) < 60) { return; }
    update_option('ai_blog_last_run', time());
    if (!get_option('ai_blog_otomatik_acik')) return;
    $index = ai_blog_sonraki_konu_index();
    if ($index === false) return;

    $liste = ai_blog_konu_listesi();
    $konu = $liste[$index] ?? '';
    $apiKey = get_option('ai_blog_openai_key');
    if (!$apiKey || !$konu) return;

    $min_kelime = (int) get_option('ai_blog_min_kelime', 1000);
    $max_kelime = max($min_kelime, 1500);
    $model = get_option('ai_blog_model', 'gpt-4o');
    $prompt = get_option('ai_blog_prompt', '');
    $gorsel_istege_bagli = get_option('ai_blog_gorsel_istege_bagli', 1);

    $icerik = ai_blog_gpt_uret($apiKey, $konu, $min_kelime, $max_kelime, $model, $prompt);

    // SEO meta üret
    $icerik_excerpt = substr(strip_tags($icerik), 0, 200);
    $seo_meta = ai_blog_generate_seo_meta($apiKey, $konu, $icerik_excerpt, $model);

    $gorselId = 0;
    $gorselUrl = '';
    if ($gorsel_istege_bagli) {
        // Görsel ayarlarını ayarlardan oku
        $gorsel_stil = get_option('ai_blog_gorsel_stil', 'modern');
        $gorsel_boyut = get_option('ai_blog_gorsel_boyut', '1024x1024');
        $default_gorsel_prompt = get_option('ai_blog_gorsel_prompt', '{KONU} için modern, beyaz arka planlı detaylı bir illüstrasyon');
        $gorsel_prompt = str_replace('{KONU}', $konu, $default_gorsel_prompt);

        $gorselUrl = ai_blog_dalle_uret($apiKey, $gorsel_prompt, $gorsel_boyut);
        if ($gorselUrl) {
            $gorselId = ai_blog_media_handle($gorselUrl, "$konu-gorsel.jpg");
        }
    }
    $etiketler = ai_blog_gpt_tags($apiKey, $konu, $model);

    $postId = wp_insert_post([
        'post_title'   => $seo_meta['title'] ?? $konu,
        'post_name'    => ai_blog_slugify_tr($seo_meta['title'] ?? $konu),
        'post_content' => $icerik,
        'post_status'  => 'publish',
        'post_author'  => 1
    ]);
    if ($gorselId) set_post_thumbnail($postId, $gorselId);
    if ($etiketler) wp_set_post_tags($postId, $etiketler);

    // Extract simple FAQ pairs from content
    $faq = [];
    $lines = preg_split('/\r?\n/', wp_strip_all_tags($icerik));
    for ($i=0; $i<count($lines)-1; $i++) {
        if (preg_match('/^\s*Soru\s*:\s*(.+)/iu', $lines[$i], $m1) && preg_match('/^\s*Cevap\s*:\s*(.+)/iu', $lines[$i+1], $m2)) {
            $faq[] = ['q' => trim($m1[1]), 'a' => trim($m2[1])];
            $i++;
            if (count($faq) >= 5) break;
        }
    }
    if (!empty($faq)) update_post_meta($postId, '_ai_blog_faq', $faq);


    update_post_meta($postId, '_ai_blog_writer', 1);

    // SEO meta kaydet (Yoast entegrasyonu)
    if (function_exists('wpseo_replace_vars')) {
        update_post_meta($postId, '_yoast_wpseo_title', $seo_meta['title']);
        update_post_meta($postId, '_yoast_wpseo_metadesc', $seo_meta['description']);
        update_post_meta($postId, '_yoast_wpseo_focuskw', implode(',', $seo_meta['keywords']));
    }
    // Instant Indexing entegrasyonu
    if (function_exists('instant_indexing_submit')) {
        try { instant_indexing_submit([$postId]); } catch (Throwable $e) { ai_blog_log('warn', 'Instant Indexing hatası', ['e' => $e->getMessage()]); }
    }
}