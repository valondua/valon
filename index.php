<?php get_header(); ?><main id="main" class="container page-main"><header class="page-heading"><p class="eyebrow">VALON ASANI</p><h1><?php echo esc_html(
    valon_text("Writing & reflections.", "Shkrime & mendime."),
); ?></h1></header><div class="card-grid"><?php while (have_posts()) {
    the_post();
    get_template_part("template-parts/card");
} ?></div><?php the_posts_pagination(); ?></main><?php get_footer(); ?>
