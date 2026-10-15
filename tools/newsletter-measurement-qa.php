<?php
/** Run with WordPress loaded. Uses temporary SQL tables, no emails or external requests. */
$original_prefix=$wpdb->prefix;
$wpdb->prefix='vp_qa_'.bin2hex(random_bytes(5)).'_';
$tables=[$wpdb->prefix.'vp_newsletter_events',$wpdb->prefix.'vp_newsletter_intents'];
$wpdb->query("CREATE TEMPORARY TABLE {$tables[0]} (event_hash char(64) PRIMARY KEY,member_hash char(64),event_type varchar(20),occurred_at datetime,language varchar(8),source varchar(20),placement varchar(20),is_test tinyint)");
$wpdb->query("CREATE TEMPORARY TABLE {$tables[1]} (token_hash char(64) PRIMARY KEY,member_hash char(64),created_at datetime,language varchar(8),source varchar(20),placement varchar(20),confirmed tinyint DEFAULT 0,reported tinyint DEFAULT 0)");
$config=['token'=>str_repeat('a',64),'signing_secret'=>'fixture-signing-secret','test_hashes'=>[vp_newsletter_member_hash('owner@example.invalid')]];
$filter=fn()=>$config; add_filter('pre_option_vp_newsletter_webhook_config',$filter);
$assertions=0;
$check=function($condition,$name)use(&$assertions){if(!$condition)throw new RuntimeException($name);$assertions++;};
$make=function($email='reader@example.invalid',$type='subscribe',$interest='Letters from Valon',$list='293f2ca94d')use($config){
    $body=http_build_query(['type'=>$type,'fired_at'=>gmdate('Y-m-d H:i:s'),'data'=>['list_id'=>$list,'email'=>$email,'merges'=>['INTERESTS'=>$interest,'VLANG'=>'de']]]);
    $r=new WP_REST_Request('POST','/');$r->set_url_params(['token'=>$config['token']]);$r->set_header('content-type','application/x-www-form-urlencoded');$r->set_body($body);parse_str($body,$params);$r->set_body_params($params);
    $r->set_header('x-mailchimp-signature','t='.time().',v1='.hash_hmac('sha256',time().'.'.$body,$config['signing_secret']));return $r;
};
try {
    $r=$make();$check(vp_newsletter_signature_valid($config['signing_secret'],$r->get_header('x-mailchimp-signature'),$r->get_body(),time()),'valid signature');
    $check(!vp_newsletter_signature_valid($config['signing_secret'],$r->get_header('x-mailchimp-signature'),$r->get_body().'x',time()),'tampered body');
    $check(!vp_newsletter_signature_valid($config['signing_secret'],$r->get_header('x-mailchimp-signature'),$r->get_body(),time()+301),'expired signature');
    $bad=$make();$bad->set_header('x-mailchimp-signature','');$check(is_wp_error(vp_newsletter_webhook($bad)),'unsigned fails');
    $bad=$make();$bad->set_url_params(['token'=>str_repeat('b',64)]);$check(is_wp_error(vp_newsletter_webhook($bad)),'wrong token fails');
    $check(is_wp_error(vp_newsletter_webhook($make('reader@example.invalid','subscribe','Letters from Valon','wrong'))),'wrong audience fails');
    vp_newsletter_webhook($make('other@example.invalid','subscribe','BE THE ONE updates'));
    $check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables[0]}")===0,'other brand ignored');
    $token=str_repeat('c',64);
    $wpdb->insert($tables[1],['token_hash'=>hash('sha256',$token),'member_hash'=>vp_newsletter_member_hash('reader@example.invalid'),'created_at'=>gmdate('Y-m-d H:i:s',time()-60),'language'=>'sq','source'=>'facebook','placement'=>'hero']);
    $result=vp_newsletter_webhook($r);$check(!is_wp_error($result),'valid confirmation accepted');
    vp_newsletter_webhook($r);$check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables[0]}")===1,'retry deduplicated');
    $row=$wpdb->get_row("SELECT * FROM {$tables[0]}",ARRAY_A);$check($row['source']==='facebook' && $row['language']==='sq','consented attribution retained');
    $check(!str_contains(json_encode($row),'reader@'),'no raw email stored');
    $status=new WP_REST_Request('POST','/');$status->set_header('origin',home_url());$status->set_param('token',$token);
    $check(vp_newsletter_confirmation_status($status)->get_data()['confirmed']===true,'signed event claim');
    $check(vp_newsletter_confirmation_status($status)->get_data()['confirmed']===false,'claim only once');
    $status->set_header('origin','https://example.invalid');$check(is_wp_error(vp_newsletter_confirmation_status($status)),'cross origin fails');
    vp_newsletter_webhook($make('owner@example.invalid'));$check((int)$wpdb->get_var("SELECT is_test FROM {$tables[0]} WHERE member_hash='".vp_newsletter_member_hash('owner@example.invalid')."'")===1,'test excluded');
    vp_newsletter_webhook($make('unknown@example.invalid'));$check($wpdb->get_var("SELECT source FROM {$tables[0]} WHERE member_hash='".vp_newsletter_member_hash('unknown@example.invalid')."'")==='unknown','unattributed not assumed website');
    vp_newsletter_webhook($make('reader@example.invalid','unsubscribe',''));
    $check((int)$wpdb->get_var("SELECT confirmed FROM {$tables[1]} LIMIT 1")===0,'unsubscribe clears claim');
    echo "$assertions newsletter measurement assertions passed; temporary SQL only, no emails.\n";
} finally {
    foreach($tables as $table)$wpdb->query("DROP TEMPORARY TABLE IF EXISTS $table");
    $wpdb->prefix=$original_prefix;remove_filter('pre_option_vp_newsletter_webhook_config',$filter);
}
