<!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo(
    "charset",
); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main"><?php echo esc_html(
    valon_text("Skip to content", "Kalo te përmbajtja"),
); ?></a>
<header class="site-header"><div class="container header-inner"><a class="wordmark" href="<?php echo esc_url(
    valon_url(),
); ?>" aria-label="Valon Asani — home">Valon Asani</a>
<button class="menu-toggle" aria-label="<?php echo esc_attr(
    valon_text("Menu", "Menyja"),
); ?>" aria-expanded="false" aria-controls="site-navigation"><?php echo esc_html(
    valon_text("Menu", "Menyja"),
); ?> ☰</button>
<nav id="site-navigation" class="main-navigation" aria-label="<?php echo esc_attr(
    valon_text("Main navigation", "Navigimi kryesor"),
); ?>"><?php valon_nav(); ?></nav>
<div class="language-switcher"><?php valon_languages(); ?></div>
<details class="header-search"><summary aria-label="<?php echo esc_attr(
    valon_text("Search writing", "Kërko shkrime"),
); ?>"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></summary><div class="search-panel"><?php get_search_form(); ?></div></details>
<a class="header-newsletter" href="<?php echo esc_url(
    valon_url("newsletter"),
); ?>"><?php echo esc_html(valon_text("Free newsletter", "Letra falas")); ?></a>
</div></header>
