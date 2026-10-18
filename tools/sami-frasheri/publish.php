<?php
// Run with the existing authenticated production CLI after installing this template and verified assets.
require '/var/www/html/wp-load.php';
if (home_url() !== 'https://www.valonasani.com') { throw new Exception('Wrong website'); }
$slug = 'sami-frasheri-shqiperia';
if (get_page_by_path($slug)) { throw new Exception('Slug already exists; inspect before retry'); }
$owner = (int) get_option('vp_editorial_owner');
wp_set_current_user($owner);
if (!current_user_can('publish_pages')) { throw new Exception('Publication capability required'); }
$manifest = json_decode(file_get_contents(__DIR__ . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$uploads = wp_upload_dir();
foreach ($manifest as $file) {
    $path = $uploads['basedir'] . '/sami-frasheri-2026/' . $file['filename'];
    if (!is_file($path) || hash_file('sha256', $path) !== $file['sha256'] || file_get_contents($path, false, null, 0, 5) !== '%PDF-') { throw new Exception('Invalid PDF: ' . $file['filename']); }
}
if (!is_file(get_template_directory() . '/page-sami-frasheri.php')) { throw new Exception('Missing template'); }
$title = 'Sami Frashëri: Shqipëria — shkarko librin';
$description = 'Lexo veprën e Sami Frashërit: një botim shqip, një përshtatje në gegnisht dhe gjashtë përkthime me AI. PDF falas, burime dhe shënime.';
$id = wp_insert_post(wp_slash(['post_type'=>'page', 'post_status'=>'draft', 'post_author'=>$owner, 'post_name'=>$slug, 'post_title'=>$title, 'post_excerpt'=>$description, 'post_content'=>'Shqipëria: Ç’ka qënë, ç’është e ç’do të bëhetë? Botim leximi shqip, përshtatje në gegnisht dhe gjashtë përkthime të gjeneruara me AI (2026). Shkarkime PDF dhe burimet e veprës së Sami Frashërit.', 'comment_status'=>'closed', 'ping_status'=>'closed']), true);
if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
if (function_exists('pll_set_post_language')) { pll_set_post_language($id, 'sq'); }
$meta = ['_wp_page_template'=>'page-sami-frasheri.php', '_yoast_wpseo_title'=>'Sami Frashëri: Shqipëria — PDF në 8 botime | Valon Asani', '_yoast_wpseo_metadesc'=>$description, '_yoast_wpseo_opengraph-title'=>$title, '_yoast_wpseo_opengraph-description'=>$description, '_yoast_wpseo_opengraph-image'=>get_template_directory_uri().'/assets/sami-frasheri/sami-frasheri-1896.jpg', '_yoast_wpseo_twitter-title'=>$title, '_yoast_wpseo_twitter-description'=>$description, '_yoast_wpseo_twitter-image'=>get_template_directory_uri().'/assets/sami-frasheri/sami-frasheri-1896.jpg', '_vp_sami_publication'=>'2026-10-18'];
foreach ($meta as $key=>$value) { update_post_meta($id, $key, $value); }
$r=wp_update_post(['ID'=>$id, 'post_status'=>'publish'], true);
if (is_wp_error($r) || get_post_status($id)!=='publish') { throw new Exception('Publication failed: inspect draft '.$id); }
echo json_encode(['id'=>$id,'url'=>get_permalink($id),'status'=>get_post_status($id)], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
