<?php
/** Dossier page families. Shared source fragments remain the editorial source of truth. */
defined('ABSPATH') || exit;
function valon_dossier_routes() {
 return [
 'overview'=>['','', 'Overview','Përmbledhje',[]],
 'demands'=>['demands','kerkesat','Demands','Kërkesat',['demands']],
 'timeline'=>['timeline','kronologjia','Timeline','Kronologjia',['timeline']],
 'investigations'=>['investigations','hetime','Investigations','Hetime',[]],
 'coast'=>['investigations/coastal-development','hetime/zhvillimi-bregdetar','Coastal development','Zhvillimi bregdetar',['story','protected-areas-law','geography']],
 'accountability'=>['investigations/accountability','hetime/llogaridhenia','Accountability and crime','Llogaridhënia dhe krimi',['crime-entry-title','christopher-hyland','evidence-desk','protesters','network','claims','albania2030']],
 'media'=>['investigations/media','hetime/media','Media ownership and framing','Pronësia dhe paraqitja mediatike',['coverage-audit','media-investigation']],
 'history'=>['investigations/jewish-history-projects','hetime/historia-hebreje','Jewish history, statements and museums','Historia hebreje, deklaratat dhe muzetë',['jewish-history-projects']],
 'development'=>['investigations/development','hetime/zhvillimi','Development and context','Zhvillimi dhe konteksti',['development-2010','historical-context']],
 'sources'=>['sources','burimet','Sources & methods','Burimet dhe metodat',['research-update','submit-information']],
 'feed'=>['online-coverage','rrjedha','Online coverage','Nga terreni',[]]
 ];
}
function valon_dossier_key(){ $key=(string)get_post_meta(get_queried_object_id(),'_valon_dossier_key',true);return isset(valon_dossier_routes()[$key])?$key:'overview'; }
function valon_dossier_url($key='overview',$sq=null){$sq=$sq??valon_albania_is_sq();$r=valon_dossier_routes();$suffix=$r[$key][$sq?1:0]??'';return home_url(($sq?'/sq/shqiperia/':'/albania/').($suffix?$suffix.'/':''));}
function valon_dossier_label($key,$sq=null){$sq=$sq??valon_albania_is_sq();return valon_dossier_routes()[$key][$sq?3:2];}
function valon_dossier_language_switch(){ $html='<nav class="albania-language" aria-label="Language">';foreach([false=>'English',true=>'Shqip'] as $sq=>$label)$html.='<a href="'.esc_url(valon_dossier_url(valon_dossier_key(),(bool)$sq)).'" hreflang="'.($sq?'sq':'en').'" lang="'.($sq?'sq':'en').'"'.(valon_albania_is_sq()===(bool)$sq?' aria-current="page"':'').'>'.$label.'</a>';return $html.'</nav>'; }
function valon_dossier_parse($html){$d=new DOMDocument('1.0','UTF-8');$old=libxml_use_internal_errors(true);$d->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();libxml_use_internal_errors($old);return $d;}
/** Context photographs; originals and derivative licences stay visible beside each image. */
function valon_dossier_card_image($key) {
 $photos=[
 'coast'=>['Sazan Island viewed across the sea, 8 July 2016.','Sazan_Island,_Albania_2016_02.jpg','Pasztilla / Attila Terbócs','Pasztilla','by-sa/4.0','CC BY-SA 4.0'],
 'accountability'=>['A banner and crowd at the Tirana protest, 13 June 2026.','Flamingo_Revolution_Tirana_Protest_nr14_June_13_2026_07.jpg','Albinfo','Albinfo','by/4.0','CC BY 4.0'],
 'history'=>['Remains of the ancient synagogue in Sarandë, photographed on 24 September 2015.','Onchesmos_5th_c_synagogue_2015_02.jpg','Pasztilla / Attila Terbócs','Pasztilla','by-sa/4.0','CC BY-SA 4.0'],
 'media'=>['Camera crews covering the Tirana gathering, 13 June 2026.','Flamingo_Revolution_Tirana_Protest_nr14_June_13_2026_50.jpg','Albinfo','Albinfo','by/4.0','CC BY 4.0'],
 'development'=>['A university building at Mother Teresa Square, Tirana, 5 September 2015.','Tirana_University_and_Mother_Teresa_Square.jpg','Leeturtle','Leeturtle','by-sa/3.0','CC BY-SA 3.0']
 ];
 $p=$photos[$key];$base='ALBANIA_ASSETS/investigation-'.$key;
 return '<figure class="investigation-card-photo"><img src="'.$base.'-480.webp" srcset="'.$base.'-480.webp 480w, '.$base.'-960.webp 960w" sizes="(max-width:700px) calc(100vw - 48px), (max-width:1000px) 45vw, 350px" width="960" height="640" loading="lazy" decoding="async" alt="'.esc_attr($p[0]).'"><figcaption><span>'.esc_html($p[0]).'</span><details><summary>Photo credit and licence</summary><span>Photo:</span> <a href="https://commons.wikimedia.org/wiki/User:'.esc_attr($p[3]).'">'.esc_html($p[2]).'</a> / <a href="https://commons.wikimedia.org/wiki/File:'.esc_attr($p[1]).'">Wikimedia Commons</a> · <a href="https://creativecommons.org/licenses/'.$p[4].'/">'.$p[5].'</a>. <span>Resized, not cropped. Context photograph.</span></details></figcaption></figure>';
}
function valon_dossier_render($html){
 $key=valon_dossier_key();$sq=valon_albania_is_sq();$routes=valon_dossier_routes();$doc=valon_dossier_parse($html);$xp=new DOMXPath($doc);$catalog=iterator_to_array($xp->query('//a[starts-with(@href,"https://") or starts-with(@href,"http://")]'));$map=[];$parts=[];
 foreach($routes as $k=>$r)foreach($r[4] as $id){$node=$doc->getElementById($id);if(!$node)continue;if($id==='crime-entry-title')$node=$node->parentNode->parentNode;$map[$id]=$k;foreach($xp->query('.//*[@id]',$node) as $child)$map[$child->getAttribute('id')]=$k;$parts[$id]=$node;}
 // Nested demands has its own canonical destination, irrespective of source order.
 $demands=$doc->getElementById('demands');if($demands){$map['demands']='demands';foreach($xp->query('.//*[@id]',$demands) as $n)$map[$n->getAttribute('id')]='demands';$demands->parentNode->removeChild($demands);}
 $crime=$doc->getElementById('crime-entry-title');if($crime){$aside=$crime->parentNode->parentNode;$aside->parentNode->removeChild($aside);}
 $map['sources']='sources';$map['sourcefilter']='sources';$map['library']='sources';$map['submit-information']='sources';$map['crime-entry-title']='accountability';
 foreach($xp->query('//*[@id]') as $record){$id=$record->getAttribute('id');if(!preg_match('/^r[0-9]+$/',$id))continue;$map[$id]='sources';$map['evidence-'.strtoupper($id)]='accountability';$map['evidence-'.$id]='accountability';}
 foreach(['law21','kkt','nasufi','kumbaro','zhupa','orthodox_idea','rugova','jim_xhema','stephanopoulos','hyland','baba_mondi','hazis_vardar','rama','gov','parl','kushner','affinity','sazan','zvernec','alex','george','roth','erdogan','bektashi','aadf','museum','balluku','spak','pif','kurti','vucic','meloni','mitsotakis','kovacevski','trump','students','flamingo','opposition','qatar','police','media'] as $person)$map['person-'.$person]='accountability';
 $base=[];foreach($routes as $k=>$r)$base[$k]=valon_dossier_url($k);
 $new='<a class="skip-link" href="#dossier-main">Skip to content</a><header><a href="'.esc_url(home_url('/')).'">Valon Asani</a><span class="logo">DOSSIER</span><a class="demands-header" href="'.esc_url($base['demands']).'#demands">'.esc_html(valon_dossier_label('demands')).'</a><a class="submit-header" href="'.esc_url(valon_albania_submit_url()).'">Submit information ↗</a></header>'.valon_dossier_language_switch();
 $new.='<nav class="dossier-navigation" aria-label="Dossier navigation">';foreach(['overview','demands','timeline','investigations','sources','feed'] as $k)$new.='<a href="'.esc_url($base[$k]).'"'.(($key===$k||($k==='investigations'&&in_array($key,['coast','accountability','media','history','development'],true)))?' aria-current="page"':'').'>'.esc_html($k==='sources'?($sq?'Burimet':'Sources'):valon_dossier_label($k)).'</a>';$new.='</nav><main class="wrap dossier-main" id="dossier-main" tabindex="-1">';
 if($key!=='overview'){$new.='<nav class="dossier-breadcrumb" aria-label="Breadcrumb"><a href="'.esc_url($base['overview']).'">'.esc_html(valon_dossier_label('overview')).'</a> / ';if(in_array($key,['coast','accountability','media','history','development'],true))$new.='<a href="'.esc_url($base['investigations']).'">'.esc_html(valon_dossier_label('investigations')).'</a> / ';$new.='<span>'.esc_html(valon_dossier_label($key)).'</span></nav><h1>'.esc_html(valon_dossier_label($key)).'</h1>';}
 if($key==='overview')$new.=file_get_contents(__DIR__.'/../template-parts/albania-overview.html');
 if($key==='overview'||$key==='investigations'){$new.='<section class="dossier-topics"><h2>Explore the investigations</h2><div class="dossier-card-grid">';foreach(['coast','accountability','history','media','development'] as $k)$new.='<article>'.valon_dossier_card_image($k).'<h3><a href="'.esc_url($base[$k]).'">'.esc_html(valon_dossier_label($k)).'</a></h3><p>'.esc_html(['coast'=>'The yacht-to-streets story, Sazan, Zvërnec and the location map.','accountability'=>'Protest organisation, public accountability, allegations and documented relationships.','history'=>'Keep the statement timeline, historical evidence and two museums together.','media'=>'Compare sampled coverage and inspect media ownership separately.','development'=>'Read economic, education and population measures with their limits.'][$k]).'</p></article>';$new.='</div></section>';}
 if($key==='timeline')$new.=file_get_contents(__DIR__.'/../template-parts/albania-timeline-photo.html');
 if($key!=='sources')foreach($routes[$key][4] as $id){if(isset($parts[$id]))$new.=$doc->saveHTML($parts[$id]);}
 if($key==='accountability'){$new.='<noscript><section><h2>Text alternatives for graph links</h2><ul>';foreach($map as $id=>$k){if($doc->getElementById($id))continue;if(preg_match('/^evidence-[rR][0-9]+$/',$id)){$record=strtolower(substr($id,9));$new.='<li id="'.esc_attr($id).'"><a href="'.esc_url($base['sources'].'#'.$record).'">'.esc_html(strtoupper($record)).' — <span>Full evidence record</span></a></li>';}elseif(str_starts_with($id,'person-'))$new.='<li id="'.esc_attr($id).'"><a href="#network">'.esc_html(substr($id,7)).' — <span>Profiles and relationships</span></a></li>';}$new.='</ul></section></noscript>';}
 if($key==='feed')$new.=file_get_contents(__DIR__.'/../template-parts/albania-feed.html');
 if($key==='sources'){
  $new.='<section id="sources" class="source-catalog"><h2>Original documents and reporting</h2><p>Search the linked documents across every investigation. A source link establishes provenance, not that every claim in the source is verified. Read the associated evidence and limitations.</p><label for="sourcefilter">Search documents, publishers and context</label><input id="sourcefilter" type="search"><noscript><p>Use your browser’s Find command to search this page without JavaScript. Open the document list below to browse original sources.</p></noscript><p id="document-count" role="status"></p><p id="document-empty" hidden>No matching documents. Try a shorter term or a publisher name.</p><details id="document-results"><summary>Browse original documents</summary><div id="library">';$seen=[];
  foreach($catalog as $a){$url=$a->getAttribute('href');$host=parse_url($url,PHP_URL_HOST);if(!$host||str_contains($host,'valonasani.com'))continue;$context=$a->parentNode;while($context->parentNode&&!in_array($context->nodeName,['article','section','li'],true))$context=$context->parentNode;$heading=$xp->query('.//h2|.//h3|.//h4',$context)->item(0);$title=trim($a->textContent);$label=($heading?trim($heading->textContent).' — ':'').($title?:$host);if(isset($seen[$url]))continue;$seen[$url]=true;$new.='<article class="document-item"><h3><a href="'.esc_url($url).'">'.($heading?'<span>'.esc_html(trim($heading->textContent)).'</span> — ':'').'<span>'.esc_html($title?:$host).'</span></a></h3><p>'.esc_html($host).'</p>';$contextId=$context instanceof DOMElement?$context->getAttribute('id'):'';if($contextId&&isset($map[$contextId]))$new.='<a href="'.esc_url($base[$map[$contextId]].'#'.$contextId).'">Read the related evidence and context</a>';$new.='</article>';}
  $new.='</div></details></section>';foreach($routes[$key][4] as $id)if(isset($parts[$id]))$new.=$doc->saveHTML($parts[$id]);$new.='<section><h2>Methods and corrections</h2><p>Evidence records distinguish documents, reporting, attributed allegations and unresolved questions. Dates describe the cited record; they are not a promise of continuous verification. Corrections should update the canonical record and its linked summaries together.</p></section>';
 }
 if($key==='overview'){$new.='<section class="legacy-index"><h2>Following an older link?</h2><p>Older section and record links still work. Choose the destination below if automatic navigation is unavailable.</p><div class="dossier-card-grid">';foreach(['demands','timeline','sources','history','coast','accountability','media','development'] as $k)$new.='<a href="'.esc_url($base[$k]).'">'.esc_html(valon_dossier_label($k)).'</a>';$new.='</div><noscript><details><summary>Legacy section and record links</summary><ul>';foreach($map as $id=>$k)if($k!=='overview')$new.='<li id="'.esc_attr($id).'"><a href="'.esc_url($base[$k].'#'.$id).'">'.esc_html($id.' — '.valon_dossier_label($k)).'</a></li>';$new.='</ul></details></noscript></section>';}
 $new.='</main><footer class="wrap dossier-footer"><a href="'.esc_url($base['overview']).'">Back to dossier overview</a> · <a href="'.esc_url($base['demands']).'#demands">'.esc_html(valon_dossier_label('demands')).'</a> · <a href="'.esc_url($base['sources']).'">'.esc_html(valon_dossier_label('sources')).'</a><p>Independent editorial research. Source links and evidence labels remain part of each claim.</p></footer>';
 $render=valon_dossier_parse($new);$rx=new DOMXPath($render);
 foreach($rx->query('//a[starts-with(@href,"#")]') as $a){$id=substr($a->getAttribute('href'),1);if(isset($map[$id])&&$map[$id]!==$key)$a->setAttribute('href',$base[$map[$id]].'#'.$id);}
 // Child stories are subordinate to the page title, with no duplicate H1.
 if($key!=='overview')foreach(iterator_to_array($rx->query('//section//h1')) as $old){$h=$render->createElement('h2');while($old->firstChild)$h->appendChild($old->firstChild);$old->parentNode->replaceChild($h,$old);}
 $body=$render->getElementsByTagName('body')->item(0);$out='';foreach($body->childNodes as $n)$out.=$render->saveHTML($n);
 $out=str_replace('ALBANIA_SUBMIT_URL',esc_url(valon_albania_submit_url()),$out);
 $out=str_replace('ALBANIA_ASSETS',esc_url(get_theme_file_uri('/assets/albania')),$out);
 foreach($routes as $k=>$r)$out=str_replace('DOSSIER_'.strtoupper($k).'_URL',esc_url($base[$k]),$out);
 $out.='<script type="application/json" id="dossier-route-map">'.wp_json_encode(['current'=>$key,'routes'=>$base,'anchors'=>$map],JSON_HEX_TAG|JSON_HEX_AMP).'</script>';
 return $out;
}

// Let the existing SEO provider emit one canonical and a contextual page title.
add_filter('wpseo_canonical',function($url){return is_page_template('page-albania-protests-investigation.php')&&get_option('valon_dossier_multipage_ready')?valon_dossier_url(valon_dossier_key()):$url;},30);
function valon_dossier_seo_title($title){if(!is_page_template('page-albania-protests-investigation.php')||!get_option('valon_dossier_multipage_ready'))return $title;return (valon_dossier_key()==='overview'?(valon_albania_is_sq()?'Protestat në Shqipëri: kërkesat, ngjarjet dhe provat':'Albania’s protests: demands, events and evidence'):valon_dossier_label(valon_dossier_key())).' | Valon Asani';}
add_filter('wpseo_title','valon_dossier_seo_title',30);
add_filter('pre_get_document_title','valon_dossier_seo_title',30);
