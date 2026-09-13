<?php get_header(); ?><main id="main" class="container article-main"><?php
while (have_posts()):
    the_post(); ?><header class="article-heading"><p class="eyebrow"><?php
$cats = get_the_category();
echo esc_html($cats ? $cats[0]->name : "Writing");
?></p><h1><?php the_title(); ?></h1><p class="card-meta">Valon Asani <span>·</span> <time datetime="<?php echo esc_attr(
    get_the_date("c"),
); ?>"><?php echo esc_html(
    get_the_date(valon_lang() === "en" ? "F j, Y" : "d.m.Y"),
); ?></time> <span>·</span> <?php echo esc_html(
    valon_reading_time(),
); ?></p><?php if (
    get_post_meta(get_the_ID(), "_valon_substantive_update", true)
): ?><p class="muted"><?php echo esc_html(
    valon_text("Updated ", "Përditësuar ") . get_the_modified_date(),
); ?></p><?php endif; ?></header><?php if (
    function_exists("vp_video_id") &&
    vp_video_id(get_the_ID())
) {
    echo vp_video_article_player(get_the_ID());
} else {
    valon_render_article_image(get_the_ID());
} ?><article class="prose" data-article="<?php the_ID(); ?>"><?php
the_content();
wp_link_pages();
?></article><?php
$video_article = function_exists("vp_video_id") && vp_video_id(get_the_ID());
if ($video_article) {
    get_template_part("template-parts/video-next");
} else {
    echo '<div class="prose">';
    valon_post_navigation();
    echo "</div>";
}

endwhile;
if (empty($video_article)): ?><section class="section"><h2><?php echo esc_html(
    valon_text("Keep exploring.", "Vazhdo me zbulu."),
); ?></h2><div class="card-grid"><?php valon_posts(
    3,
); ?></div></section><?php endif;
?></main><?php
if (empty($video_article)) {
    get_template_part("template-parts/newsletter");
}
get_footer();


?>
