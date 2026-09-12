<?php get_header(); ?><main id="main" class="container article-main"><?php while (
    have_posts()
):
    the_post(); ?><header class="article-heading"><p class="eyebrow"><?php
$cats = get_the_category();
echo esc_html($cats ? $cats[0]->name : "Writing");
?></p><h1><?php the_title(); ?></h1><p class="card-meta">Valon Asani <span>·</span> <time datetime="<?php echo esc_attr(
    get_the_date("c"),
); ?>"><?php echo esc_html(
    get_the_date("F j, Y"),
); ?></time> <span>·</span> <?php echo esc_html(
    valon_reading_time(),
); ?></p><?php if (
    get_post_meta(get_the_ID(), "_valon_substantive_update", true)
): ?><p class="muted"><?php echo esc_html(
    valon_text("Updated ", "Përditësuar ") . get_the_modified_date(),
); ?></p><?php endif; ?></header><?php valon_render_article_image(
    get_the_ID(),
); ?><article class="prose" data-article="<?php the_ID(); ?>"><?php
the_content();
wp_link_pages();
?></article><div class="prose"><?php valon_post_navigation(); ?></div><?php
endwhile; ?><section class="section"><h2><?php echo esc_html(
    valon_text("Keep exploring.", "Vazhdo me zbulu."),
); ?></h2><div class="card-grid"><?php valon_posts(
    3,
); ?></div></section></main><?php
get_template_part("template-parts/newsletter");
get_footer();

?>
