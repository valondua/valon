<?php get_header(); ?><main id="main" class="container page-main error-page" data-page-error="404"><p class="eyebrow">404 / <?php echo esc_html(
    valon_text("A little off the path", "Pak jashtë rrugës"),
); ?></p><h1><?php echo esc_html(
    valon_text("Let’s find your way back.", "Ta gjejmë rrugën prapë."),
); ?></h1><p><?php echo esc_html(
    valon_text(
        "This page may have moved. There’s plenty more to explore.",
        "Kjo faqe mund të ketë lëvizë. Ka ende shumë me zbulu.",
    ),
); ?></p><a class="button" href="<?php echo esc_url(
    valon_url("start"),
); ?>"><?php echo esc_html(
    valon_text("Start here", "Fillo këtu"),
); ?> ↗</a><?php get_search_form(); ?></main><?php get_footer(); ?>
