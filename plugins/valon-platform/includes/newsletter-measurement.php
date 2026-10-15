<?php
/** Signed Mailchimp confirmations; no raw subscriber data in the measurement tables. */
defined('ABSPATH') || exit();

function vp_newsletter_measurement_install() {
    if (get_option('vp_newsletter_measurement_version') === '1') return;
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$wpdb->prefix}vp_newsletter_intents (
        token_hash char(64) NOT NULL,
        member_hash char(64) NOT NULL,
        created_at datetime NOT NULL,
        language varchar(8) NOT NULL,
        source varchar(20) NOT NULL,
        placement varchar(20) NOT NULL,
        confirmed tinyint NOT NULL DEFAULT 0,
        reported tinyint NOT NULL DEFAULT 0,
        PRIMARY KEY  (token_hash),
        KEY member_hash (member_hash),
        KEY created_at (created_at)
    ) $charset;");
    dbDelta("CREATE TABLE {$wpdb->prefix}vp_newsletter_events (
        event_hash char(64) NOT NULL,
        member_hash char(64) NOT NULL,
        event_type varchar(20) NOT NULL,
        occurred_at datetime NOT NULL,
        language varchar(8) NOT NULL,
        source varchar(20) NOT NULL,
        placement varchar(20) NOT NULL,
        is_test tinyint NOT NULL DEFAULT 0,
        PRIMARY KEY  (event_hash),
        KEY member_hash (member_hash),
        KEY occurred_at (occurred_at)
    ) $charset;");
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix.'vp_newsletter_events'))) !== $wpdb->prefix.'vp_newsletter_events' || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix.'vp_newsletter_intents'))) !== $wpdb->prefix.'vp_newsletter_intents') return;
    update_option('vp_newsletter_measurement_version', '1', false);
    if (!get_option('vp_newsletter_measurement_started')) update_option('vp_newsletter_measurement_started', gmdate('Y-m-d H:i:s'), false);
}
add_action('init', 'vp_newsletter_measurement_install');

function vp_newsletter_member_hash($email) {
    return hash_hmac('sha256', strtolower(trim($email)), wp_salt('auth'));
}
function vp_newsletter_measurement_choice($value, $allowed, $fallback) {
    return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
}
function vp_newsletter_signature_valid($secret, $header, $body, $now) {
    if (!$secret || !preg_match('/(?:^|,)\s*t=(\d+)(?:,|$)/', $header, $t) || !preg_match('/(?:^|,)\s*v1=([a-f0-9]{64})(?:,|$)/', $header, $s)) return false;
    return abs($now - (int)$t[1]) <= 300 && hash_equals(hash_hmac('sha256', $t[1] . '.' . $body, $secret), $s[1]);
}
function vp_newsletter_measurement_origin($request) {
    $origin = $request->get_header('origin');
    return $origin && untrailingslashit($origin) === untrailingslashit(home_url());
}
function vp_newsletter_intent($request) {
    if (!vp_newsletter_measurement_origin($request) || $request->get_param('statistics_consent') !== true) return new WP_Error('not_allowed', 'Not allowed.', ['status'=>403]);
    $email = $request->get_param('email');
    if (!is_string($email) || strlen($email) > 254 || !is_email($email)) return new WP_Error('invalid', 'Invalid request.', ['status'=>400]);
    $bucket = 'vp_intent_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', wp_salt('nonce'));
    $attempts = (int)get_transient($bucket);
    if ($attempts >= 20) return new WP_Error('limited', 'Try later.', ['status'=>429]);
    set_transient($bucket, $attempts + 1, HOUR_IN_SECONDS);
    $token = bin2hex(random_bytes(32));
    global $wpdb;
    $ok = $wpdb->insert($wpdb->prefix . 'vp_newsletter_intents', [
        'token_hash'=>hash('sha256', $token), 'member_hash'=>vp_newsletter_member_hash($email), 'created_at'=>gmdate('Y-m-d H:i:s'),
        'language'=>vp_newsletter_measurement_choice($request->get_param('language'), ['en','sq','de'], 'en'),
        'source'=>vp_newsletter_measurement_choice($request->get_param('source'), ['website','tiktok','instagram','facebook','linkedin','x','newsletter'], 'website'),
        'placement'=>vp_newsletter_measurement_choice($request->get_param('placement'), ['hero','footer','landing','newsletter','inline','popup'], 'inline'),
    ]);
    if (!$ok) return new WP_Error('unavailable', 'Unavailable.', ['status'=>503]);
    return new WP_REST_Response(['token'=>$token, 'expires'=>time()+30*DAY_IN_SECONDS], 200, ['Cache-Control'=>'no-store']);
}
function vp_newsletter_confirmation_status($request) {
    if (!vp_newsletter_measurement_origin($request)) return new WP_Error('not_allowed', 'Not allowed.', ['status'=>403]);
    $token = $request->get_param('token');
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return new WP_Error('invalid', 'Invalid request.', ['status'=>400]);
    global $wpdb;
    $table = $wpdb->prefix . 'vp_newsletter_intents';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token_hash=%s AND created_at >= %s", hash('sha256',$token), gmdate('Y-m-d H:i:s', time()-30*DAY_IN_SECONDS)), ARRAY_A);
    $confirmed = false;
    if ($row && $row['confirmed'] && !$row['reported']) {
        $confirmed = $wpdb->query($wpdb->prepare("UPDATE $table SET reported=1 WHERE token_hash=%s AND reported=0 AND confirmed=1", $row['token_hash'])) === 1;
    }
    $result = ['confirmed'=>$confirmed, 'finished'=>(bool)($row && $row['reported']) || !$row];
    if ($confirmed) $result['parameters'] = ['language'=>$row['language'], 'source'=>$row['source'], 'placement'=>$row['placement'], 'method'=>'mailchimp_double_opt_in'];
    return new WP_REST_Response($result, 200, ['Cache-Control'=>'no-store']);
}
function vp_newsletter_webhook($request) {
    $config = get_option('vp_newsletter_webhook_config', []);
    $token = $request->get_param('token');
    if (empty($config['token']) || !is_string($token) || !hash_equals($config['token'], $token)) return new WP_Error('forbidden','Forbidden.',['status'=>403]);
    if ($request->get_method() === 'GET') return new WP_REST_Response(['ok'=>true],200,['Cache-Control'=>'no-store']);
    if (strlen($request->get_body()) > 65536 || !vp_newsletter_signature_valid($config['signing_secret'] ?? '', $request->get_header('x-mailchimp-signature'), $request->get_body(), time())) return new WP_Error('signature','Invalid signature.',['status'=>403]);
    $type = $request->get_param('type'); $data = $request->get_param('data');
    if (!is_array($data) || ($data['list_id'] ?? '') !== '293f2ca94d') return new WP_Error('audience','Invalid audience.',['status'=>400]);
    if (!in_array($type,['subscribe','unsubscribe','cleaned'],true)) return ['ok'=>true];
    $email = $data['email'] ?? ''; $merges = $data['merges'] ?? [];
    if (!is_string($email) || !is_email($email) || !is_array($merges)) return new WP_Error('payload','Invalid payload.',['status'=>400]);
    $interests = $merges['INTERESTS'] ?? '';
    $letters = is_string($interests) && in_array('Letters from Valon', array_map('trim',explode(',',$interests)),true);
    $member = vp_newsletter_member_hash($email);
    global $wpdb; $events = $wpdb->prefix.'vp_newsletter_events'; $intents = $wpdb->prefix.'vp_newsletter_intents';
    $known = $wpdb->get_var($wpdb->prepare("SELECT member_hash FROM $events WHERE member_hash=%s LIMIT 1",$member));
    if (!$letters && !($known && $type !== 'subscribe')) return ['ok'=>true];
    $fired = $request->get_param('fired_at');
    $date = is_string($fired) ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$fired,new DateTimeZone('UTC')) : false;
    if (!$date || $date->format('Y-m-d H:i:s') !== $fired || $date->getTimestamp() > time()+300) return new WP_Error('date','Invalid event date.',['status'=>400]);
    // Only a previously recorded, consented website attempt can establish site attribution.
    $intent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $intents WHERE member_hash=%s AND created_at<=%s AND created_at>=%s ORDER BY created_at DESC LIMIT 1",$member,$fired,gmdate('Y-m-d H:i:s',$date->getTimestamp()-30*DAY_IN_SECONDS)),ARRAY_A);
    $test = in_array($member, $config['test_hashes'] ?? [], true);
    $event = ['event_hash'=>hash('sha256',$member.'|'.$type.'|'.$fired),'member_hash'=>$member,'event_type'=>$type,'occurred_at'=>$fired,
        'language'=>$intent['language'] ?? vp_newsletter_measurement_choice($merges['VLANG'] ?? '',['en','sq','de'],'unknown'),
        'source'=>$intent['source'] ?? 'unknown','placement'=>$intent['placement'] ?? 'unknown','is_test'=>(int)$test];
    $columns = implode(',',array_keys($event));
    $inserted = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $events ($columns) VALUES (%s,%s,%s,%s,%s,%s,%s,%d)",...array_values($event)));
    if ($inserted === false) return new WP_Error('storage','Retry later.',['status'=>503]);
    if ($type === 'subscribe' && $inserted === 1 && $intent && !$test) {
        // One browser claim per confirmation; retries and older attempts cannot duplicate it.
        $wpdb->update($intents,['confirmed'=>1],['token_hash'=>$intent['token_hash']]);
    }
    if ($type !== 'subscribe') $wpdb->update($intents,['confirmed'=>0,'reported'=>1],['member_hash'=>$member]);
    return ['ok'=>true];
}
function vp_newsletter_measurement_report() {
    global $wpdb; $table=$wpdb->prefix.'vp_newsletter_events';
    return ['tracking_started_utc'=>get_option('vp_newsletter_measurement_started'), 'days'=>30,
        'note'=>'Signed contact-originated Mailchimp events only. Tests excluded. Unknown source is not attributed to this website. Not a historical audience total; GA requires a consented same-browser return.',
        'rows'=>$wpdb->get_results($wpdb->prepare("SELECT event_type,language,source,placement,COUNT(*) AS events,COUNT(DISTINCT member_hash) AS unique_contacts FROM $table WHERE is_test=0 AND occurred_at >= %s GROUP BY event_type,language,source,placement",gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS)),ARRAY_A)];
}
add_action('rest_api_init',function(){
    register_rest_route('valon/v1','/newsletter-intent',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'vp_newsletter_intent']);
    register_rest_route('valon/v1','/newsletter-confirmation',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>'vp_newsletter_confirmation_status']);
    register_rest_route('valon/v1','/mailchimp-confirmation/(?P<token>[a-f0-9]{64})',['methods'=>['GET','POST'],'permission_callback'=>'__return_true','callback'=>'vp_newsletter_webhook']);
    register_rest_route('valon/v1','/newsletter-report',['methods'=>'GET','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>'vp_newsletter_measurement_report']);
});
add_action('vp_hourly_sync',function(){
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}vp_newsletter_intents WHERE created_at < %s",gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS)));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}vp_newsletter_events WHERE occurred_at < %s",gmdate('Y-m-d H:i:s',time()-400*DAY_IN_SECONDS)));
});
if (defined('WP_CLI') && WP_CLI) WP_CLI::add_command('valon newsletter-report',function(){ WP_CLI::line(wp_json_encode(vp_newsletter_measurement_report(),JSON_PRETTY_PRINT)); });

add_action('admin_menu',function(){
    add_management_page('Newsletter confirmations','Newsletter confirmations','manage_options','valon-newsletter-confirmations',function(){
        if (!current_user_can('manage_options')) wp_die('Administrator access required.');
        $report=vp_newsletter_measurement_report();
        echo '<div class="wrap"><h1>Newsletter confirmations</h1><p>Letters from Valon · last 30 days · UTC</p>';
        echo '<p>Tracking started: '.esc_html($report['tracking_started_utc']).' UTC. Earlier subscriptions are not backfilled. Test addresses are excluded.</p>';
        echo '<p>This report counts signed Mailchimp subscription notifications from contacts. It excludes admin/API additions and BeTheOne-only subscriptions. Changes to newsletter preferences on already-subscribed contacts are not counted as new subscriptions.</p>';
        echo '<table class="widefat striped"><thead><tr><th>Event</th><th>Language</th><th>Source</th><th>Placement</th><th>Events</th><th>Distinct contacts in row</th></tr></thead><tbody>';
        foreach($report['rows'] as $row){echo '<tr>';foreach(['event_type','language','source','placement','events','unique_contacts'] as $key)echo '<td>'.esc_html($row[$key]).'</td>';echo '</tr>';}
        if (!$report['rows']) echo '<tr><td colspan="6">No non-test confirmations received yet.</td></tr>';
        echo '</tbody></table><p>Unknown source means no matching consented website signup attempt. Distinct contacts across rows may overlap. A resubscription is another event, not necessarily a new person.</p>';
        echo '<p>GA4 newsletter_confirmed is a consented subset: the visitor must return in the same browser within 30 days. Use this server report for confirmation totals and Site Kit for attributed website behaviour.</p></div>';
    });
});
