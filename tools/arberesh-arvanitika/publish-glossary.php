<?php
// One-time companion page creation. Run only with the existing production CLI.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require '/var/www/html/wp-load.php';
if (home_url() !== 'https://www.valonasani.com') { throw new Exception('Wrong website'); }
$slug = 'arberesh-arvanitika-fjalorth';
if (get_page_by_path($slug)) { throw new Exception('Slug exists: inspect before retry'); }
if (!function_exists('pll_set_post_language')) { throw new Exception('Language plugin unavailable'); }
$owner = (int) get_option('vp_editorial_owner');
wp_set_current_user($owner);
if (!current_user_can('publish_pages')) { throw new Exception('Publication capability required'); }
if (!is_file(get_template_directory().'/page-arberesh-arvanitika-fjalorth.php')) { throw new Exception('Template missing'); }
$title = 'Fjalorth arbëresh dhe arvanitas → shqip';
$description = 'Fjalorth historik me 285 dëshmi arbëreshe e arvanitase, shpjegime në shqip, faqe burimi dhe 22 lexime të pasigurta të paraqitura veçmas.';
$id=wp_insert_post(wp_slash(['post_type'=>'page','post_status'=>'draft','post_author'=>$owner,'post_name'=>$slug,'post_title'=>$title,'post_excerpt'=>$description,'post_content'=>$description,'comment_status'=>'closed','ping_status'=>'closed']),true);
if(is_wp_error($id)) { throw new Exception($id->get_error_message()); }
pll_set_post_language($id,'sq');
$meta=['_wp_page_template'=>'page-arberesh-arvanitika-fjalorth.php','_yoast_wpseo_title'=>$title.' | Valon Asani','_yoast_wpseo_metadesc'=>$description,'_yoast_wpseo_canonical'=>'https://www.valonasani.com/sq/'.$slug.'/','_yoast_wpseo_opengraph-title'=>$title,'_yoast_wpseo_opengraph-description'=>$description,'_yoast_wpseo_twitter-title'=>$title,'_yoast_wpseo_twitter-description'=>$description,'_vp_glossary_publication'=>'2026-10-18'];
foreach($meta as $key=>$value) { update_post_meta($id,$key,$value); }
$r=wp_update_post(['ID'=>$id,'post_status'=>'publish','page_template'=>'page-arberesh-arvanitika-fjalorth.php'],true);
if(is_wp_error($r)||get_post_status($id)!=='publish') { throw new Exception('Publication failed: inspect draft '.$id); }
echo json_encode(['id'=>$id,'url'=>get_permalink($id),'status'=>get_post_status($id)],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
