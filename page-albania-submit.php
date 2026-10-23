<?php
/** Template Name: Albania investigation — submit information */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',static function(){
    wp_dequeue_style('valon-style');wp_dequeue_style('valon-editorial');wp_dequeue_script('valon-site');
    $base=get_theme_file_uri('/assets/albania/');
    wp_enqueue_style('albania-investigation',$base.'investigation.css',[],filemtime(__DIR__.'/assets/albania/investigation.css'));
    wp_enqueue_style('albania-story',$base.'story.css',['albania-investigation'],filemtime(__DIR__.'/assets/albania/story.css'));
    wp_enqueue_script('albania-submissions',$base.'submissions.js',[],filemtime(__DIR__.'/assets/albania/submissions.js'),true);
},999);
?><!doctype html><html lang="<?php echo valon_albania_is_sq()?'sq':'en'; ?>"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class('albania-investigation submission-page'); ?>><?php wp_body_open();
ob_start(); ?>
<header><a href="<?php echo esc_url(home_url('/')); ?>">Valon Asani</a><span class="logo">DOSSIER</span><a href="<?php echo esc_url(valon_albania_url()); ?>">← Investigation</a></header>
<?php echo valon_albania_language_switch(); ?>
<main class="wrap submission-wrap"><div class="kicker">Private submission</div><h1>Share information.<br>Report an injustice.</h1><p>Tell me what happened and attach any supporting photos or documents. Your submission goes to Valon for review and is not published automatically.</p>
<?php require __DIR__.'/template-parts/albania-submission-form.php'; ?>
</main>
<?php echo valon_albania_localize_html(ob_get_clean());wp_footer(); ?></body></html>
