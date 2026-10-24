<?php
/** Run through the existing authenticated maintenance workflow after installing the reviewed theme.
 * Idempotent: creates only missing dossier children; never replaces existing page content.
 */
if(!defined('ABSPATH')||!function_exists('valon_dossier_routes')||!function_exists('pll_set_post_language'))throw new RuntimeException('WordPress, reviewed theme and Polylang required.');
$parents=['en'=>5557,'sq'=>(int)get_option('valon_albania_sq_page')];
if(!$parents['sq'])throw new RuntimeException('Albanian dossier parent missing.');
$result=[];$pairs=[];
foreach(['en','sq'] as $lang){
 foreach(valon_dossier_routes() as $key=>$route){
  if($key==='overview')continue;
  $suffix=$route[$lang==='sq'?1:0];$segments=explode('/',$suffix);$slug=end($segments);
  $parent=count($segments)>1?($pairs['investigations'][$lang]??0):$parents[$lang];
  if(!$parent)throw new RuntimeException('Investigation parent missing.');
  $matches=get_posts(['post_type'=>'page','post_status'=>'any','post_parent'=>$parent,'name'=>$slug,'numberposts'=>2,'suppress_filters'=>true]);
  if(count($matches)>1)throw new RuntimeException('Ambiguous route '.$suffix);
  if($matches){$id=$matches[0]->ID;if(get_post_meta($id,'_valon_dossier_key',true)!==$key)throw new RuntimeException('Existing unrelated route '.$suffix);}
  else{$id=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>$route[$lang==='sq'?3:2],'post_name'=>$slug,'post_parent'=>$parent,'post_content'=>'','meta_input'=>['_wp_page_template'=>'page-albania-protests-investigation.php','_valon_dossier_key'=>$key]],true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());pll_set_post_language($id,$lang);}
  $pairs[$key][$lang]=$id;$result[]=['key'=>$key,'language'=>$lang,'id'=>$id,'url'=>get_permalink($id)];
 }
}
foreach($pairs as $pair){pll_save_post_translations($pair);foreach($pair as $id){if(get_post_status($id)!=='publish')wp_update_post(['ID'=>$id,'post_status'=>'publish']);}}
update_option('valon_dossier_multipage_ready',1,false);
flush_rewrite_rules(false);
echo wp_json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
