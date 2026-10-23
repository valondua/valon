<?php
/** Consented popup exposure cohorts. No raw client ID or email is stored here. */
defined('ABSPATH') || exit();
function vp_newsletter_impressions_install() {
    if (get_option('vp_newsletter_impressions_version') === '1') return;
    global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
    $charset=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$wpdb->prefix}vp_newsletter_impressions (
        id char(64) NOT NULL,
        visitor_hash char(64) NOT NULL,
        placement varchar(20) NOT NULL,
        occurred_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY occurred_at (occurred_at),
        KEY visitor_hash (visitor_hash)
    ) $charset;");
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->prefix.'vp_newsletter_impressions'))) === $wpdb->prefix.'vp_newsletter_impressions') {
        update_option('vp_newsletter_impressions_version','1',false);
        if (!get_option('vp_newsletter_impressions_started')) update_option('vp_newsletter_impressions_started',gmdate('Y-m-d H:i:s'),false);
    }
}
add_action('init','vp_newsletter_impressions_install');
function vp_newsletter_viewer_hash($client) { return hash_hmac('sha256',$client,wp_salt('auth')); }
function vp_newsletter_impression($request) {
    if (!vp_newsletter_measurement_origin($request) || $request->get_param('statistics_consent') !== true || is_user_logged_in()) return new WP_Error('not_allowed','Not allowed.',['status'=>403]);
    $client=vp_newsletter_ga_id($request->get_param('client_id'));
    $placement=vp_newsletter_measurement_choice($request->get_param('placement'),['popup_exit','popup_timed','popup_manual'],'');
    if (!$client || !$placement) return new WP_Error('invalid','Invalid request.',['status'=>400]);
    global $wpdb; $table=$wpdb->prefix.'vp_newsletter_impressions'; $visitor=vp_newsletter_viewer_hash($client);
    // Bound automated noise without saving an IP address.
    if ((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE visitor_hash=%s AND occurred_at>= %s",$visitor,gmdate('Y-m-d H:i:s',time()-HOUR_IN_SECONDS)))>=20) return new WP_Error('limited','Try later.',['status'=>429]);
    $id=bin2hex(random_bytes(32));
    if (!$wpdb->insert($table,['id'=>$id,'visitor_hash'=>$visitor,'placement'=>$placement,'occurred_at'=>gmdate('Y-m-d H:i:s')])) return new WP_Error('unavailable','Unavailable.',['status'=>503]);
    return new WP_REST_Response(['id'=>$id],200,['Cache-Control'=>'no-store']);
}
function vp_newsletter_valid_impression($request) {
    $id=$request->get_param('impression_id'); $client=vp_newsletter_ga_id($request->get_param('client_id'));
    if (!is_string($id) || !preg_match('/^[a-f0-9]{64}$/D',$id) || !$client) return '';
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}vp_newsletter_impressions WHERE id=%s AND visitor_hash=%s AND placement=%s AND occurred_at>=%s",$id,vp_newsletter_viewer_hash($client),$request->get_param('placement'),gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS))) ?: '';
}
function vp_newsletter_popup_report($start,$end) {
    global $wpdb; $p=$wpdb->prefix;
    // Cohort by exposure date, not confirmation date. Repeated views do not multiply people.
    // Exclude the entire known test browser cohort from numerator AND denominator.
    $test_hashes=get_option('vp_newsletter_webhook_config',[])['test_hashes'] ?? [];
    $test_clients=[];
    foreach ($test_hashes as $member) {
        foreach ($wpdb->get_col($wpdb->prepare("SELECT client_id FROM {$p}vp_newsletter_intents WHERE member_hash=%s AND client_id<>''",$member)) as $client) $test_clients[]=vp_newsletter_viewer_hash($client);
    }
    $exclude='';
    if ($test_clients) $exclude=$wpdb->prepare(' AND v.visitor_hash NOT IN ('.implode(',',array_fill(0,count($test_clients),'%s')).')',...$test_clients);
    $rows=$wpdb->get_results($wpdb->prepare("SELECT v.placement,COUNT(DISTINCT v.id) AS impressions,COUNT(DISTINCT v.visitor_hash) AS viewers,
        COUNT(DISTINCT CASE WHEN e.event_hash IS NOT NULL THEN v.visitor_hash END) AS confirmed_viewers,
        COUNT(DISTINCT e.member_hash) AS confirmed_contacts
        FROM {$p}vp_newsletter_impressions v
        LEFT JOIN {$p}vp_newsletter_intents i ON i.impression_id=v.id AND i.analytics_consent='granted'
        LEFT JOIN {$p}vp_newsletter_ga4 q ON q.token_hash=i.token_hash
        LEFT JOIN {$p}vp_newsletter_events e ON e.event_hash=q.event_hash AND e.event_type='subscribe' AND e.is_test=0 AND e.occurred_at<=%s
        WHERE v.occurred_at>=%s AND v.occurred_at<%s $exclude GROUP BY v.placement",$end,$start,$end),ARRAY_A);
    foreach ($rows as &$row) $row['confirmed_viewer_rate']=(int)$row['viewers'] ? (int)$row['confirmed_viewers']/(int)$row['viewers'] : null;
    return ['started_utc'=>get_option('vp_newsletter_impressions_started'),'start_utc'=>$start,'end_utc'=>$end,'rows'=>$rows,
        'definition'=>'Confirmed distinct browser visitors / distinct consented browser visitors exposed in this window, confirmed by cutoff. Only linked confirmations qualify. Not cross-device people or randomized lift. Pending confirmations may mature later. Missing consent or GA identifiers are excluded; legacy popup traffic cannot be backfilled.'];
}
add_action('rest_api_init',function(){register_rest_route('valon/v1','/newsletter-impression',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'vp_newsletter_impression']);});
add_action('vp_hourly_sync',function(){global $wpdb;$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}vp_newsletter_impressions WHERE occurred_at<%s",gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS)));});
