<?php get_header(); ?><main id="main" class="container page-main"><header class="page-heading"><h1><?php echo esc_html(
    valon_text("Search the writing", "Kërko në shkrime"),
); ?></h1><?php get_search_form(); ?></header><div class="card-grid"><?php
if (!have_posts()) {
    echo "<p>" .
        esc_html(
            valon_text(
                "No results. Try another word.",
                "Pa rezultate. Provo një fjalë tjetër.",
            ),
        ) .
        "</p>";
}
while (have_posts()) {
    the_post();
    get_template_part("template-parts/card");
}
?></div><?php the_posts_pagination(); ?></main><?php get_footer(); ?>
