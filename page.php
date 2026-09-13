<?php get_header(); ?><main id="main" class="container page-main"><?php while (
    have_posts()
):

    the_post();
    $route =
        get_post_meta(get_the_ID(), "_valon_route", true) ?:
        get_post_field("post_name");
    if ($route === "media") {
        get_template_part("template-parts/media");
        continue;
    }
    if ($route === "newsletter") {
        get_template_part("template-parts/letter-landing");
        continue;
    }
    get_template_part("template-parts/page-intro", null, ["route" => $route]);
    if ($route === "about") {
        get_template_part("template-parts/photo-journal");
    }
    ?>
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
if (!$q->have_posts() && valon_lang() !== "en") {
    $q = new WP_Query([
        "post_type" => "post",
        "post_status" => "publish",
        "posts_per_page" => 12,
        "paged" => $paged,
        "lang" => "en",
    ]);
    echo '<p class="archive-note">' .
        esc_html(
            valon_text(
                "From the English archive. Translations are being prepared.",
                "Nga arkivi në anglisht. Përkthimet janë tue u përgatitë.",
            ),
        ) .
        "</p>";
}
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
<?php if (in_array($route, ["watch", "start", "contact"], true)) {
    get_template_part("template-parts/connect");
} ?>
<?php if (
    $route === "start"
): ?><div class="page-signup"><?php valon_newsletter(
    $route,
); ?></div><?php endif;
endwhile; ?></main><?php get_footer(); ?>
