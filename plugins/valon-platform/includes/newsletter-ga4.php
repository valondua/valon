<?php
/** Consent-gated server reporting. No email, member hash or receipt leaves WordPress. */
defined('ABSPATH') || exit();
function vp_newsletter_ga_id($value) {
    return is_string($value) && preg_match('/^[0-9]{1,20}\.[0-9]{1,20}$/D', $value) ? $value : '';
}
function vp_newsletter_ga_session($value) {
    return is_string($value) && preg_match('/^[1-9][0-9]{0,15}$/D', $value) ? $value : '';
}
function vp_newsletter_revoke($request) {
    if (!vp_newsletter_measurement_origin($request)) return new WP_Error('not_allowed','Not allowed.',['status'=>403]);
    $token=$request->get_param('token');
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) return new WP_Error('invalid','Invalid request.',['status'=>400]);
    global $wpdb; $table=$wpdb->prefix.'vp_newsletter_intents';
    $member=$wpdb->get_var($wpdb->prepare("SELECT member_hash FROM $table WHERE token_hash=%s",hash('sha256',$token)));
    if ($member) {
        // All pending attempts for this contact are covered by withdrawal.
        $wpdb->update($table,['analytics_consent'=>'revoked','client_id'=>'','session_id'=>''],['member_hash'=>$member]);
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}vp_newsletter_ga4 q JOIN $table i ON q.token_hash=i.token_hash SET q.status='revoked' WHERE i.member_hash=%s AND q.status='pending'",$member));
    }
    return new WP_REST_Response(['ok'=>true],200,['Cache-Control'=>'no-store']);
}
function vp_newsletter_ga_enqueue($event, $intent) {
    if (($intent['analytics_consent'] ?? '') !== 'granted' || !vp_newsletter_ga_id($intent['client_id'] ?? '')) return true;
    $cfg=get_option('vp_newsletter_ga4_config',[]);
    if (!empty($event['is_test']) && !in_array($event['member_hash'],$cfg['test_member_hashes'] ?? [],true)) return true;
    global $wpdb;
    return $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}vp_newsletter_ga4 (event_hash,token_hash,occurred_at,status,updated_at) VALUES (%s,%s,%s,'pending',%s)",$event['event_hash'],$intent['token_hash'],$event['occurred_at'],gmdate('Y-m-d H:i:s'))) !== false;
}
function vp_newsletter_ga_payload($row) {
    $params=['language'=>$row['language'],'source'=>$row['source'],'placement'=>$row['placement'],'method'=>'mailchimp_double_opt_in'];
    // Only associate with the original session within Google's 24-hour window.
    if (vp_newsletter_ga_session($row['session_id']) && time()-(int)$row['session_id'] < DAY_IN_SECONDS && (int)$row['session_id'] <= time()) $params['session_id']=(int)$row['session_id'];
    if (!empty($row['is_test'])) { $params['validation_test']=1; $params['debug_mode']=true; }
    return ['client_id'=>$row['client_id'],'timestamp_micros'=>strtotime($row['occurred_at'].' UTC')*1000000,
        'consent'=>['ad_user_data'=>'DENIED','ad_personalization'=>'DENIED'],
        'non_personalized_ads'=>true,'events'=>[['name'=>'newsletter_confirmed','params'=>$params]]];
}
function vp_newsletter_ga_deliver($event_hash) {
    global $wpdb; $queue=$wpdb->prefix.'vp_newsletter_ga4';
    $cfg=get_option('vp_newsletter_ga4_config',[]);
    if (empty($cfg['api_secret']) || empty($cfg['enabled'])) return;
    $row=$wpdb->get_row($wpdb->prepare("SELECT q.*,i.client_id,i.session_id,i.analytics_consent,i.confirmed,i.language,i.source,i.placement,e.is_test FROM $queue q JOIN {$wpdb->prefix}vp_newsletter_intents i ON q.token_hash=i.token_hash JOIN {$wpdb->prefix}vp_newsletter_events e ON e.event_hash=q.event_hash WHERE q.event_hash=%s AND q.status='pending'",$event_hash),ARRAY_A);
    if (!$row) return;
    if ($row['analytics_consent'] !== 'granted' || !$row['confirmed'] || !vp_newsletter_ga_id($row['client_id'])) {
        $wpdb->update($queue,['status'=>'revoked'],['event_hash'=>$event_hash,'status'=>'pending']);return;
    }
    if (strtotime($row['occurred_at'].' UTC') < time()-71*HOUR_IN_SECONDS) {
        $wpdb->update($queue,['status'=>'expired'],['event_hash'=>$event_hash,'status'=>'pending']);return;
    }
    // Atomic ownership: duplicate callbacks, cron and cached browser code cannot double-send.
    if ($wpdb->query($wpdb->prepare("UPDATE $queue SET status='sending',updated_at=%s WHERE event_hash=%s AND status='pending'",gmdate('Y-m-d H:i:s'),$event_hash)) !== 1) return;
    $response=wp_remote_post('https://region1.google-analytics.com/mp/collect?measurement_id=G-9264D19BZD&api_secret='.rawurlencode($cfg['api_secret']),[
        'timeout'=>5,'redirection'=>0,'headers'=>['Content-Type'=>'application/json'],
        'body'=>wp_json_encode(vp_newsletter_ga_payload($row)), 'limit_response_size'=>2048,
    ]);
    $code=is_wp_error($response)?0:wp_remote_retrieve_response_code($response);
    // A 2xx is transport acceptance, not proof of GA ingestion. Ambiguous failures
    // are surfaced for review, never auto-retried: GA custom events lack idempotency.
    $state=$code>=200 && $code<300 ? 'accepted' : ($code===0 ? 'uncertain' : 'failed');
    $wpdb->update($queue,['status'=>$state,'http_status'=>$code,'updated_at'=>gmdate('Y-m-d H:i:s')],['event_hash'=>$event_hash]);
    if ($state==='accepted') $wpdb->update($wpdb->prefix.'vp_newsletter_intents',['reported'=>1],['token_hash'=>$row['token_hash']]);
}
add_filter('cron_schedules',function($s){$s['vp_five_minutes']=['interval'=>300,'display'=>'Every five minutes'];return $s;});
add_action('init',function(){if(!wp_next_scheduled('vp_newsletter_ga_dispatch'))wp_schedule_event(time()+60,'vp_five_minutes','vp_newsletter_ga_dispatch');});
add_action('vp_newsletter_ga_dispatch',function(){
    global $wpdb;
    // A process killed during submission has an unknown outcome; don't resend it.
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}vp_newsletter_ga4 SET status='uncertain' WHERE status='sending' AND updated_at < %s",gmdate('Y-m-d H:i:s',time()-300)));
    foreach($wpdb->get_col("SELECT event_hash FROM {$wpdb->prefix}vp_newsletter_ga4 WHERE status='pending' ORDER BY occurred_at LIMIT 20") as $hash) vp_newsletter_ga_deliver($hash);
});
