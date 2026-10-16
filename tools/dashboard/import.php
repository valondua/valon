<?php
/** Run only through WP CLI with an outside-document-root JSON file as first argument. */
if (!defined('WP_CLI') || !WP_CLI) { http_response_code(404); exit; }
if (!valon_dashboard_owner()) WP_CLI::error('Run as the existing configured owner using --user.');
$path = realpath($args[0] ?? '');
$root = realpath(ABSPATH);
if (!$path || !is_file($path) || str_starts_with($path, $root . DIRECTORY_SEPARATOR)) WP_CLI::error('Inventory must be outside the web root.');
$data = json_decode(file_get_contents($path), true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) WP_CLI::error('Expected an items array.');
$statuses = ['needs-valon','in-progress','blocked','done'];
foreach ($data['items'] as $item) {
    if (!is_array($item) || !in_array($item['status'] ?? '', $statuses, true) || empty($item['title']) || empty($item['verified'])) WP_CLI::error('Every item needs a valid status, title and verification date.');
    foreach (($item['sources'] ?? []) as $source) {
        if (parse_url($source['url'] ?? '', PHP_URL_SCHEME) !== 'https') WP_CLI::error('Sources must use HTTPS.');
    }
}
// Deliberately avoid update_option/get_option and their persistent object caches.
global $wpdb;
$result = $wpdb->replace($wpdb->options, ['option_name'=>'valon_dashboard_inventory', 'option_value'=>wp_json_encode($data), 'autoload'=>'off'], ['%s','%s','%s']);
if ($result === false) WP_CLI::error('Inventory write failed.');
wp_cache_delete('valon_dashboard_inventory', 'options');
WP_CLI::success('Private inventory replaced. No posts, REST endpoints or public files created.');
