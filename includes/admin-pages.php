<?php
// includes/admin-pages.php
if (!defined('ABSPATH')) exit;

// Add admin menus
add_action('admin_menu', 'ai_blog_add_admin_menus');

function ai_blog_add_admin_menus() {
    add_menu_page(
        __('AI Blog Üret', 'ai-blog-writer'),
        __('AI Blog Üret', 'ai-blog-writer'),
        'manage_options',
        'ai-blog-writer-produce',
        'ai_blog_writer_admin_form',
        'dashicons-edit',
        27
    );

    add_submenu_page(
        'ai-blog-writer-produce',
        __('Konu Takip', 'ai-blog-writer'),
        __('Konu Takip', 'ai-blog-writer'),
        'manage_options',
        'ai-blog-writer-tracker',
        'ai_blog_konu_takip_sayfa'
    );

    add_submenu_page(
        'ai-blog-writer-produce',
        __('Onay Kuyruğu', 'ai-blog-writer'),
        __('Onay Kuyruğu', 'ai-blog-writer'),
        'manage_options',
        'ai-blog-queue',
        'ai_blog_queue_page'
    );

    add_submenu_page(
        'ai-blog-writer-produce',
        __('Üretim Günlükleri', 'ai-blog-writer'),
        __('Günlükler', 'ai-blog-writer'),
        'manage_options',
        'ai-blog-logs',
        'ai_blog_logs_page'
    );

    add_options_page(
        __('AI Blog Ayarları', 'ai-blog-writer'),
        __('AI Blog Ayarları', 'ai-blog-writer'),
        'manage_options',
        'ai-blog-settings',
        'ai_blog_ayar_sayfasi'
    );
}

// Settings page (Güncellenmiş: Görsel ayarları eklendi)
function ai_blog_ayar_sayfasi() {
    $otomatiksure = get_option('ai_blog_otomatik_sure', 'thirty_minutes');
    $min_kelime   = get_option('ai_blog_min_kelime', 1000);
    $model        = get_option('ai_blog_model', 'gpt-4o');
    $prompt       = get_option('ai_blog_prompt', '');
    $gorsel_istege_bagli = get_option('ai_blog_gorsel_istege_bagli', 1);

    // Yeni: Görsel ayarları
    $gorsel_stil = get_option('ai_blog_gorsel_stil', 'modern');
    $gorsel_boyut = get_option('ai_blog_gorsel_boyut', '1024x1024');
    $gorsel_prompt = get_option('ai_blog_gorsel_prompt', '{KONU} için modern, beyaz arka planlı detaylı bir illüstrasyon');

    ?>
    <div class="wrap">
        <h1><?php _e('AI Blog Writer Ayarları', 'ai-blog-writer'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ai_blog_settings_group');
            do_settings_sections('ai_blog_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('OpenAI API Key', 'ai-blog-writer'); ?></th>
                    <td>
                        <input type="text" name="ai_blog_openai_key" value="<?php echo esc_attr(get_option('ai_blog_openai_key')); ?>" style="width:400px;">
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Konu Listesi', 'ai-blog-writer'); ?></th>
                    <td>
                        <textarea name="ai_blog_konu_listesi" rows="5" style="width:400px;overflow:auto;"><?php echo esc_textarea(get_option('ai_blog_konu_listesi')); ?></textarea>
                        <p><?php _e('Her satıra bir başlık gelecek şekilde konu listenizi girin.', 'ai-blog-writer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Otomatik İçerik Paylaşımı', 'ai-blog-writer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_blog_otomatik_acik" value="1" <?php checked(get_option('ai_blog_otomatik_acik'), 1); ?>>
                            <?php _e('Otomatik içerik paylaşımını aktif et', 'ai-blog-writer'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Otomatik Paylaşım Süresi', 'ai-blog-writer'); ?></th>
                    <td>
                        <select name="ai_blog_otomatik_sure">
                            <option value="five_minutes"     <?php selected($otomatiksure, 'five_minutes'); ?>><?php _e('5 Dakika', 'ai-blog-writer'); ?></option>
                            <option value="fifteen_minutes"  <?php selected($otomatiksure, 'fifteen_minutes'); ?>><?php _e('15 Dakika', 'ai-blog-writer'); ?></option>
                            <option value="thirty_minutes"   <?php selected($otomatiksure, 'thirty_minutes'); ?>><?php _e('30 Dakika', 'ai-blog-writer'); ?></option>
                            <option value="hourly"           <?php selected($otomatiksure, 'hourly'); ?>><?php _e('1 Saat', 'ai-blog-writer'); ?></option>
                            <option value="six_hours"        <?php selected($otomatiksure, 'six_hours'); ?>><?php _e('6 Saat', 'ai-blog-writer'); ?></option>
                            <option value="daily"            <?php selected($otomatiksure, 'daily'); ?>><?php _e('Günde 1', 'ai-blog-writer'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Minimum Kelime Sayısı', 'ai-blog-writer'); ?></th>
                    <td>
                        <input type="number" name="ai_blog_min_kelime" value="<?php echo esc_attr($min_kelime); ?>" min="1000" max="1500" style="width:80px;">
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Otomatik İçerik İçin Model Seçimi', 'ai-blog-writer'); ?></th>
                    <td>
                        <select name="ai_blog_model">
                            <option value="gpt-4o"             <?php selected($model, 'gpt-4o'); ?>><?php _e('GPT-4o (En güncel, hızlı)', 'ai-blog-writer'); ?></option>
                            <option value="gpt-4"              <?php selected($model, 'gpt-4'); ?>><?php _e('GPT-4 (Klasik)', 'ai-blog-writer'); ?></option>
                            <option value="gpt-4-1106-preview" <?php selected($model, 'gpt-4-1106-preview'); ?>><?php _e('GPT-4.1 (Preview)', 'ai-blog-writer'); ?></option>
                            <option value="gpt-3.5-turbo"      <?php selected($model, 'gpt-3.5-turbo'); ?>><?php _e('GPT-3.5 Turbo (Uygun Maliyetli)', 'ai-blog-writer'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Varsayılan GPT Prompt', 'ai-blog-writer'); ?></th>
                    <td>
                        <textarea name="ai_blog_prompt" rows="5" style="width:400px;overflow:auto;"><?php echo esc_textarea($prompt); ?></textarea>
                        <p><?php _e('İçerik üretiminde kullanılacak varsayılan prompt. <b>{KONU}</b> başlık ile otomatik değişir.', 'ai-blog-writer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('WebP Dönüştürme', 'ai-blog-writer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_blog_webp_aktif" value="1" <?php checked(get_option('ai_blog_webp_aktif'), 1); ?>>
                            <?php _e('Görselleri WebP formatına otomatik dönüştür', 'ai-blog-writer'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Otomatikta Görsel Oluşturulsun mu?', 'ai-blog-writer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_blog_gorsel_istege_bagli" value="1" <?php checked($gorsel_istege_bagli, 1); ?>>
                            <?php _e('Evet, otomatik içeriklerde görsel de oluşturulsun', 'ai-blog-writer'); ?>
                        </label>
                    </td>
                </tr>
                <!-- Yeni: Görsel ayarları -->
                <tr>
                    <th><?php _e('Varsayılan Görsel Stil', 'ai-blog-writer'); ?></th>
                    <td>
                        <select name="ai_blog_gorsel_stil">
                            <option value="modern" <?php selected($gorsel_stil, 'modern'); ?>><?php _e('Modern', 'ai-blog-writer'); ?></option>
                            <option value="realistic" <?php selected($gorsel_stil, 'realistic'); ?>><?php _e('Gerçekçi', 'ai-blog-writer'); ?></option>
                            <option value="cartoon" <?php selected($gorsel_stil, 'cartoon'); ?>><?php _e('Karikatür', 'ai-blog-writer'); ?></option>
                            <option value="abstract" <?php selected($gorsel_stil, 'abstract'); ?>><?php _e('Soyut', 'ai-blog-writer'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Varsayılan Görsel Boyut', 'ai-blog-writer'); ?></th>
                    <td>
                        <select name="ai_blog_gorsel_boyut">
                            <option value="1024x1024" <?php selected($gorsel_boyut, '1024x1024'); ?>><?php _e('1024x1024 (Kare)', 'ai-blog-writer'); ?></option>
                            <option value="1792x1024" <?php selected($gorsel_boyut, '1792x1024'); ?>><?php _e('1792x1024 (Yatay)', 'ai-blog-writer'); ?></option>
                            <option value="1024x1792" <?php selected($gorsel_boyut, '1024x1792'); ?>><?php _e('1024x1792 (Dikey)', 'ai-blog-writer'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Varsayılan Görsel Prompt', 'ai-blog-writer'); ?></th>
                    <td>
                        <textarea name="ai_blog_gorsel_prompt" rows="3" style="width:400px;"><?php echo esc_textarea($gorsel_prompt); ?></textarea>
                        <p><?php _e('Görsel üretiminde kullanılacak varsayılan prompt. <b>{KONU}</b> başlık ile otomatik değişir.', 'ai-blog-writer'); ?></p>
                    </td>
                </tr>
                            <tr>
                    <th><?php _e('Onay Kuyruğu (Otomatik Taslak)', 'ai-blog-writer'); ?></th>
                    <td>
                        <label><input type="checkbox" name="ai_blog_moderation_on" value="1" <?php checked((int) get_option('ai_blog_moderation_on', 0), 1); ?>> <?php _e('Otomatik üretilen yazılar yayınlanmadan önce taslakta beklesin', 'ai-blog-writer'); ?></label>
                    </td>
                </tr>
            
                <tr>
                    <th><?php _e('Onay Kuyruğu (Otomatik Taslak)', 'ai-blog-writer'); ?></th>
                    <td>
                        <label><input type="checkbox" name="ai_blog_moderation_on" value="1" <?php checked((int) get_option('ai_blog_moderation_on', 0), 1); ?>> <?php _e('Otomatik üretilen yazılar yayınlanmadan önce taslakta beklesin', 'ai-blog-writer'); ?></label>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Content production form
function ai_blog_writer_admin_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('ai_blog_produce_action', 'ai_blog_produce_nonce');
    }

    $models = [
        "gpt-4o"                => __("GPT-4o (En güncel, hızlı)", 'ai-blog-writer'),
        "gpt-4"                 => __("GPT-4 (Klasik)", 'ai-blog-writer'),
        "gpt-4-1106-preview"    => __("GPT-4.1 (Preview)", 'ai-blog-writer'),
        "gpt-3.5-turbo"         => __("GPT-3.5 Turbo (Uygun Maliyetli)", 'ai-blog-writer')
    ];
    $default_model = get_option('ai_blog_model', 'gpt-4o');
    $default_prompt = get_option('ai_blog_prompt', '');

    ?>
    <div class="wrap">
        <h1><?php _e('AI Blog Writer – İçerik Üret', 'ai-blog-writer'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('ai_blog_produce_action', 'ai_blog_produce_nonce'); ?>
            <label><b><?php _e('Konu başlığı:', 'ai-blog-writer'); ?></b></label><br>
            <input type="text" name="konu" style="width:80%;padding:7px;font-size:16px;" required><br><br>

            <label><b><?php _e('Model Seçimi:', 'ai-blog-writer'); ?></b></label><br>
            <select name="model" style="padding:7px;font-size:16px;width:200px;">
                <?php foreach ($models as $k => $v) : ?>
                    <option value="<?php echo esc_attr($k); ?>" <?php selected($default_model, $k); ?>>
                        <?php echo esc_html($v); ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label><b><?php _e('Prompt (isteğe bağlı):', 'ai-blog-writer'); ?></b></label><br>
            <textarea name="prompt" rows="4" style="width:80%;padding:7px;font-size:14px;" placeholder="<?php esc_attr_e('Varsayılan prompt kullanılacak', 'ai-blog-writer'); ?>"><?php
                echo isset($_POST['prompt']) ? esc_textarea($_POST['prompt']) : '';
            ?></textarea><br>

            <label>
                <input type="checkbox" name="gorsel_olsun" value="1" <?php checked(isset($_POST['gorsel_olsun']) ? $_POST['gorsel_olsun'] : 1, 1); ?>>
                <?php _e('Görsel de oluşturulsun', 'ai-blog-writer'); ?>
            </label>
            <br><br>

            <button type="submit" class="button button-primary" style="margin-top:10px;"><?php _e('Yazı Üret', 'ai-blog-writer'); ?></button>
        </form>
    <?php
    if (!empty($_POST['konu'])) {
        $konu       = sanitize_text_field($_POST['konu']);
        $model      = sanitize_text_field($_POST['model'] ?? $default_model);
        $prompt     = sanitize_textarea_field(trim($_POST['prompt']) ?: $default_prompt);
        $apiKey     = get_option('ai_blog_openai_key');
        $min_kelime = (int) get_option('ai_blog_min_kelime', 1000);
        $max_kelime = max($min_kelime, 1500);
        $gorsel_olsun = isset($_POST['gorsel_olsun']) ? (bool) $_POST['gorsel_olsun'] : true;

        // Görsel ayarlarını ayarlardan oku
        $gorsel_stil = get_option('ai_blog_gorsel_stil', 'modern');
        $gorsel_boyut = get_option('ai_blog_gorsel_boyut', '1024x1024');
        $default_gorsel_prompt = get_option('ai_blog_gorsel_prompt', '{KONU} için modern, beyaz arka planlı detaylı bir illüstrasyon');
        $gorsel_prompt = str_replace('{KONU}', $konu, $default_gorsel_prompt);

        if (!$apiKey) {
            echo '<div style="color:red">' . __('API anahtarını admin panelinden giriniz!', 'ai-blog-writer') . '</div>';
        } else {
            $icerik    = ai_blog_gpt_uret($apiKey, $konu, $min_kelime, $max_kelime, $model, $prompt);
            $gorselId  = 0;
            $gorselUrl = '';
            if ($gorsel_olsun) {
                $gorselUrl = ai_blog_dalle_uret($apiKey, $gorsel_prompt, $gorsel_boyut);
                if ($gorselUrl) {
                    $gorselId = ai_blog_media_handle($gorselUrl, "$konu-gorsel.jpg");
                }
            }
            $etiketler = ai_blog_gpt_tags($apiKey, $konu, $model);

            $postId = wp_insert_post([
                'post_title'   => $konu,
                'post_content' => $icerik,
                'post_status'  => 'draft',
                'post_author'  => get_current_user_id()
            ]);
            if ($gorselId) set_post_thumbnail($postId, $gorselId);
            if ($etiketler) wp_set_post_tags($postId, $etiketler);
            // Extract FAQ for manual posts
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


            echo "<div style='margin:16px 0;padding:12px;background:#f4f6fa;border-radius:8px;'>
                <b>" . __('Yazı taslak olarak kaydedildi.', 'ai-blog-writer') . "</b> 
                <a href='" . get_edit_post_link($postId) . "'>" . __('Düzenle', 'ai-blog-writer') . "</a><br>
                <b>" . __('Görsel:', 'ai-blog-writer') . "</b> " .
                ($gorselId ? wp_get_attachment_image($gorselId, 'medium') : __('Yok', 'ai-blog-writer')) .
            "</div>";
        }
    }
    echo "</div>";
}

// Topic tracker page
function ai_blog_konu_takip_sayfa() {
    $konular = ai_blog_konu_listesi();
    $kullanilan_index = (int) get_option('ai_blog_konu_index', 0);

    echo '<div class="wrap"><h1>' . __('Konu Takip', 'ai-blog-writer') . '</h1>';
    ?>
    <button id="ai-hide-used" class="button"><?php _e('Kullanılanları Gizle/Göster', 'ai-blog-writer'); ?></button>
    <button id="ai-reset-index" class="button" style="margin-left:12px;"><?php _e('Takibi Sıfırla', 'ai-blog-writer'); ?></button>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('ai-hide-used');
        var btnReset = document.getElementById('ai-reset-index');
        var used = document.querySelectorAll('.ai-blog-used');
        var hidden = false;
        btn.onclick = function() {
            hidden = !hidden;
            used.forEach(function(item){ item.style.display = hidden ? 'none' : ''; });
        };
        btnReset.onclick = function() {
            if(confirm('<?php _e('Takibi sıfırlamak istediğine emin misin?', 'ai-blog-writer'); ?>')) {
                var data = new FormData();
                data.append('action', 'ai_blog_reset_index');
                data.append('nonce', '<?php echo wp_create_nonce('ai_blog_reset_index_nonce'); ?>');
                fetch(ajaxurl, {method: 'POST', body: data}).then(function(){ location.reload(); });
            }
        };
    });
    </script>
    <?php

    if (!empty($konular)) {
        echo "<ul style='padding-left:20px;'>";
        foreach ($konular as $i => $baslik) {
            if ($i < $kullanilan_index) {
                echo "<li class='ai-blog-used'><del>" . esc_html($baslik) . "</del></li>";
            } else {
                echo "<li>" . esc_html($baslik) . "</li>";
            }
        }
        echo "</ul>";
        echo "<p><b>" . __('Kullanılan konu sayısı:', 'ai-blog-writer') . "</b> $kullanilan_index / " . count($konular) . "</p>";
    } else {
        echo "<p>" . __('Konu listesi boş.', 'ai-blog-writer') . "</p>";
    }
    echo '</div>';
}

// Admin notice for topic list end
add_action('admin_notices', 'ai_blog_admin_notices');

function ai_blog_admin_notices() {
    if (get_option('ai_blog_konu_bitti_uyari')) {
        echo '<div class="notice notice-warning"><p><strong>'
            . __('AI Blog Writer:', 'ai-blog-writer')
            . '</strong> ' . __('Konu listeniz tükendi!', 'ai-blog-writer')
            . '</p></div>';
        delete_option('ai_blog_konu_bitti_uyari');
    }
}
function ai_blog_logs_page() {
    if (!current_user_can('manage_options')) return;
    $logs = get_option('ai_blog_logs', []);
    echo '<div class="wrap"><h1>' . __('Üretim Günlükleri', 'ai-blog-writer') . '</h1>';
    if (empty($logs)) {
        echo '<p>' . __('Kayıt bulunamadı.', 'ai-blog-writer') . '</p>';
    } else {
        echo '<table class="widefat"><thead><tr><th>Zaman</th><th>Seviye</th><th>Mesaj</th><th>Context</th></tr></thead><tbody>';
        foreach ($logs as $row) {
            echo '<tr><td>' . esc_html($row['time']) . '</td><td>' . esc_html($row['level']) . '</td><td>' . esc_html($row['message']) . '</td><td><code>' . esc_html(json_encode($row['context'])) . '</code></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

function ai_blog_queue_page() {
    if (!current_user_can('manage_options')) return;
    $nonce = wp_create_nonce('ai_blog_queue_actions');
    $args = [
        'post_type' => 'post',
        'post_status' => 'draft',
        'meta_key' => '_ai_blog_writer',
        'meta_value' => 1,
        'posts_per_page' => 20,
    ];
    $q = new WP_Query($args);
    echo '<div class="wrap"><h1>' . __('Onay Kuyruğu', 'ai-blog-writer') . '</h1>';
    if (!$q->have_posts()) {
        echo '<p>' . __('Kuyrukta taslak yok.', 'ai-blog-writer') . '</p></div>';
        return;
    }
    echo '<table class="widefat"><thead><tr><th>ID</th><th>Başlık</th><th>Oluşturulma</th><th>İşlem</th></tr></thead><tbody>';
    while ($q->have_posts()) { $q->the_post();
        $id = get_the_ID();
        $title = get_the_title();
        $date = get_the_date('Y-m-d H:i');
        $approve = admin_url('admin-post.php?action=ai_blog_approve&post_id='.$id.'&_wpnonce='.$nonce);
        $delete = admin_url('admin-post.php?action=ai_blog_delete&post_id='.$id.'&_wpnonce='.$nonce);
        $regen_intro = admin_url('admin-post.php?action=ai_blog_regen_intro&post_id='.$id.'&_wpnonce='.$nonce);
        $regen_outro = admin_url('admin-post.php?action=ai_blog_regen_outro&post_id='.$id.'&_wpnonce='.$nonce);
        echo '<tr><td>'.$id.'</td><td>'.esc_html($title).'</td><td>'.$date.'</td><td>'
            .'<a class="button button-primary" href="'.$approve.'">'.__('Onayla', 'ai-blog-writer').'</a> '
            .'<a class="button" href="'.$regen_intro.'">'.__('Girişi Yeniden Yaz', 'ai-blog-writer').'</a> '
            .'<a class="button" href="'.$regen_outro.'">'.__('Sonucu Yeniden Yaz', 'ai-blog-writer').'</a> '
            .'<a class="button button-link-delete" href="'.$delete.'" onclick="return confirm(\''.__('Silinsin mi?', 'ai-blog-writer').'\')">'.__('Sil', 'ai-blog-writer').'</a>'
            .'</td></tr>';
    }
    wp_reset_postdata();
    echo '</tbody></table></div>';
}

add_action('admin_post_ai_blog_approve', 'ai_blog_action_approve');
add_action('admin_post_ai_blog_delete', 'ai_blog_action_delete');
add_action('admin_post_ai_blog_regen_intro', 'ai_blog_action_regen_intro');
add_action('admin_post_ai_blog_regen_outro', 'ai_blog_action_regen_outro');

function ai_blog_verify_action() {
    if (!current_user_can('manage_options')) wp_die('forbidden');
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ai_blog_queue_actions')) wp_die('nonce');
    $post_id = (int) ($_GET['post_id'] ?? 0);
    if (!$post_id) wp_die('no post');
    return $post_id;
}

function ai_blog_action_approve() {
    $post_id = ai_blog_verify_action();
    wp_update_post(['ID'=>$post_id, 'post_status'=>'publish']);
    ai_blog_log('info', 'Post approved', ['post_id'=>$post_id]);
    wp_redirect(admin_url('admin.php?page=ai-blog-queue'));
    exit;
}
function ai_blog_action_delete() {
    $post_id = ai_blog_verify_action();
    wp_delete_post($post_id, true);
    ai_blog_log('info', 'Post deleted', ['post_id'=>$post_id]);
    wp_redirect(admin_url('admin.php?page=ai-blog-queue'));
    exit;
}

function ai_blog_regenerate_section($post_id, $section) {
    $apiKey = get_option('ai_blog_openai_key');
    $model  = get_option('ai_blog_model', 'gpt-4o');
    $content = get_post_field('post_content', $post_id);
    $title = get_the_title($post_id);
    if (!$apiKey || !$content) return;

    $plain = wp_strip_all_tags($content);
    $paras = preg_split('/\n\s*\n/', $plain);
    if (!$paras || count($paras) == 0) return;

    $target_idx = ($section === 'intro') ? 0 : (count($paras)-1);
    $original = trim($paras[$target_idx]);

    $prompt = "BAŞLIK: {$title}
BÖLÜM: {$section}
Aşağıdaki paragrafı Türkçe, SEO uyumlu, daha akıcı ve özgün biçimde yeniden yaz. Ton: bilgi verici, samimi. Tek paragraf döndür.

Orijinal Paragraf:
{$original}";

    $chatData = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Kısa ve net Türkçe blog paragrafları yazan bir editörsün."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.6,
        "max_tokens" => 220
    ];
    $resp = ai_blog_openai_api_call('chat/completions', $apiKey, $chatData);
    if (!$resp) return;
    $json = json_decode($resp, true);
    $newp = trim($json['choices'][0]['message']['content'] ?? '');
    if (!$newp) return;

    $paras[$target_idx] = $newp;
    $new_content = implode("\n\n", array_map('wpautop', $paras));
    wp_update_post(['ID'=>$post_id, 'post_content'=>$new_content]);
}

function ai_blog_action_regen_intro() {
    $post_id = ai_blog_verify_action();
    ai_blog_regenerate_section($post_id, 'intro');
    ai_blog_log('info', 'Intro regenerated', ['post_id'=>$post_id]);
    wp_redirect(admin_url('admin.php?page=ai-blog-queue'));
    exit;
}
function ai_blog_action_regen_outro() {
    $post_id = ai_blog_verify_action();
    ai_blog_regenerate_section($post_id, 'outro');
    ai_blog_log('info', 'Outro regenerated', ['post_id'=>$post_id]);
    wp_redirect(admin_url('admin.php?page=ai-blog-queue'));
    exit;
}


// İç link önerileri meta kutusu (sadece öneri, otomatik ekleme yok)
add_action('add_meta_boxes', function() {
    add_meta_box('ai_blog_link_suggestions', __('AI İç Link Önerileri', 'ai-blog-writer'), 'ai_blog_link_suggestions_metabox', 'post', 'side', 'default');
});
function ai_blog_link_suggestions_metabox($post) {
    $tags = wp_get_post_tags($post->ID, ['fields'=>'names']);
    $cat_ids = wp_get_post_categories($post->ID);
    $kw = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
    $kw_list = array_filter(array_map('trim', explode(',', (string)$kw)));
    $args = [
        'post_type' => 'post',
        'post__not_in' => [$post->ID],
        'posts_per_page' => 8,
        'ignore_sticky_posts' => true,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    if (!empty($cat_ids)) $args['category__in'] = $cat_ids;
    $q = new WP_Query($args);
    $cands = [];
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $score = 0;
            $title = get_the_title();
            $link = get_permalink();
            $t2 = wp_get_post_tags(get_the_ID(), ['fields'=>'names']);
            if ($tags && $t2) $score += count(array_intersect($tags, $t2)) * 2;
            foreach ($kw_list as $k) { if ($k && stripos($title, $k) !== false) $score += 1; }
            $cands[] = ['title'=>$title, 'link'=>$link, 'score'=>$score];
        }
        wp_reset_postdata();
    }
    usort($cands, function($a,$b){ return $b['score'] <=> $a['score']; });
    $cands = array_slice($cands, 0, 8);
    if (empty($cands)) { echo '<p>' . __('Öneri bulunamadı.', 'ai-blog-writer') . '</p>'; return; }
    echo '<ol style="margin-left:1em">';
    foreach ($cands as $c) echo '<li style="margin-bottom:6px"><a href="'.esc_url($c['link']).'" target="_blank">'.esc_html($c['title']).'</a></li>';
    echo '</ol><p style="color:#666;font-size:12px">' . __('Not: Öneriler Auto Internal Links ile çakışmaz; ekleme size bırakılır.', 'ai-blog-writer') . '</p>';
}
