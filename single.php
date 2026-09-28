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
} ?><?php
$archive_review = [
    1002 => "health", 1017 => "health", 1022 => "health", 1232 => "health",
    1498 => "health", 2196 => "health", 1912 => "finance", 1960 => "finance",
][get_the_ID()] ?? "";
if ($archive_review): ?>
<aside class="article-archive-context" aria-label="<?php echo esc_attr(valon_localized([
    "en" => "Archive context", "sq" => "Konteksti i arkivit", "de" => "Hinweis zum Archiv",
])); ?>">
    <strong><?php echo esc_html(valon_localized([
        "en" => "From the archive", "sq" => "Prej arkivit", "de" => "Aus dem Archiv",
    ])); ?></strong>
    <p><?php echo esc_html($archive_review === "health" ? valon_localized([
        "en" => "I wrote this from personal experience. I am reviewing its health claims; read it as a record of what I tried, not a current recommendation.",
        "sq" => "Këtë e shkrova prej përvojës teme. Po i rishikoj pretendimet për shëndetin; lexoje si shënim të asaj që provova, jo si këshillë të sotme.",
        "de" => "Ich schrieb dies aus eigener Erfahrung. Die Gesundheitsbehauptungen prüfe ich erneut; lies es als Bericht über meinen Versuch, nicht als aktuelle Empfehlung.",
    ]) : valon_localized([
        "en" => "This reflects my view when I wrote it. I am reviewing its figures and financial claims; read it as an older opinion, not current guidance.",
        "sq" => "Ky ishte mendimi im kur e shkrova. Po i rishikoj shifrat dhe pretendimet financiare; lexoje si mendim të mëhershëm, jo si këshillë të sotme.",
        "de" => "Das war meine Sicht zur Zeit der Veröffentlichung. Zahlen und Finanzbehauptungen prüfe ich erneut; lies es als ältere Meinung, nicht als aktuelle Empfehlung.",
    ])); ?></p>
</aside>
<?php endif; ?><article class="prose" data-article="<?php the_ID(); ?>"><?php
the_content();
wp_link_pages();
?></article><?php get_template_part("template-parts/brand-trail"); ?><?php
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
