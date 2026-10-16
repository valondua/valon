<?php
// Isolated control-flow test; no credentials, network or production database.
define('ABSPATH', __DIR__);
$callback = null; $reads = 0;
$mode = $argv[1] ?? 'anonymous';
$_SERVER['REQUEST_URI'] = $argv[2] ?? '/dashboard/';
function add_action($name, $fn, $priority) { $GLOBALS['callback'] = $fn; }
function get_option($name, $fallback=0) { return $GLOBALS['mode']==='unconfigured' ? 0 : 42; }
function is_user_logged_in() { return $GLOBALS['mode'] !== 'anonymous'; }
function get_current_user_id() { return $GLOBALS['mode']==='other-admin' ? 7 : 42; }
function current_user_can($cap) { return $GLOBALS['mode'] !== 'demoted'; }
function home_url($path) { return 'https://example.test'.$path; }
function nocache_headers() {}
function wp_safe_redirect($url,$status) { echo "REDIRECT:$status"; }
function wp_login_url($url) { return 'https://example.test/wp-login.php'; }
function status_header($code) { echo "STATUS:$code\n"; }
function esc_url($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function get_template_directory_uri() { return '/theme'; }
function wp_logout_url($url) { return '/logout'; }
class DB { public $options='wp_options'; function prepare($s,$v){return $s;} function get_var($s){$GLOBALS['reads']++; return json_encode(['items'=>[['status'=>'needs-valon','title'=>'PRIVATE_CANARY','summary'=>'<script>alert(1)</script>','verified'=>'2026-10-16']]]);} }
$wpdb = new DB;
register_shutdown_function(function(){ echo "\nPRIVATE_READS:".$GLOBALS['reads']; });
require __DIR__.'/../../includes/dashboard.php';
$callback();
