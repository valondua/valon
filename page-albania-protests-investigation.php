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
    wp_enqueue_style('albania-story', $base . 'story.css', ['albania-investigation'], filemtime(__DIR__ . '/assets/albania/story.css'));
    wp_enqueue_script('albania-profiles-data', $base . 'profiles-data.js', [], filemtime(__DIR__ . '/assets/albania/profiles-data.js'), true);
    wp_enqueue_script('albania-profiles', $base . 'profiles.js', ['albania-profiles-data'], filemtime(__DIR__ . '/assets/albania/profiles.js'), true);
    wp_enqueue_script('albania-story-data', $base . 'story-data.js', [], filemtime(__DIR__ . '/assets/albania/story-data.js'), true);
    wp_enqueue_script('albania-story', $base . 'story.js', ['albania-story-data', 'albania-network', 'albania-profiles'], filemtime(__DIR__ . '/assets/albania/story.js'), true);
    wp_enqueue_script('albania-leaflet', $base . 'vendor/leaflet.js', [], '1.9.4', true);
    wp_enqueue_script('albania-network', $base . 'vendor/vis-network.min.js', [], '9.1.9', true);
    wp_enqueue_script('albania-research', $base . 'research.js', [], filemtime(__DIR__ . '/assets/albania/research.js'), true);
    wp_enqueue_script('albania-navigation-map', $base . 'navigation-map.js', ['albania-leaflet'], filemtime(__DIR__ . '/assets/albania/navigation-map.js'), true);
    wp_enqueue_script('albania-investigation', $base . 'investigation.js', ['albania-leaflet', 'albania-network', 'albania-profiles'], filemtime(__DIR__ . '/assets/albania/investigation.js'), true);
}, 999);
?>
<!doctype html><html lang="<?php echo valon_albania_is_sq() ? 'sq' : 'en'; ?>"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('albania-investigation'); ?>><?php wp_body_open(); ?>
<?php
// Both fragments are versioned, reviewed local HTML; no visitor input is interpolated.
$content = str_replace(['ALBANIA_ASSETS', '<!-- ALBANIA_RESEARCH_REGISTER -->', 'ALBANIA_SUBMIT_URL', '<!-- ALBANIA_LANGUAGE_SWITCH -->'],
    [esc_url(get_theme_file_uri('/assets/albania')), file_get_contents(__DIR__ . '/template-parts/albania-research-register.html'), esc_url(valon_albania_submit_url()), valon_albania_language_switch()],
    file_get_contents(__DIR__ . '/template-parts/albania-investigation.html'));
echo valon_albania_localize_html($content);
if(valon_albania_is_sq())echo '<p class="wrap" style="padding:1rem 0;font-size:.8rem">Përkthimi në shqip është asistuar nga Google Translate; terminologjia e provave është rishikuar. Për formulimin e saktë të dokumenteve dhe citimeve, shihni burimet origjinale të lidhura. Korrigjimet janë të mirëpritura.</p>';
?>
<?php wp_footer(); ?></body></html>
