<?php get_header(); ?><main id="main" class="container page-main"><header class="page-heading"><p class="eyebrow"><?php echo esc_html(
    valon_text("Explore the archive", "Zbulo arkivin"),
); ?></p><h1><?php echo esc_html(
    single_term_title("", false) ?: get_the_archive_title(),
); ?></h1><?php the_archive_description(); ?></header><div class="card-grid"><?php while (
    have_posts()
) {
    the_post();
    get_template_part("template-parts/card");
} ?></div><?php the_posts_pagination(); ?></main><?php get_footer(); ?>
