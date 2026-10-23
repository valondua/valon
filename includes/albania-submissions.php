<?php
/** Private investigation inbox. No public posts, media attachments or REST records. */
defined('ABSPATH') || exit;
function valon_tips_table() { global $wpdb; return $wpdb->prefix . 'valon_tips'; }
function valon_tips_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = valon_tips_table();
    dbDelta("CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        receipt char(36) NOT NULL,
        created_at datetime NOT NULL,
        rate_key char(64) NOT NULL,
        reviewed tinyint(1) NOT NULL DEFAULT 0,
        email_status varchar(20) NOT NULL DEFAULT 'pending',
        email_attempts int NOT NULL DEFAULT 0,
        payload longtext NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY receipt (receipt),
        KEY rate_created (rate_key,created_at)
    ) " . $wpdb->get_charset_collate() . ';');
}
function valon_tips_storage() {
    $configured = (string) get_option('valon_tip_storage_dir', '');
    if (!$configured || $configured[0] !== '/') throw new RuntimeException('Private storage not configured');
    $base = realpath($configured);
    $web = realpath(ABSPATH);
    if (!$base || !$web || $base === $web || str_starts_with($base, $web . DIRECTORY_SEPARATOR) || !is_writable($base)) {
        throw new RuntimeException('Private storage unavailable');
    }
    return $base;
}
function valon_tips_headers() {
    if (headers_sent()) return;
    nocache_headers();
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}
function valon_tips_token() {
    valon_tips_headers();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') wp_send_json_error(['message'=>'Use GET.'], 405);
    $body = base64_encode(wp_json_encode(['id'=>wp_generate_uuid4(), 'time'=>time()]));
    wp_send_json_success(['token'=>$body . '.' . hash_hmac('sha256', $body, wp_salt('nonce'))]);
}
add_action('admin_post_nopriv_valon_tip_token', 'valon_tips_token');
add_action('admin_post_valon_tip_token', 'valon_tips_token');
function valon_tips_decode_token($token) {
    $parts = explode('.', $token);
    if (count($parts)!==2 || !hash_equals(hash_hmac('sha256', $parts[0], wp_salt('nonce')), $parts[1])) return false;
    $data = json_decode(base64_decode($parts[0], true) ?: '', true);
    if (!is_array($data) || !preg_match('/^[a-f0-9-]{36}$/D', $data['id'] ?? '') || !is_int($data['time'] ?? null)) return false;
    $age = time() - $data['time'];
    return $age >= 2 && $age <= 7200 ? $data : false;
}
function valon_tips_text($key, $max, $required=false) {
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw)) throw new InvalidArgumentException('Please check the form fields.');
    $v = trim(wp_unslash($raw));
    if (($required && $v==='') || mb_strlen($v)>$max) throw new InvalidArgumentException('Please complete the required fields and keep within the stated limits.');
    return sanitize_textarea_field($v);
}
function valon_tips_validate_uploads($files) {
    if (!$files || ($files['error'][0] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return [];
    foreach (['name','tmp_name','error','size'] as $key) if (!isset($files[$key]) || !is_array($files[$key])) throw new InvalidArgumentException('Invalid attachment request.');
    $n=count($files['name']);
    if ($n>3) throw new InvalidArgumentException('Attach up to 3 files.');
    $out=[]; $total=0; $mime=new finfo(FILEINFO_MIME_TYPE);
    $allowed=['pdf'=>'application/pdf','txt'=>'text/plain','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    for ($i=0;$i<$n;$i++) {
        if (($files['error'][$i] ?? -1)!==UPLOAD_ERR_OK || !is_string($files['tmp_name'][$i] ?? null) || !is_uploaded_file($files['tmp_name'][$i])) throw new InvalidArgumentException('An attachment did not upload. Please select the files again.');
        $tmp=$files['tmp_name'][$i]; $size=filesize($tmp); $total+=$size;
        if ($size<1 || $size>5*1024*1024 || $total>10*1024*1024) throw new InvalidArgumentException('Each file must be at most 5 MB; all attachments together at most 10 MB.');
        $name=sanitize_file_name((string)$files['name'][$i]); $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $type=$mime->file($tmp);
        if (!isset($allowed[$ext]) || $type!==$allowed[$ext]) throw new InvalidArgumentException('Use genuine PDF, TXT, JPG, PNG or WebP files. Other file types are not accepted.');
        if (str_starts_with($type,'image/')) { $info=@getimagesize($tmp); if (!$info || $info[0]*$info[1]>40000000) throw new InvalidArgumentException('Use an image of no more than 40 megapixels.'); }
        if ($ext==='pdf') { $f=fopen($tmp,'rb'); $signature=fread($f,5); fclose($f); if ($signature!=='%PDF-') throw new InvalidArgumentException('The PDF is invalid.'); }
        $out[]=['tmp'=>$tmp,'name'=>mb_substr($name ?: 'attachment.'.$ext,0,180),'mime'=>$type,'size'=>$size,'stored'=>wp_generate_uuid4().'.'.$ext];
    }
    return $out;
}
function valon_tips_submit() {
    valon_tips_headers();
    if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') wp_send_json_error(['message'=>'Use the submission form.'],405);
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>12*1024*1024) wp_send_json_error(['message'=>'The upload is too large. Use at most 10 MB of attachments.'],413);
    $origin=$_SERVER['HTTP_ORIGIN'] ?? '';
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')==='cross-site' || ($origin && untrailingslashit($origin)!==untrailingslashit(home_url()))) wp_send_json_error(['message'=>'Please submit from the investigation page.'],403);
    $token=is_string($_POST['token'] ?? null) ? valon_tips_decode_token(wp_unslash($_POST['token'])) : false;
    if (!$token) wp_send_json_error(['message'=>'The form session expired or is not ready. Please wait a moment or reload the page; your text has not been sent.'],403);
    $lock=null; $folder=null; $saved=false;
    try {
        if (!empty($_POST['website'])) throw new InvalidArgumentException('The submission could not be accepted.');
        $category=valon_tips_text('category',30,true);
        if (!in_array($category,['information','injustice','correction'],true)) throw new InvalidArgumentException('Choose the type of submission.');
        $message=valon_tips_text('message',12000,true);
        if (mb_strlen($message)<20) throw new InvalidArgumentException('Please provide at least 20 characters describing what happened.');
        $email=valon_tips_text('email',254);
        if ($email && !is_email($email)) throw new InvalidArgumentException('Check your email address, or leave it blank.');
        if (($_POST['consent'] ?? '')!=='1') throw new InvalidArgumentException('Please confirm that Valon may review this submission.');
        $payload=['category'=>$category,'message'=>$message,'name'=>valon_tips_text('name',120),'email'=>$email,'language'=>($_POST['language'] ?? '')==='sq'?'sq':'en','context'=>valon_tips_text('context',500),'sources'=>valon_tips_text('sources',3000),'credit'=>valon_tips_text('credit',500),'files'=>[]];
        $base=valon_tips_storage();
        if (disk_free_space($base) < 150*1024*1024) throw new RuntimeException('Storage reserve reached');
        $lock=fopen($base.'/.submit.lock','c'); if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Submission lock unavailable');
        global $wpdb; $table=valon_tips_table();
        $existing=$wpdb->get_var($wpdb->prepare("SELECT receipt FROM $table WHERE receipt=%s",$token['id']));
        if ($existing) {flock($lock,LOCK_UN);fclose($lock);wp_send_json_success(['receipt'=>$existing,'message'=>'Your submission has been received for private review.']);}
        $rate=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'),wp_salt('auth'));
        $cutoff=gmdate('Y-m-d H:i:s',time()-3600);
        $recent=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE rate_key=%s AND created_at>%s",$rate,$cutoff));
        $global=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE created_at>%s",$cutoff));
        if ($recent>=8 || $global>=50) {flock($lock,LOCK_UN);fclose($lock);header('Retry-After: 3600');wp_send_json_error(['message'=>'Too many submissions have arrived recently. Please try again in an hour. Your information has not been saved.'],429);}
        $files=valon_tips_validate_uploads($_FILES['attachments'] ?? []);
        $folder=$base.'/'.$token['id']; if (!mkdir($folder,0700)) throw new RuntimeException('Cannot create private folder');
        foreach ($files as $file) {
            if (!move_uploaded_file($file['tmp'],$folder.'/'.$file['stored'])) throw new RuntimeException('Could not store attachment');
            chmod($folder.'/'.$file['stored'],0600); unset($file['tmp']); $payload['files'][]=$file;
        }
        $result=$wpdb->insert($table,['receipt'=>$token['id'],'created_at'=>gmdate('Y-m-d H:i:s'),'rate_key'=>$rate,'payload'=>wp_json_encode($payload)]);
        if (!$result) throw new RuntimeException('Could not store submission');
        $saved=true; $submission_id=(int)$wpdb->insert_id; flock($lock,LOCK_UN);fclose($lock);$lock=null;
        wp_schedule_single_event(time()+5,'valon_tip_deliver',[$submission_id]);
        wp_send_json_success(['receipt'=>$token['id'],'message'=>'Your submission has been received for private review. It is not published on the website.']);
    } catch (Throwable $e) {
        if (!$saved && $folder && is_dir($folder)) {foreach (glob($folder.'/*') ?: [] as $file) if (is_file($file)) unlink($file);rmdir($folder);}
        if (is_resource($lock)) {flock($lock,LOCK_UN);fclose($lock);}
        $invalid=$e instanceof InvalidArgumentException;
        wp_send_json_error(['message'=>$invalid ? $e->getMessage() : 'We could not save your submission. Keep your text and try again later.'], $invalid ? 400 : 503);
    }
}
add_action('admin_post_nopriv_valon_tip_submit','valon_tips_submit');
add_action('admin_post_valon_tip_submit','valon_tips_submit');
function valon_tips_owner_required() {
    if (!valon_dashboard_owner()) wp_die('Only the site owner may access investigation submissions.', '', ['response'=>403]);
    valon_tips_headers();
}
add_action('admin_menu',function(){if(valon_dashboard_owner()) add_management_page('Investigation inbox','Investigation inbox','manage_options','valon-investigation-inbox','valon_tips_inbox');});
function valon_tips_inbox() {
    valon_tips_owner_required();global $wpdb;$table=valon_tips_table();
    echo '<div class="wrap"><h1>Investigation inbox</h1><p>Private reader submissions. Nothing here is published automatically. Attachments are unscanned files supplied by readers; download and inspect with care.</p>';
    if(!valon_tips_delivery_available())echo '<div class="notice notice-warning"><p>Email delivery is not connected. Submissions are saved privately; delivery to valon@dua.com is pending. <a href="'.esc_url(admin_url('admin.php?page=wp-mail-smtp')).'">Connect a sending service</a>.</p></div>';
    $id=absint($_GET['submission'] ?? 0);
    if ($id) {
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));
        if (!$row) {echo '<p>Submission not found.</p></div>';return;}
        $p=json_decode($row->payload,true);
        echo '<p><a href="'.esc_url(admin_url('tools.php?page=valon-investigation-inbox')).'">← All submissions</a></p><h2>'.esc_html(ucfirst($p['category'])).' · '.esc_html($row->created_at).' UTC</h2><p>Receipt: '.esc_html($row->receipt).' · '.($row->reviewed?'Reviewed':'New').'</p>';
        foreach (['message'=>'Account','context'=>'Where / when','sources'=>'Sources','name'=>'Name (optional)','email'=>'Email (optional)','credit'=>'Contact / attribution preference'] as $key=>$label) echo '<h3>'.esc_html($label).'</h3><div style="white-space:pre-wrap;overflow-wrap:anywhere">'.esc_html($p[$key] ?: 'Not provided').'</div>';
        echo '<p>Email notification: '.esc_html($row->email_status).' (accepted means handed to the mail service, not confirmed inbox delivery).</p><h3>Attachments</h3><ul>';
        foreach ($p['files'] as $index=>$file) { $url=wp_nonce_url(admin_url('admin-post.php?action=valon_tip_download&id='.$id.'&file='.$index),'valon_tip_download_'.$id); echo '<li><a href="'.esc_url($url).'">'.esc_html($file['name']).'</a> · '.esc_html(size_format($file['size'])).'</li>'; }
        echo '</ul><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="valon_tip_review"><input type="hidden" name="id" value="'.$id.'">';wp_nonce_field('valon_tip_review_'.$id);submit_button($row->reviewed?'Mark as new':'Mark as reviewed');echo '</form>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="valon_tip_retry"><input type="hidden" name="id" value="'.$id.'">';wp_nonce_field('valon_tip_retry_'.$id);submit_button('Retry email to valon@dua.com','secondary');echo '</form>';
    } else {
        $page=max(1,absint($_GET['paged'] ?? 1));$rows=$wpdb->get_results($wpdb->prepare("SELECT id,created_at,reviewed,email_status,payload FROM $table ORDER BY id DESC LIMIT 21 OFFSET %d",($page-1)*20));
        echo '<table class="widefat striped"><thead><tr><th>Received (UTC)</th><th>Type</th><th>Status</th><th>Attachments</th><th>Email</th><th>Review</th></tr></thead><tbody>';
        foreach(array_slice($rows,0,20) as $row){$p=json_decode($row->payload,true);echo '<tr><td>'.esc_html($row->created_at).'</td><td>'.esc_html(ucfirst($p['category'])).'</td><td>'.($row->reviewed?'Reviewed':'New').'</td><td>'.count($p['files']).'</td><td>'.esc_html($row->email_status).'</td><td><a href="'.esc_url(admin_url('tools.php?page=valon-investigation-inbox&submission='.$row->id)).'">Open submission</a></td></tr>';}
        if(!$rows)echo '<tr><td colspan="6">No submissions yet.</td></tr>';echo '</tbody></table>';
        if($page>1)echo '<a href="'.esc_url(add_query_arg('paged',$page-1)).'">Previous</a> ';
        if(count($rows)>20)echo '<a href="'.esc_url(add_query_arg('paged',$page+1)).'">Next</a>';
    }
    echo '</div>';
}
add_action('admin_post_valon_tip_review',function(){valon_tips_owner_required();if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST')wp_die('Use POST.','',['response'=>405]);$id=absint($_POST['id'] ?? 0);check_admin_referer('valon_tip_review_'.$id);global $wpdb;$table=valon_tips_table();$wpdb->query($wpdb->prepare("UPDATE $table SET reviewed=1-reviewed WHERE id=%d",$id));wp_safe_redirect(admin_url('tools.php?page=valon-investigation-inbox&submission='.$id));exit;});
add_action('admin_post_valon_tip_download',function(){
    valon_tips_owner_required();$id=absint($_GET['id'] ?? 0);check_admin_referer('valon_tip_download_'.$id);global $wpdb;$table=valon_tips_table();$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));$p=$row?json_decode($row->payload,true):[];$index=filter_var($_GET['file'] ?? null,FILTER_VALIDATE_INT);$file=$index!==false&&$index!==null?($p['files'][$index] ?? null):null;
    if(!$row||!$file||!preg_match('/^[a-f0-9-]{36}\.(pdf|txt|jpg|jpeg|png|webp)$/D',$file['stored']))wp_die('File not found.','',['response'=>404]);
    $root=valon_tips_storage();$path=realpath($root.'/'.$row->receipt.'/'.$file['stored']);
    if(!$path||!str_starts_with($path,$root.'/')||!is_file($path))wp_die('File not found.','',['response'=>404]);
    header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="attachment.'.pathinfo($file['stored'],PATHINFO_EXTENSION).'"; filename*=UTF-8\'\''.rawurlencode($file['name']));header('Content-Length: '.filesize($path));header("Content-Security-Policy: sandbox");readfile($path);exit;
});
function valon_tips_delivery_available() {
    $options=get_option('wp_mail_smtp',[]);
    return !empty($options['mail']['mailer']) && $options['mail']['mailer']!=='mail';
}
function valon_tips_deliver($id) {
    global $wpdb;$table=valon_tips_table();$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));
    if(!$row||$row->email_status==='accepted'||$row->email_status==='sending'||$row->email_attempts>=5)return;
    if(!valon_tips_delivery_available()) {if(!wp_next_scheduled('valon_tip_deliver',[$id]))wp_schedule_single_event(time()+HOUR_IN_SECONDS,'valon_tip_deliver',[$id]);return;}
    // Atomic claim prevents concurrent cron executions emailing the same submission.
    if(!$wpdb->query($wpdb->prepare("UPDATE $table SET email_status='sending',email_attempts=email_attempts+1 WHERE id=%d AND email_status IN ('pending','failed')",$id)))return;
    $p=json_decode($row->payload,true);$body="A reader submitted information through the Albania investigation form.\n\nReceipt: ".$row->receipt."\nReceived (UTC): ".$row->created_at."\nType: ".$p['category']."\nLanguage: ".($p['language'] ?? 'en')."\nName: ".($p['name'] ?: 'Not provided')."\nContact email: ".($p['email'] ?: 'Not provided')."\n\nAccount:\n".$p['message'];
    foreach(['context'=>'Where / when','sources'=>'Sources','credit'=>'Contact / attribution preferences'] as $key=>$label)if(!empty($p[$key]))$body.="\n\n".$label.":\n".$p[$key];
    $body.="\n\nPrivate review: ".admin_url('tools.php?page=valon-investigation-inbox&submission='.$id)."\n\nThis is a reader submission, not verified reporting. Do not publish without review.";
    $attachments=[];
    try {$base=valon_tips_storage();foreach($p['files'] as $file){$path=$base.'/'.$row->receipt.'/'.$file['stored'];if(!is_file($path))throw new RuntimeException('Attachment unavailable');$attachments[]=$path;}
        $headers=['Content-Type: text/plain; charset=UTF-8'];if($p['email'])$headers[]='Reply-To: '.$p['email'];
        $accepted=wp_mail('valon@dua.com','[Albania investigation] '.ucfirst($p['category']).' · '.substr($row->receipt,0,8),$body,$headers,$attachments);
    }catch(Throwable $e){$accepted=false;}
    $wpdb->update($table,['email_status'=>$accepted?'accepted':'failed'],['id'=>$id]);
    if(!$accepted&&!wp_next_scheduled('valon_tip_deliver',[$id]))wp_schedule_single_event(time()+15*MINUTE_IN_SECONDS,'valon_tip_deliver',[$id]);
}
add_action('valon_tip_deliver','valon_tips_deliver');
add_action('admin_post_valon_tip_retry',function(){valon_tips_owner_required();if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST')wp_die('Use POST.','',['response'=>405]);$id=absint($_POST['id'] ?? 0);check_admin_referer('valon_tip_retry_'.$id);global $wpdb;$wpdb->update(valon_tips_table(),['email_status'=>'pending','email_attempts'=>0],['id'=>$id]);valon_tips_deliver($id);wp_safe_redirect(admin_url('tools.php?page=valon-investigation-inbox&submission='.$id));exit;});
