<?php
defined("ABSPATH") || exit();
add_filter("body_class", function ($classes) {
    if (
        is_page() &&
        get_post_meta(get_queried_object_id(), "_valon_route", true) ===
            "newsletter"
    ) {
        $classes[] = "valon-letter-landing";
    }
    return $classes;
});

/** All three editions use the same editorial data, with reviewed UI copy. */
function valon_localized($copy, $language = "")
{
    return $copy[$language ?: valon_lang()] ?? ($copy["en"] ?? "");
}

/** Resolve a shared cover without fetching third-party resources during rendering. */
function valon_article_image($post_id)
{
    $source_id = (int) get_post_meta($post_id, "_vp_source_article", true);
    if (!$source_id && function_exists("pll_get_post")) {
        $source_id = (int) pll_get_post($post_id, "en");
    }
    $source_id =
        $source_id && get_post_type($source_id) === "post"
            ? $source_id
            : $post_id;
    foreach (array_unique([$post_id, $source_id]) as $candidate) {
        if ($thumbnail = get_post_thumbnail_id($candidate)) {
            if ($url = wp_get_attachment_image_url($thumbnail, "large")) {
                return [
                    "url" => $url,
                    "attachment" => $thumbnail,
                    "kind" => "original",
                ];
            }
        }
    }
    $video_cover = get_post_meta($post_id, "_vp_video_cover", true);
    if (
        in_array(
            $video_cover,
            ["valon-reading", "valon-travel", "valon-dua", "valon-portrait"],
            true,
        )
    ) {
        return [
            "url" =>
                get_template_directory_uri() .
                "/assets/" .
                $video_cover .
                ".jpeg",
            "attachment" => 0,
            "kind" => "portrait",
        ];
    }
    foreach (array_unique([$post_id, $source_id]) as $candidate) {
        $legacy = get_post_meta($candidate, "_valon_legacy_image", true);
        if (
            is_string($legacy) &&
            str_starts_with(
                $legacy,
                "https://www.valonasani.com/wp-content/uploads/",
            )
        ) {
            return [
                "url" => esc_url_raw($legacy),
                "attachment" => 0,
                "kind" => "original",
            ];
        }
    }
    static $covers;
    $covers ??=
        json_decode(
            file_get_contents(
                get_template_directory() . "/assets/article-covers.json",
            ),
            true,
        ) ?:
        [];
    $slug = get_post_field("post_name", $source_id);
    $cover = $covers[$slug] ?? null;
    if (!$cover) {
        // Future posts still have a cover; assigning a featured image always overrides it.
        $cover = ["path" => "assets/valon-reading.jpeg", "kind" => "portrait"];
    }
    $url = str_starts_with(
        $cover["path"],
        "https://www.valonasani.com/wp-content/uploads/",
    )
        ? $cover["path"]
        : get_template_directory_uri() . "/" . ltrim($cover["path"], "/");
    return ["url" => $url, "attachment" => 0, "kind" => $cover["kind"]];
}

function valon_render_article_image($post_id, $card = false)
{
    $cover = valon_article_image($post_id);
    $alt = $card
        ? ""
        : ($cover["kind"] === "portrait"
            ? "Valon Asani"
            : wp_strip_all_tags(get_the_title($post_id)));
    $attrs = [
        "alt" => $alt,
        "class" => $card ? "" : "article-cover",
        "loading" => $card ? "lazy" : "eager",
    ];
    if ($cover["attachment"]) {
        echo wp_get_attachment_image(
            $cover["attachment"],
            $card ? "valon-card" : "large",
            false,
            $attrs,
        );
    } else {
        printf(
            '<img src="%s" alt="%s" class="%s" loading="%s" decoding="async" width="1200" height="750">',
            esc_url($cover["url"]),
            esc_attr($alt),
            esc_attr($attrs["class"]),
            esc_attr($attrs["loading"]),
        );
    }
}

// Give shared links the same cover readers see, while respecting manually set Yoast images.
foreach (["wpseo_opengraph_image", "wpseo_twitter_image"] as $filter) {
    add_filter($filter, function ($url) {
        if (!is_singular("post")) {
            return $url;
        }
        $id = get_queried_object_id();
        $key =
            current_filter() === "wpseo_twitter_image"
                ? "_yoast_wpseo_twitter-image"
                : "_yoast_wpseo_opengraph-image";
        return get_post_meta($id, $key, true)
            ? $url
            : valon_article_image($id)["url"];
    });
}

function valon_media_data()
{
    static $data;
    $data ??= json_decode(
        file_get_contents(get_template_directory() . "/assets/media.json"),
        true,
    );
    return $data;
}
