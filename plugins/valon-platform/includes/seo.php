<?php
defined("ABSPATH") || exit();
// The exact broken legacy destination. Never redirect other query-based pages.
add_action("template_redirect", function () {
    if (
        isset($_GET["page_id"]) &&
        (string) $_GET["page_id"] === "1233" &&
        count($_GET) === 1 &&
        is_404()
    ) {
        $pages = get_option("valon_pages", []);
        $id = $pages["en"]["now"] ?? 0;
        if ($id && get_post_status($id) === "publish") {
            wp_safe_redirect(get_permalink($id), 301, "Valon Platform");
            exit();
        }
    }
});
add_filter("wpseo_schema_graph", function ($graph) {
    $person_id = home_url("/#valon-asani");
    $person = [
        "@type" => "Person",
        "@id" => $person_id,
        "name" => "Valon Asani",
        "url" => home_url("/about/"),
        "sameAs" => array_values(VP_Social::profiles()),
    ];
    $out = [];
    foreach ($graph as $node) {
        $types = (array) ($node["@type"] ?? []);
        if (
            in_array("Organization", $types, true) ||
            in_array("Person", $types, true)
        ) {
            continue;
        }
        if (in_array("WebSite", $types, true)) {
            $node["name"] = "Valon Asani";
            $node["publisher"] = ["@id" => $person_id];
        }
        if (
            in_array("Article", $types, true) ||
            in_array("BlogPosting", $types, true)
        ) {
            $node["author"] = ["@id" => $person_id];
            $node["publisher"] = ["@id" => $person_id];
        }
        if (
            in_array("WebPage", $types, true) &&
            is_page() &&
            (get_post_meta(get_queried_object_id(), "_valon_route", true) ===
                "about" ||
                is_page("about"))
        ) {
            $node["@type"] = ["WebPage", "ProfilePage"];
            $node["mainEntity"] = ["@id" => $person_id];
        }
        if (
            isset($node["about"]["@id"]) &&
            str_ends_with($node["about"]["@id"], "#organization")
        ) {
            $node["about"] = ["@id" => $person_id];
        }
        $out[] = $node;
    }
    $out[] = $person;
    return $out;
});
add_filter("wpseo_metadesc", function ($description) {
    if ($description) {
        return $description;
    }
    if (is_front_page()) {
        return vp_text(
            "Ideas and honest lessons from Valon Asani on relationships, personal growth, business and life between cultures. Read the writing and join Letters from Valon.",
            "Ide dhe mësime të sinqerta nga Valon Asani për marrëdhëniet, rritjen personale, biznesin dhe jetën mes kulturave.",
        );
    }
    if (is_singular()) {
        return wp_trim_words(
            wp_strip_all_tags(
                get_the_excerpt() ?:
                get_post_field("post_content", get_queried_object_id()),
            ),
            28,
            "…",
        );
    }
    return $description;
});
add_filter("wpseo_canonical", function ($url) {
    if (
        is_page() &&
        get_post_meta(get_queried_object_id(), "_valon_route", true) === "watch"
    ) {
        return get_permalink(get_queried_object_id());
    }
    if (!$url && is_singular()) {
        return get_permalink(get_queried_object_id());
    }
    return $url;
});
// Only published, real translations are eligible for alternates.
add_filter("pll_rel_hreflang_attributes", function ($links) {
    if (is_singular() && function_exists("pll_get_post_translations")) {
        $real = [];
        foreach (
            pll_get_post_translations(get_queried_object_id())
            as $lang => $id
        ) {
            if (get_post_status($id) === "publish") {
                $real[$lang] = get_permalink($id);
            }
        }
        if (count($real) < 2) {
            return [];
        }
        foreach ($links as $lang => $url) {
            if (!in_array($url, $real, true)) {
                unset($links[$lang]);
            }
        }
    }
    return $links;
});
add_filter("wp_robots", function ($robots) {
    if (
        wp_get_environment_type() !== "production" ||
        is_search() ||
        (is_page() &&
            in_array(
                get_post_meta(get_queried_object_id(), "_valon_route", true),
                ["check-inbox", "welcome", "unsubscribed"],
                true,
            ))
    ) {
        $robots["noindex"] = true;
        unset($robots["index"]);
    }
    return $robots;
});
add_filter("wp_sitemaps_post_types", function ($types) {
    unset($types["valon_social"]);
    return $types;
});
add_filter(
    "wpseo_sitemap_exclude_post_type",
    fn($exclude, $type) => $type === "valon_social" ? true : $exclude,
    10,
    2,
);

// Preserve stored archive content while deferring legacy video players until interaction.
add_filter(
    "the_content",
    function ($html) {
        $html = preg_replace_callback(
            '~<iframe\b[^>]*src=["\']([^"\']+)["\'][^>]*>.*?</iframe>~is',
            function ($m) {
                $src = html_entity_decode($m[1], ENT_QUOTES, "UTF-8");
                $host = wp_parse_url($src, PHP_URL_HOST);
                if (
                    !in_array(
                        $host,
                        [
                            "www.youtube.com",
                            "www.youtube-nocookie.com",
                            "youtube.com",
                            "player.vimeo.com",
                            "www.facebook.com",
                            "www.instagram.com",
                            "www.tiktok.com",
                        ],
                        true,
                    )
                ) {
                    return $m[0];
                }
                return '<div class="legacy-video"><button type="button" data-legacy-embed="' .
                    esc_url($src) .
                    '">' .
                    esc_html(
                        vp_text("Load video from ", "Ngarko videon nga ") .
                            $host,
                    ) .
                    '</button><a href="' .
                    esc_url($src) .
                    '" target="_blank" rel="noopener noreferrer">' .
                    esc_html(vp_text("Open source ↗", "Hap burimin ↗")) .
                    "</a></div>";
            },
            $html,
        );
        if (wp_get_environment_type() === "local") {
            $html = preg_replace_callback(
                '~href=(["\'])https://www\.valonasani\.com/((?!wp-content/)[^"\']*)\1~',
                fn($m) => "href=" .
                    $m[1] .
                    esc_url(home_url("/" . $m[2])) .
                    $m[1],
                $html,
            );
        }
        return $html;
    },
    100,
);

add_filter("wpseo_title", function ($title) {
    return is_front_page() ? get_the_title(get_queried_object_id()) : $title;
});
add_action("wpseo_add_opengraph_images", function ($images) {
    if (is_front_page() || is_page("about")) {
        $images->add_image(
            get_template_directory_uri() . "/assets/portrait.jpg",
        );
    }
});
