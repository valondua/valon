<!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo(
    "charset",
); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main"><?php echo esc_html(
    valon_text("Skip to content", "Kalo te përmbajtja"),
); ?></a>
<header class="site-header"><div class="container header-inner"><a class="wordmark" href="<?php echo esc_url(
    valon_url(),
); ?>" aria-label="Valon Asani — home">valon asani<span class="brand-dot">.</span></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="site-navigation"><?php echo esc_html(
    valon_text("Menu", "Menyja"),
); ?> ☰</button>
<nav id="site-navigation" class="main-navigation" aria-label="<?php echo esc_attr(
    valon_text("Main navigation", "Navigimi kryesor"),
); ?>"><?php valon_nav(); ?></nav>
<div class="language-switcher"><?php valon_languages(); ?></div></div></header>
