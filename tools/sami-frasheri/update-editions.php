<?php
/** Update only the existing Sami book page after verified edition assets and template deploy. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require '/var/www/html/wp-load.php';
if (home_url() !== 'https://www.valonasani.com') { throw new Exception('Wrong website'); }
$id = 5450;
$post = get_post($id);
if (!$post || $post->post_name !== 'sami-frasheri-shqiperia' || $post->post_status !== 'publish' || get_page_template_slug($id) !== 'page-sami-frasheri.php') { throw new Exception('Unexpected page'); }
wp_set_current_user((int) get_option('vp_editorial_owner'));
if (!current_user_can('edit_post', $id)) { throw new Exception('Page edit capability required'); }
$manifest = json_decode(file_get_contents(__DIR__ . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$count = count($manifest);
if (!in_array($count, [9, 10], true)) { throw new Exception('Unexpected edition count'); }
$template = file_get_contents(get_template_directory() . '/page-sami-frasheri.php');
if (substr_count($template, '<article class="sf-card"') !== $count) { throw new Exception('Template count mismatch'); }
foreach ($manifest as $file) {
    $path = wp_upload_dir()['basedir'] . '/sami-frasheri-2026/' . $file['filename'];
    if (!is_file($path) || hash_file('sha256', $path) !== $file['sha256'] || file_get_contents($path, false, null, 0, 5) !== '%PDF-') { throw new Exception('Invalid PDF: ' . $file['filename']); }
}
$word = $count === 9 ? 'shtatë' : 'tetë';
$description = 'Lexo veprën e Sami Frashërit: një botim shqip, një përshtatje në gegnisht dhe ' . $word . ' përkthime me AI. PDF falas, burime dhe shënime.';
$content = 'Shqipëria: Ç’ka qënë, ç’është e ç’do të bëhetë? Botim leximi shqip, përshtatje në gegnisht dhe ' . $word . ' përkthime të gjeneruara me AI (2026). Shkarkime PDF dhe burimet e veprës së Sami Frashërit.';
$result = wp_update_post(wp_slash(['ID' => $id, 'post_excerpt' => $description, 'post_content' => $content]), true);
if (is_wp_error($result)) { throw new Exception($result->get_error_message()); }
update_post_meta($id, '_yoast_wpseo_title', 'Sami Frashëri: Shqipëria — PDF në ' . $count . ' botime | Valon Asani');
foreach (['_yoast_wpseo_metadesc', '_yoast_wpseo_opengraph-description', '_yoast_wpseo_twitter-description'] as $key) { update_post_meta($id, $key, $description); }
clean_post_cache($id);
if (function_exists('YoastSEO')) { YoastSEO()->classes->get('Yoast\\WP\\SEO\\Builders\\Indexable_Builder')->build_for_id_and_type($id, 'post'); }
echo json_encode(['id' => $id, 'editions' => $count, 'url' => get_permalink($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
