<?php
// includes/functions.php
if (!defined('ABSPATH')) exit;

// Helper function for topic list
function ai_blog_konu_listesi() {
    $list = get_option('ai_blog_konu_listesi', '');
    $lines = array_filter(array_map('trim', explode("\n", $list)));
    return array_values($lines);
}

// GPT content generation
function ai_blog_gpt_uret($apiKey, $konu, $min_kelime = 1000, $max_kelime = 1500, $model = null, $prompt = null) {
    if (!$model)  $model  = get_option('ai_blog_model', 'gpt-4o');
    if (!$prompt) $prompt = get_option('ai_blog_prompt', '');
    $max_tokens = ceil($max_kelime * 1.5);
    $prompt     = str_replace("{KONU}", $konu, $prompt);

    $chatData = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Sen uzun süredir Türkçe içerik üreten deneyimli bir blog yazarı ve SEO uzmanısın."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.97,
        "max_tokens" => $max_tokens
    ];

    $response = ai_blog_openai_api_call('chat/completions', $apiKey, $chatData);
    if (!$response) return '';

    $result = json_decode($response, true);
    if (empty($result['choices'][0]['message']['content'])) {
        error_log("OpenAI GPT beklenmeyen yanıt: $response");
        return '';
    }
    $icerik = $result['choices'][0]['message']['content'];
    $icerik = preg_replace('/^(`{3,}|\'{3,})html\s*/', '', $icerik);
    $icerik = preg_replace('/^(`{3,}|\'{3,})\s*/', '', $icerik);
    $icerik = preg_replace('/(`{3,}|\'{3,})$/', '', $icerik);
    return trim($icerik);
}

// GPT tags generation
function ai_blog_gpt_tags($apiKey, $konu, $model = null) {
    if (!$model) $model = get_option('ai_blog_model', 'gpt-4o');
    $prompt = "KONU: $konu\nBu konuda 5 kısa, Türkçe, SEO uyumlu etiket öner. Sadece virgülle ayır.";

    $chatData = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Sen bir Türk içerik yazarı ve SEO uzmanısın."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.4,
        "max_tokens" => 100
    ];

    $response = ai_blog_openai_api_call('chat/completions', $apiKey, $chatData);
    if (!$response) return [];

    $result = json_decode($response, true);
    $tags_str = $result['choices'][0]['message']['content'] ?? '';
    $tags = array_map('trim', explode(',', $tags_str));
    return array_filter($tags);
}

// DALL-E image generation
function ai_blog_dalle_uret($apiKey, $prompt, $size = '1024x1024') {
    $data = ["model" => "dall-e-3", "prompt" => $prompt, "n" => 1, "size" => $size];

    $response = ai_blog_openai_api_call('images/generations', $apiKey, $data);
    if (!$response) return null;

    $json = json_decode($response, true);
    return $json['data'][0]['url'] ?? null;
}

// Centralized OpenAI API call for reusability and error handling
function ai_blog_openai_api_call($endpoint, $apiKey, $data) {
    $url = "https://api.openai.com/v1/$endpoint";
    $attempts = 0;
    $max_attempts = 3;
    $delay = 2; // seconds

    while ($attempts < $max_attempts) {
        $attempts++;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($errno) {
            error_log("OpenAI API hata ($endpoint): " . curl_error($ch));
        }
        curl_close($ch);

        if (!$errno && $response && $status >= 200 && $status < 300) {
            return $response;
        }

        // backoff
        sleep($delay);
        $delay *= 2;
    }
    return null;
}
// Media handling with improvements
function ai_blog_media_handle($url_or_path, $filename, $parent_post_id = 0, $context = []) {
    $is_temp = false;
    if (filter_var($url_or_path, FILTER_VALIDATE_URL)) {
        $tmpfile = download_url($url_or_path);
        if (is_wp_error($tmpfile)) {
            error_log("Media download error: " . $tmpfile->get_error_message());
            return 0;
        }
        $is_temp = true;
    } else {
        $tmpfile = $url_or_path;
    }

    if (get_option('ai_blog_webp_aktif') && function_exists('imagewebp')) {
        $webp = ai_blog_convert_to_webp($tmpfile);
        if ($webp && file_exists($webp)) {
            $tmpfile = $webp;
            $filename = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filename);
        }
    }

    $file_array = ['name' => $filename, 'tmp_name' => $tmpfile];
    $id = media_handle_sideload($file_array, (int) $parent_post_id);

    if ($is_temp) @unlink($tmpfile);
    if (is_wp_error($id)) {
        error_log("Media upload error: " . $id->get_error_message());
        return 0;
    }
    // Alt ve caption üret
    if ($id) {
        $alt = sanitize_text_field($filename);
        if (!empty($context['alt'])) { $alt = sanitize_text_field($context['alt']); }
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
        if (!empty($context['caption'])) {
            wp_update_post([ 'ID' => $id, 'post_excerpt' => sanitize_text_field($context['caption']) ]);
        }
    }
    return $id;
}

// WebP conversion
function ai_blog_convert_to_webp($image_path) {
    if (!function_exists('imagewebp')) {
        error_log('GD extension veya WebP desteği yok.');
        return false;
    }
    $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = imagecreatefromjpeg($image_path);
            break;
        case 'png':
            $img = imagecreatefrompng($image_path);
            break;
        default:
            return false;
    }

    if (!$img) return false;

    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);
    imagewebp($img, $webp_path, 85);
    imagedestroy($img);
    return $webp_path;
}

// Get next topic index
function ai_blog_sonraki_konu_index() {
    $liste = ai_blog_konu_listesi();
    $index = (int) get_option('ai_blog_konu_index', 0);
    if ($index >= count($liste) || count($liste) == 0) {
        update_option('ai_blog_otomatik_acik', 0);
        update_option('ai_blog_konu_bitti_uyari', 1);
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            __('AI Blog Writer - Konu Listesi Bitti!', 'ai-blog-writer'),
            sprintf(__("Merhaba,\n\n%s sitesindeki AI Blog Writer eklentisinin konu listesi tükendi. Lütfen yeni konular ekleyip tekrar başlatın.", "ai-blog-writer"), get_bloginfo('name'))
        );
        return false;
    }
    update_option('ai_blog_konu_index', $index + 1);
    return $index;
}

// Yeni: SEO meta üretimi (GPT ile)
function ai_blog_generate_seo_meta($apiKey, $konu, $icerik_excerpt, $model = null) {
    if (!$model) $model = get_option('ai_blog_model', 'gpt-4o');
    $prompt = "KONU: $konu\nİÇERİK ÖZETİ: $icerik_excerpt\nBu konu için SEO uyumlu meta title (max 60 karakter), meta description (max 160 karakter) ve 3-5 focus keyword öner. JSON formatında dön: {\"title\": \"...\", \"description\": \"...\", \"keywords\": [\"...\", \"...\"]}";

    $chatData = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Sen bir SEO uzmanısın."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.7,
        "max_tokens" => 200
    ];

    $response = ai_blog_openai_api_call('chat/completions', $apiKey, $chatData);
    if (!$response) return ['title' => $konu, 'description' => '', 'keywords' => []];

    $result = json_decode($response, true);
    $meta_str = $result['choices'][0]['message']['content'] ?? '';
    $meta = json_decode($meta_str, true) ?? ['title' => $konu, 'description' => '', 'keywords' => []];
    return $meta;
}

// Basit üretim log fonksiyonları
function ai_blog_log($level, $message, $context = []) {
    $logs = get_option('ai_blog_logs', []);
    if (!is_array($logs)) $logs = [];
    $logs[] = [
        'time' => current_time('mysql'),
        'level' => $level,
        'message' => $message,
        'context' => $context
    ];
    // halka buffer: son 200 log
    if (count($logs) > 200) { $logs = array_slice($logs, -200); }
    update_option('ai_blog_logs', $logs);
}


function ai_blog_slugify_tr($title) {
    $map = ['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ç'=>'c','Ç'=>'c','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ğ'=>'g','Ğ'=>'g'];
    $t = strtr($title, $map);
    $t = strtolower($t);
    $t = preg_replace('~[^a-z0-9\s-]~', '', $t);
    $t = preg_replace('~\s+~', '-', trim($t));
    $t = preg_replace('~-+~', '-', $t);
    // stop words (kısa liste)
    $stops = ['ve','ile','veya','ile','için','gibi','çok','bir','bu','şu','o','mi','mı','mu','mü'];
    $parts = array_filter(explode('-', $t), function($p) use ($stops){ return $p !== '' && !in_array($p, $stops); });
    $slug = implode('-', $parts);
    return substr($slug, 0, 80);
}
