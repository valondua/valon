<?php
/** Private owner workspace. Data is an unregistered, non-autoloaded option, never a post. */
defined('ABSPATH') || exit;
function valon_dashboard_owner() {
    $owner = (int) get_option('vp_editorial_owner', 0);
    return $owner > 0 && is_user_logged_in() && get_current_user_id() === $owner && current_user_can('manage_options');
}
function valon_dashboard_request() {
    $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    return rtrim($path, '/') === rtrim((string) parse_url(home_url('/dashboard'), PHP_URL_PATH), '/');
}
// Runs before canonical redirects and theme output. No public page or REST registration.
add_action('template_redirect', function () {
    if (!valon_dashboard_request()) return;
    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
    if (!defined('DONOTCACHEDB')) define('DONOTCACHEDB', true);
    nocache_headers();
    header('Cache-Control: private, no-store, max-age=0, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/dashboard/')), 302);
        exit;
    }
    if (!valon_dashboard_owner()) {
        status_header(403);
        echo 'This workspace is available only to its owner.';
        exit;
    }
    status_header(200);
    // Bypass persistent object caches for the private inventory.
    global $wpdb;
    $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'valon_dashboard_inventory'));
    $inventory = $raw ? json_decode($raw, true) : []; 
    $inventory = is_array($inventory) ? $inventory : [];
    require __DIR__ . '/dashboard-view.php';
    exit;
}, -100);
