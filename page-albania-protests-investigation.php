<?php
/** Template Name: Albania protests — interactive investigation */
if (!defined('ABSPATH')) { exit; }
// The dossier owns its layout; retain WordPress SEO, consent and other head/footer hooks.
add_action('wp_enqueue_scripts', static function () {
    wp_dequeue_style('valon-style');
    wp_dequeue_style('valon-editorial');
    wp_dequeue_script('valon-site');
    $base = get_theme_file_uri('/assets/albania/');
    wp_enqueue_style('albania-leaflet', $base . 'vendor/leaflet.css', [], '1.9.4');
    wp_enqueue_style('albania-investigation', $base . 'investigation.css', ['albania-leaflet'], filemtime(__DIR__ . '/assets/albania/investigation.css'));
    wp_enqueue_script('albania-leaflet', $base . 'vendor/leaflet.js', [], '1.9.4', true);
    wp_enqueue_script('albania-network', $base . 'vendor/vis-network.min.js', [], '9.1.9', true);
    wp_enqueue_script('albania-investigation', $base . 'investigation.js', ['albania-leaflet', 'albania-network'], filemtime(__DIR__ . '/assets/albania/investigation.js'), true);
}, 999);
?>
<!doctype html><html lang="en"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('albania-investigation'); ?>><?php wp_body_open(); ?>
<?php readfile(__DIR__ . '/template-parts/albania-investigation.html'); ?>
<?php wp_footer(); ?></body></html>
