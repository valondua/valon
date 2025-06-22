<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="container">
            <div class="site-branding">
                <div class="site-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <img src="https://www.valonasani.com/wp-content/uploads/2025/06/Valoni.png" alt="<?php bloginfo('name'); ?>" />
                    </a>
                </div>
                
                <?php 
                $valon_tagline = get_theme_mod('valon_tagline', 'A journey of consciousness and authentic expression');
                if ($valon_tagline) : ?>
                    <p class="site-description"><?php echo esc_html($valon_tagline); ?></p>
                <?php endif; ?>
            </div>

            <nav id="site-navigation" class="main-navigation">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'fallback_cb'    => 'valon_default_menu',
                ));
                ?>
            </nav>
        </div>
    </header>

    <div id="content" class="site-content">
