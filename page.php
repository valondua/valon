<?php get_header(); ?><main id="main" class="container page-main"><?php while (
    have_posts()
):

    the_post();
    $route =
        get_post_meta(get_the_ID(), "_valon_route", true) ?:
        get_post_field("post_name");
    ?><header class="page-heading"><p class="eyebrow">VALON ASANI / <?php echo esc_html(
    valon_lang() === "sq" ? "SHQIP" : "ENGLISH",
); ?></p><h1><?php the_title(); ?></h1></header><div class="prose"><?php
the_content();
wp_link_pages();
?></div>
<?php if (
    $route === "start"
): ?><section class="section"><div class="card-grid"><?php valon_posts(
    3,
    true,
); ?></div></section><?php endif; ?>
<?php if (
    $route === "writing"
): ?><div class="topic-filter"><a href="<?php echo esc_url(
    valon_url("writing"),
); ?>"><?php echo esc_html(
    valon_text("All writing", "Krejt shkrimet"),
); ?></a><?php foreach (valon_topics() as $s => $t) {
    echo '<a href="' .
        esc_url(valon_topic_url($s)) .
        '">' .
        esc_html(valon_text($t[0], $t[1])) .
        "</a>";
} ?></div><?php
get_search_form();
$paged = max(1, (int) get_query_var("paged"));
$q = new WP_Query([
    "post_type" => "post",
    "post_status" => "publish",
    "posts_per_page" => 12,
    "paged" => $paged,
    "lang" => valon_lang(),
]);
?><div class="card-grid"><?php while ($q->have_posts()) {
    $q->the_post();
    get_template_part("template-parts/card");
} ?></div><nav class="pagination"><?php echo paginate_links([
    "total" => $q->max_num_pages,
    "current" => $paged,
]); ?></nav><?php wp_reset_postdata();endif; ?>
<?php if ($route === "watch" && function_exists("vp_render_feed")):

    $platform = isset($_GET["platform"])
        ? sanitize_key(wp_unslash($_GET["platform"]))
        : "tiktok";
    if (
        !in_array(
            $platform,
            ["tiktok", "instagram", "facebook", "linkedin", "x"],
            true,
        )
    ) {
        $platform = "tiktok";
    }
    ?><nav class="topic-filter" aria-label="Social platforms"><?php foreach (
    [
        "tiktok" => "TikTok",
        "instagram" => "Instagram",
        "facebook" => "Facebook",
        "linkedin" => "LinkedIn",
        "x" => "X",
    ]
    as $key => $label
) {
    echo "<a " .
        ($platform === $key ? 'aria-current="page" ' : "") .
        'href="' .
        esc_url(add_query_arg("platform", $key, valon_url("watch"))) .
        '">' .
        $label .
        "</a>";
} ?></nav><?php echo vp_render_feed($platform, 24);
endif; ?>
<?php if (
    in_array($route, ["start", "newsletter"], true)
): ?><div class="page-signup"><?php valon_newsletter(
    $route,
); ?></div><?php endif;
endwhile; ?></main><?php get_footer(); ?>
