<?php
defined("ABSPATH") || exit();
function valon_lang()
{
    return function_exists("pll_current_language")
        ? (pll_current_language("slug") ?:
            "en")
        : "en";
}
function valon_text($en, $sq)
{
    return valon_lang() === "sq" ? $sq : $en;
}
function valon_url($route = "home", $lang = "")
{
    $lang = $lang ?: valon_lang();
    $pages = get_option("valon_pages", []);
    if (
        !empty($pages[$lang][$route]) &&
        get_post_status($pages[$lang][$route]) === "publish"
    ) {
        return get_permalink($pages[$lang][$route]);
    }
    return home_url(
        ($lang === "sq" ? "/sq" : "") .
            ($route === "home" ? "/" : "/" . $route . "/"),
    );
}
add_action("after_setup_theme", function () {
    foreach (
        [
            "title-tag",
            "post-thumbnails",
            "responsive-embeds",
            "wp-block-styles",
            "align-wide",
            "editor-styles",
            "automatic-feed-links",
        ]
        as $f
    ) {
        add_theme_support($f);
    }
    add_theme_support("html5", [
        "search-form",
        "comment-form",
        "comment-list",
        "gallery",
        "caption",
        "style",
        "script",
    ]);
    register_nav_menus(["primary" => "Primary navigation"]);
    add_editor_style("style.css");
    add_image_size("valon-card", 840, 620, true);
});
add_action("wp_enqueue_scripts", function () {
    wp_enqueue_style(
        "valon-style",
        get_stylesheet_uri(),
        [],
        filemtime(get_template_directory() . "/style.css"),
    );
    wp_enqueue_script(
        "valon-site",
        get_template_directory_uri() . "/js/site.js",
        [],
        filemtime(get_template_directory() . "/js/site.js"),
        true,
    );
});
function valon_nav()
{
    foreach (
        [
            "start" => ["Start here", "Fillo këtu"],
            "writing" => ["Writing", "Shkrime"],
            "watch" => ["Watch", "Shiko"],
            "about" => ["About", "Rreth meje"],
            "newsletter" => ["Newsletter", "Letrat"],
        ]
        as $r => $label
    ) {
        echo '<a href="' .
            esc_url(valon_url($r)) .
            '">' .
            esc_html(valon_text(...$label)) .
            "</a>";
    }
}
function valon_languages()
{
    foreach (["en" => "EN", "sq" => "SQ"] as $code => $label) {
        $target =
            function_exists("pll_get_post") && is_singular()
                ? pll_get_post(get_queried_object_id(), $code)
                : 0;
        $url =
            $target && get_post_status($target) === "publish"
                ? get_permalink($target)
                : valon_url("home", $code);
        echo '<a href="' .
            esc_url($url) .
            '" lang="' .
            $code .
            '" aria-label="' .
            ($code === "en" ? "English" : "Shqip") .
            '"' .
            (valon_lang() === $code ? ' aria-current="true"' : "") .
            ">" .
            $label .
            "</a>";
    }
}
function valon_topics()
{
    return [
        "relationships" => [
            "Relationships",
            "Marrëdhëniet",
            "Love, connection & the people we choose.",
            "Dashnia, lidhjet dhe njerëzit që zgjedhim.",
        ],
        "personal-growth" => [
            "Personal growth",
            "Rritja personale",
            "Becoming a little more yourself.",
            "Me u ba çdo ditë pak ma shumë vetvetja.",
        ],
        "business-technology" => [
            "Business & technology",
            "Biznesi & teknologjia",
            "Building things. Learning as I go.",
            "Tue ndërtu e tue mësu gjatë rrugës.",
        ],
        "life-between-cultures" => [
            "Life between cultures",
            "Jeta mes kulturave",
            "Kosovo, Switzerland & everything between.",
            "Kosova, Zvicra dhe krejt çka ka mes tyne.",
        ],
    ];
}
function valon_topic_url($slug)
{
    $term = get_term_by(
        "slug",
        $slug . (valon_lang() === "sq" ? "-sq" : ""),
        "category",
    );
    return $term ? get_term_link($term) : valon_url("writing");
}
function valon_reading_time($content = "")
{
    preg_match_all(
        "/[\p{L}\p{N}]+/u",
        wp_strip_all_tags($content ?: get_the_content()),
        $words,
    );
    return max(1, (int) ceil(count($words[0]) / 220)) .
        valon_text(" min read", " min lexim");
}
function valon_posts($limit = 3, $featured = false)
{
    $args = [
        "post_type" => "post",
        "post_status" => "publish",
        "posts_per_page" => $limit,
        "ignore_sticky_posts" => true,
        "lang" => valon_lang(),
    ];
    if ($featured) {
        $args["meta_key"] = "_valon_featured";
        $args["meta_value"] = "1";
    }
    $q = new WP_Query($args);
    if (!$q->have_posts() && $featured) {
        unset($args["meta_key"], $args["meta_value"]);
        $q = new WP_Query($args);
    }
    if (!$q->have_posts() && valon_lang() === "sq") {
        unset($args["meta_key"], $args["meta_value"]);
        $args["lang"] = "en";
        $q = new WP_Query($args);
        if ($q->have_posts()) {
            echo '<p class="muted archive-language-note">Prej arkivit, në anglisht. Shkrimet shqip po përgatiten.</p>';
        }
    }
    if (!$q->have_posts()) {
        echo '<p class="muted">' .
            esc_html(
                valon_text(
                    "New writing is on its way.",
                    "Shkrime të reja së shpejti.",
                ),
            ) .
            "</p>";
    }
    while ($q->have_posts()) {
        $q->the_post();
        get_template_part("template-parts/card");
    }
    wp_reset_postdata();
}
function valon_newsletter($placement = "inline")
{
    if (function_exists("vp_newsletter_form")) {
        echo vp_newsletter_form($placement, valon_lang());
    } else {
        echo "<p>" .
            esc_html(
                valon_text(
                    "Letters from Valon. Coming soon.",
                    "Letra nga Valoni. Së shpejti.",
                ),
            ) .
            "</p>";
    }
}
function valon_social_links()
{
    return [
        "Instagram" => "https://www.instagram.com/valonasanidua/",
        "TikTok" => "https://www.tiktok.com/@valon_asani",
        "Facebook" => "https://www.facebook.com/valonasanidua",
        "LinkedIn" => "https://www.linkedin.com/in/valon-asani/",
        "X" => "https://x.com/ValonAsaniDua",
    ];
}
function valon_post_navigation()
{
    the_post_navigation(["prev_text" => "← %title", "next_text" => "%title →"]);
}
add_filter("excerpt_length", fn() => 24);
add_filter("excerpt_more", fn() => "…");
add_action("customize_register", function ($c) {
    $c->add_setting("valon_portrait", ["sanitize_callback" => "absint"]);
    $c->add_control(
        new WP_Customize_Media_Control($c, "valon_portrait", [
            "label" => "Homepage portrait",
            "section" => "title_tagline",
            "mime_type" => "image",
        ]),
    );
});
