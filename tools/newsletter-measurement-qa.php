<?php
/** Run with WordPress loaded. Uses temporary SQL tables, no emails or external requests. */
global $wpdb;
$original_prefix=$wpdb->prefix;
$wpdb->prefix='vp_qa_'.bin2hex(random_bytes(5)).'_';
$tables=[$wpdb->prefix.'vp_newsletter_events',$wpdb->prefix.'vp_newsletter_intents',$wpdb->prefix.'vp_newsletter_ga4',$wpdb->prefix.'vp_newsletter_impressions'];
$wpdb->query("CREATE TEMPORARY TABLE {$tables[0]} (event_hash char(64) PRIMARY KEY,member_hash char(64),event_type varchar(20),occurred_at datetime,language varchar(8),source varchar(20),placement varchar(20),is_test tinyint)");
$wpdb->query("CREATE TEMPORARY TABLE {$tables[1]} (token_hash char(64) PRIMARY KEY,member_hash char(64),created_at datetime,language varchar(8),source varchar(20),placement varchar(20),confirmed tinyint DEFAULT 0,reported tinyint DEFAULT 0,impression_id char(64) DEFAULT '',client_id varchar(80) DEFAULT '',session_id varchar(20) DEFAULT '',analytics_consent varchar(10) DEFAULT '')");
$wpdb->query("CREATE TEMPORARY TABLE {$tables[2]} (event_hash char(64) PRIMARY KEY,token_hash char(64),occurred_at datetime,status varchar(20) DEFAULT 'pending',http_status int DEFAULT 0,updated_at datetime)");
$wpdb->query("CREATE TEMPORARY TABLE {$tables[3]} (id char(64) PRIMARY KEY,visitor_hash char(64),placement varchar(20),occurred_at datetime)");
$ga_config=['enabled'=>true,'api_secret'=>'fixture-not-real'];
$ga_filter=fn()=>$ga_config;add_filter('pre_option_vp_newsletter_ga4_config',$ga_filter);
$sent=[];$response_code=204;
$http=function($pre,$args,$url)use(&$sent,&$response_code){
    if(str_starts_with($url,'https://region1.google-analytics.com/mp/collect?')) {
        $sent[]=json_decode($args['body'],true);
        return $response_code ? ['response'=>['code'=>$response_code],'body'=>'','headers'=>[]] : new WP_Error('timeout','fixture');
    }
    throw new RuntimeException('Unexpected external request');
};add_filter('pre_http_request',$http,10,3);
$config=['token' =>str_repeat('a',64),'signing_secret'=>'fixture-signing-secret','test_hashes'=>[vp_newsletter_member_hash('owner@example.invalid')]];
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
    $wpdb->insert($tables[1],['token_hash'=>hash('sha256',$token),'member_hash'=>vp_newsletter_member_hash('reader@example.invalid'),'created_at'=>gmdate('Y-m-d H:i:s',time()-60),'language'=>'sq','source'=>'facebook','placement'=>'hero','client_id'=>'12345.67890','session_id'=>(string)time(),'analytics_consent'=>'granted']);
    $result=vp_newsletter_webhook($r);$check(!is_wp_error($result),'valid confirmation accepted');
    vp_newsletter_webhook($r);$check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables[0]}")===1,'retry deduplicated');
    $row=$wpdb->get_row("SELECT * FROM {$tables[0]}",ARRAY_A);$check($row['source']==='facebook' && $row['language']==='sq','consented attribution retained');
    $check(!str_contains(json_encode($row),'reader@'),'no raw email stored');
    $status=new WP_REST_Request('POST','/');$status->set_header('origin',home_url());$status->set_param('token',$token);
    $check(vp_newsletter_confirmation_status($status)->get_data()['confirmed']===false,'cached browser cannot claim server conversion');
    $check(vp_newsletter_confirmation_status($status)->get_data()['confirmed']===false,'claim only once');
    $status->set_header('origin','https://example.invalid');$check(is_wp_error(vp_newsletter_confirmation_status($status)),'cross origin fails');
    vp_newsletter_webhook($make('owner@example.invalid'));$check((int)$wpdb->get_var("SELECT is_test FROM {$tables[0]} WHERE member_hash='".vp_newsletter_member_hash('owner@example.invalid')."'")===1,'test excluded');
    vp_newsletter_webhook($make('unknown@example.invalid'));$check($wpdb->get_var("SELECT source FROM {$tables[0]} WHERE member_hash='".vp_newsletter_member_hash('unknown@example.invalid')."'")==='unknown','unattributed not assumed website');
    vp_newsletter_webhook($make('reader@example.invalid','unsubscribe',''));
    $check((int)$wpdb->get_var("SELECT confirmed FROM {$tables[1]} LIMIT 1")===0,'unsubscribe clears claim');
    $check(count($sent)===1,'one server delivery without browser return');
    $check($sent[0]['events'][0]['name']==='newsletter_confirmed','correct event name');
    $check($sent[0]['client_id']==='12345.67890','original GA client used');
    $check(!str_contains(json_encode($sent),'@') && !str_contains(json_encode($sent),vp_newsletter_member_hash('reader@example.invalid')),'no email or member hash sent');
    $check($sent[0]['consent']['ad_user_data']==='DENIED','ads consent denied');
    $check($wpdb->get_var("SELECT status FROM {$tables[2]} LIMIT 1")==='accepted','transport acceptance recorded');
    vp_newsletter_ga_deliver($row['event_hash']);$check(count($sent)===1,'duplicate delivery prevented');
    // Queue-only fixture: revoke before dispatch and verify zero sends.
    $token2=str_repeat('d',64);$member2=vp_newsletter_member_hash('revoke@example.invalid');
    $wpdb->insert($tables[1],['token_hash'=>hash('sha256',$token2),'member_hash'=>$member2,'created_at'=>gmdate('Y-m-d H:i:s'),'language'=>'en','source'=>'website','placement'=>'popup','client_id'=>'111.222','analytics_consent'=>'granted','confirmed'=>1]);
    $ev=['event_hash'=>str_repeat('e',64),'member_hash'=>$member2,'event_type'=>'subscribe','occurred_at'=>gmdate('Y-m-d H:i:s'),'language'=>'en','source'=>'website','placement'=>'popup','is_test'=>0];$wpdb->insert($tables[0],$ev);
    $intent=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$tables[1]} WHERE token_hash=%s",hash('sha256',$token2)),ARRAY_A);
    vp_newsletter_ga_enqueue($ev,$intent);
    $rev=new WP_REST_Request('POST','/');$rev->set_header('origin',home_url());$rev->set_param('token',$token2);vp_newsletter_revoke($rev);
    vp_newsletter_ga_deliver($ev['event_hash']);$check(count($sent)===1,'withdrawal suppresses queued event');
    $check($wpdb->get_row($wpdb->prepare("SELECT client_id FROM {$tables[1]} WHERE token_hash=%s",hash('sha256',$token2)),ARRAY_A)['client_id']==='','withdrawal erases GA identifier');
    $intent['analytics_consent']='';$ev['event_hash']=str_repeat('f',64);vp_newsletter_ga_enqueue($ev,$intent);
    $check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables[2]}")===2,'no consent cannot queue');
    $intent['analytics_consent']='granted';$intent['client_id']='';vp_newsletter_ga_enqueue($ev,$intent);
    $check((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables[2]}")===2,'no client ID cannot queue');
    $intent['client_id']='111.222';$intent['confirmed']=1;
    $wpdb->update($tables[1],['client_id'=>'111.222','analytics_consent'=>'granted'],['token_hash'=>$intent['token_hash']]);
    $wpdb->insert($tables[0],$ev);vp_newsletter_ga_enqueue($ev,$intent);
    $response_code=0;vp_newsletter_ga_deliver($ev['event_hash']);
    $check(count($sent)===2,'timeout attempted once');
    vp_newsletter_ga_deliver($ev['event_hash']);$check(count($sent)===2,'ambiguous timeout never duplicated');
    $check($wpdb->get_var($wpdb->prepare("SELECT status FROM {$tables[2]} WHERE event_hash=%s",$ev['event_hash']))==='uncertain','ambiguous outcome exposed');
    $denied=new WP_REST_Request('POST','/');$denied->set_header('origin',home_url());$denied->set_param('statistics_consent',false);$check(is_wp_error(vp_newsletter_intent($denied)),'denied consent cannot create intent');
    $check(vp_newsletter_ga_id('reader@example.invalid')==='' && vp_newsletter_ga_id('123.456')==='123.456','GA identifier allowlist excludes PII');
    foreach (['popup_exit','popup_timed','popup_manual'] as $placement) {
        $email=$placement.'@example.invalid';
        $request=new WP_REST_Request('POST','/');$request->set_header('origin',home_url());
        foreach (['email'=>$email,'statistics_consent'=>true,'placement'=>$placement,'client_id'=>'123.456'] as $key=>$value) $request->set_param($key,$value);
        $created=vp_newsletter_intent($request);
        $check(!is_wp_error($created),'popup intent accepted: '.$placement);
        $before=count($sent);vp_newsletter_webhook($make($email));
        $check(count($sent)===$before+1 && end($sent)['events'][0]['params']['placement']===$placement,'confirmed GA payload retains '.$placement);
        $check($wpdb->get_var($wpdb->prepare("SELECT placement FROM {$tables[0]} WHERE member_hash=%s",vp_newsletter_member_hash($email)))===$placement,'ledger retains '.$placement);
    }
    $response_code=204;
    foreach (['popup_exit','popup_timed'] as $placement) {
        $request=new WP_REST_Request('POST','/');$request->set_header('origin',home_url());
        foreach (['statistics_consent'=>true,'placement'=>$placement,'client_id'=>'789.123'] as $key=>$value) $request->set_param($key,$value);
        $view=vp_newsletter_impression($request);$check(!is_wp_error($view),'view accepted');
        $request->set_param('impression_id',$view->get_data()['id']);
        $check(vp_newsletter_valid_impression($request)===$view->get_data()['id'],'view linked');
        $request->set_param('client_id','789.999');$check(vp_newsletter_valid_impression($request)==='','different browser cannot claim view');
        $request->set_param('client_id','789.123');$request->set_param('email','view-'.$placement.'@example.invalid');
        vp_newsletter_intent($request);vp_newsletter_webhook($make('view-'.$placement.'@example.invalid'));
        vp_newsletter_impression($request); // A second view by the same browser.
        $request->set_param('client_id','555.666');vp_newsletter_impression($request); // Unconverted viewer.
        $request->set_param('statistics_consent',false);$check(is_wp_error(vp_newsletter_impression($request)),'denied view rejected');
    }
    $rates=vp_newsletter_popup_report(gmdate('Y-m-d H:i:s',time()-60),gmdate('Y-m-d H:i:s',time()+1));
    foreach($rates['rows'] as $rate) {
        $check((int)$rate['impressions']===3 && (int)$rate['viewers']===2,'repeated exposures deduplicated for viewers');
        $check((int)$rate['confirmed_viewers']===1 && $rate['confirmed_viewer_rate']===0.5,'matched confirmation rate is 50 percent');
    }
    $request->set_param('statistics_consent',true);$request->set_param('client_id','');
    $check(is_wp_error(vp_newsletter_impression($request)),'missing client rejected');
    $request->set_param('client_id','555.666');$request->set_header('origin','https://example.invalid');
    $check(is_wp_error(vp_newsletter_impression($request)),'cross-origin exposure rejected');
    // Known test browsers must disappear from both sides of the rate.
    $wpdb->insert($tables[1],['token_hash'=>str_repeat('9',64),'member_hash'=>vp_newsletter_member_hash('owner@example.invalid'),'client_id'=>'555.666']);
    $rates=vp_newsletter_popup_report(gmdate('Y-m-d H:i:s',time()-60),gmdate('Y-m-d H:i:s',time()+1));
    foreach($rates['rows'] as $rate) $check((int)$rate['viewers']===1 && $rate['confirmed_viewer_rate']===1,'test browser excluded from denominator');
    echo "$assertions newsletter measurement assertions passed; temporary SQL only, no emails.\n";
} finally {
    foreach($tables as $table)$wpdb->query("DROP TEMPORARY TABLE IF EXISTS $table");
    $wpdb->prefix=$original_prefix;remove_filter('pre_option_vp_newsletter_ga4_config',$ga_filter);remove_filter('pre_http_request',$http,10);remove_filter('pre_option_vp_newsletter_webhook_config',$filter);
}
