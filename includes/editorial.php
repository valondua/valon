<?php
defined("ABSPATH") || exit();
// YARPP's supported per-post filter: the video journey has its own localized next step.
add_filter("noyarpp", function ($disabled) {
    return $disabled ||
        (function_exists("vp_video_id") && vp_video_id(get_the_ID()));
});
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
    // Source-specific, locally stored thumbnails are shared across translations.
    // Never call TikTok or depend on its expiring CDN URLs while rendering a page.
    static $video_covers;
    $video_covers ??= json_decode(
        file_get_contents(get_template_directory() . "/assets/video-covers.json"),
        true,
    ) ?: [];
    $video_id = get_post_meta($post_id, "_vp_video_id", true);
    $source_cover = $video_covers[$video_id] ?? null;
    if ($source_cover && is_file(get_template_directory() . "/" . $source_cover["path"])) {
        return [
            "url" => valon_asset_url($source_cover["path"]),
            "attachment" => 0,
            "kind" => "original",
            "width" => (int) $source_cover["width"],
            "height" => (int) $source_cover["height"],
        ];
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
            "url" => valon_asset_url("assets/" . $video_cover . ".jpeg"),
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
        : valon_asset_url($cover["path"]);
    return ["url" => $url, "attachment" => 0, "kind" => $cover["kind"]];
}

/** Reuse existing upload sizes only after proving they belong to this exact cover. */
function valon_uploaded_cover_sources($url)
{
    static $resolved = [];
    if (array_key_exists($url, $resolved)) {
        return $resolved[$url];
    }
    $resolved[$url] = false;
    $uploads = wp_get_upload_dir();
    $roots = array_unique([
        "https://www.valonasani.com/wp-content/uploads/",
        rtrim($uploads["baseurl"], "/") . "/",
    ]);
    $root = "";
    foreach ($roots as $candidate) {
        if (str_starts_with($url, $candidate)) {
            $root = $candidate;
            break;
        }
    }
    // Preserve animated GIFs: WordPress intermediate GIF sizes may be flattened.
    $path = wp_parse_url($url, PHP_URL_PATH) ?: "";
    if (!$root || preg_match('/\.gif$/i', $path)) {
        return false;
    }
    $relative = substr(strtok($url, "?#"), strlen($root));
    $original = preg_replace('/-\d+x\d+(?=\.[^.]+$)/', "", $relative);
    $candidates = array_unique([
        $relative,
        $original,
        preg_replace('/(?=\.[^.]+$)/', "-scaled", $original),
    ]);
    foreach ($candidates as $candidate) {
        // Map production URLs onto the local uploads origin when previewing a copy.
        $id = attachment_url_to_postid(rtrim($uploads["baseurl"], "/") . "/" . $candidate);
        $meta = $id ? wp_get_attachment_metadata($id) : false;
        if (!$meta || empty($meta["file"]) || empty($meta["width"]) || empty($meta["height"]) || get_post_mime_type($id) === "image/gif") {
            continue;
        }
        if (dirname($meta["file"]) !== dirname($relative)) {
            continue;
        }
        $sizes = array_merge([
            ["file" => basename($meta["file"]), "width" => $meta["width"], "height" => $meta["height"]],
        ], array_values($meta["sizes"] ?? []));
        foreach ($sizes as $size) {
            if (($size["file"] ?? "") !== basename($relative) || empty($size["width"]) || empty($size["height"])) {
                continue;
            }
            $width = (int) $size["width"];
            $height = (int) $size["height"];
            $srcset = wp_calculate_image_srcset([$width, $height], $url, $meta, $id);
            $sources = [];
            foreach (explode(", ", $srcset ?: "") as $source) {
                // Never promote a legacy 1024px cover to a larger, heavier original.
                if (preg_match('/ ([0-9]+)w$/', $source, $match) && (int) $match[1] <= $width) {
                    $sources[] = str_replace(rtrim($uploads["baseurl"], "/") . "/", $root, $source);
                }
            }
            return $resolved[$url] = [
                "width" => $width,
                "height" => $height,
                "srcset" => count($sources) > 1 ? implode(", ", $sources) : "",
            ];
        }
    }
    return false;
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
    $sizes = $card
        ? "(max-width: 780px) calc(100vw - 44px), (max-width: 1100px) calc((100vw - 120px) / 3), (max-width: 1360px) calc((100vw - 176px) / 3), 395px"
        : valon_article_cover_sizes();
    if (!$card) {
        $attrs["fetchpriority"] = "high";
        $attrs["sizes"] = $sizes;
    }
    if ($cover["attachment"]) {
        if (!$card) {
            $attachment = wp_get_attachment_image_src($cover["attachment"], "large");
            $attrs["sizes"] = valon_article_cover_sizes($attachment[1] ?? 0, $attachment[2] ?? 0);
        }
        $image = wp_get_attachment_image(
            $cover["attachment"],
            $card ? "valon-card" : "large",
            false,
            $attrs,
        );
        echo $card ? $image : valon_upload_picture($cover["url"], $image, $attrs["sizes"]);
    } else {
        $asset_url = get_template_directory_uri() . "/assets/";
        $asset_path = wp_parse_url($asset_url, PHP_URL_PATH);
        $cover_path = wp_parse_url($cover["url"], PHP_URL_PATH) ?: "";
        if (
            str_starts_with($cover["url"], $asset_url) &&
            str_ends_with($cover_path, ".jpeg")
        ) {
            $basename = substr($cover_path, strlen($asset_path), -5);
            $image = valon_static_image(
                $basename,
                $attrs,
                $sizes,
                !$card,
            );
            if ($image) {
                echo $image;
                return;
            }
        }
        if (!$card && ($sources = valon_uploaded_cover_sources($cover["url"]))) {
            $cover = array_merge($cover, $sources);
            $sizes = valon_article_cover_sizes($cover["width"], $cover["height"]);
        }
        $image = sprintf(
            '<img src="%s" alt="%s" class="%s" loading="%s" decoding="async" width="%d" height="%d"%s%s>',
            esc_url($cover["url"]),
            esc_attr($alt),
            esc_attr($attrs["class"]),
            esc_attr($attrs["loading"]),
            (int) ($cover["width"] ?? 1200),
            (int) ($cover["height"] ?? 750),
            $card ? "" : ' fetchpriority="high"',
            empty($cover["srcset"]) ? "" : sprintf(' srcset="%s" sizes="%s"', esc_attr($cover["srcset"]), esc_attr($sizes)),
        );
        echo $card ? $image : valon_upload_picture($cover["url"], $image, $sizes);
    }
}

// Yoast's URL filter runs only when its image collection contains an image.
// Seed empty collections for posts whose cover comes from the theme manifest.
add_action("wpseo_add_opengraph_additional_images", function ($images) {
    if (!is_singular("post") || $images->has_images()) {
        return;
    }
    $id = get_queried_object_id();
    $explicit = get_post_meta($id, "_yoast_wpseo_opengraph-image", true);
    $images->add_image_by_url($explicit ?: valon_article_image($id)["url"]);
});

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
