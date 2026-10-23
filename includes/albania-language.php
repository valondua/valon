<?php
defined('ABSPATH') || exit;
function valon_albania_is_sq() { return function_exists('pll_get_post_language') && pll_get_post_language(get_queried_object_id())==='sq'; }
function valon_albania_url($sq=null) { $sq=$sq ?? valon_albania_is_sq();$id=$sq?(int)get_option('valon_albania_sq_page',0):5557;return $id?get_permalink($id):home_url($sq?'/sq/shqiperia/':'/albania/'); }
function valon_albania_submit_url($sq=null) {$sq=$sq ?? valon_albania_is_sq();$id=(int)get_option('valon_albania_submit_'.($sq?'sq':'en'),0);return $id?get_permalink($id):home_url($sq?'/sq/shqiperia/dergo/':'/albania/submit/');}
function valon_albania_language_switch() {
    $submission=is_page_template('page-albania-submit.php');$sq=valon_albania_is_sq();$html='<nav class="albania-language" aria-label="Language">';
    foreach([false=>'English',true=>'Shqip'] as $language=>$label){$url=$submission?valon_albania_submit_url((bool)$language):valon_albania_url((bool)$language);$html.='<a lang="'.($language?'sq':'en').'" hreflang="'.($language?'sq':'en').'" href="'.esc_url($url).'"'.($sq===(bool)$language?' aria-current="page"':'').'>'.$label.'</a>';}
    return $html.'</nav>';
}
function valon_albania_dictionary(){static $d=null;if($d===null){$path=__DIR__.'/../assets/albania/sq.json';$d=is_file($path)?json_decode(file_get_contents($path),true):[];}return is_array($d)?$d:[];}
function valon_albania_localize_html($html){
    if(!valon_albania_is_sq())return $html;
    $dictionary=valon_albania_dictionary();$dom=new DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8"><div id="albania-localized-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);$xp=new DOMXPath($dom);
    foreach($xp->query('//option[not(@value)]') as $option)$option->setAttribute('value',$option->textContent);
    foreach($xp->query('//text()[not(ancestor::script) and not(ancestor::style)]') as $node){$raw=$node->nodeValue;$key=preg_replace('/\s+/u',' ',trim($raw));if(isset($dictionary[$key]))$node->nodeValue=(preg_match('/^\s/u',$raw)?' ':'').$dictionary[$key].(preg_match('/\s$/u',$raw)?' ':'');}
    foreach($xp->query('//*[@alt or @aria-label or @placeholder or @title]') as $node)foreach(['alt','aria-label','placeholder','title'] as $attr){$key=$node->getAttribute($attr);if(isset($dictionary[$key]))$node->setAttribute($attr,$dictionary[$key]);}
    $root=$dom->getElementById('albania-localized-root');if(!$root)return $html;$out='';foreach($root->childNodes as $node)$out.=$dom->saveHTML($node);return $out;
}
add_action('wp_enqueue_scripts',function(){
    if(!valon_albania_is_sq()||(!is_page_template('page-albania-protests-investigation.php')&&!is_page_template('page-albania-submit.php')))return;
    $base=get_theme_file_uri('/assets/albania/');wp_enqueue_script('albania-locale-data',$base.'sq.js',[],filemtime(__DIR__.'/../assets/albania/sq.js'),true);wp_enqueue_script('albania-locale',$base.'locale.js',is_page_template('page-albania-protests-investigation.php')?['albania-network','albania-locale-data']:['albania-locale-data'],filemtime(__DIR__.'/../assets/albania/locale.js'),true);
    // Locale is ready before any data-driven widget renders.
    foreach(['albania-navigation-map','albania-profiles','albania-story','albania-research','albania-investigation','albania-submissions'] as $handle)if(isset(wp_scripts()->registered[$handle]))wp_scripts()->registered[$handle]->deps[]='albania-locale';
},1001);
