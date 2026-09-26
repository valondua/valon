<?php
defined("ABSPATH") || exit();

/** Give bundled assets a new browser-cache URL whenever their file changes. */
function valon_asset_url($path)
{
    $path = ltrim($path, "/");
    $file = get_template_directory() . "/" . $path;
    $url = get_template_directory_uri() . "/" . $path;
    return is_file($file)
        ? add_query_arg("ver", (string) filemtime($file), $url)
        : $url;
}

/** Match the article's 900px column and 640px-high, contained image. */
function valon_article_cover_sizes($width = 0, $height = 0)
{
    $maximum = $width > 0 && $height > 0
        ? min(900, (int) floor(640 * $width / $height))
        : 900;
    $mobile = "calc(100vw - 44px)";
    $tablet = "calc(100vw - 64px)";
    if ($maximum < 900) {
        $mobile = "min($mobile, {$maximum}px)";
        $tablet = "min($tablet, {$maximum}px)";
    }
    return "(max-width: 780px) $mobile, (max-width: 964px) $tablet, {$maximum}px";
}

/** Exact reviewed remote originals only; native-size sources retain intrinsic IMG layout. */
function valon_remote_picture($url, $fallback_html)
{
    if (!is_string($url) || strpbrk($url, "?#") !== false) {
        return $fallback_html;
    }
    static $manifests = [];
    $directory = get_template_directory();
    if (!isset($manifests[$directory])) {
        $file = $directory . "/assets/optimized-remote-images.json";
        $manifests[$directory] = is_readable($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    }
    $image = $manifests[$directory][$url] ?? null;
    if (!is_array($image) || !is_string($image["basename"] ?? null) ||
        !preg_match('/^valon-remote-[a-f0-9]{12}$/D', $image["basename"]) ||
        !is_string($image["sha256"] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $image["sha256"]) ||
        $image["basename"] !== "valon-remote-" . substr($image["sha256"], 0, 12) ||
        !is_int($image["width"] ?? null) || $image["width"] < 1 ||
        !is_int($image["height"] ?? null) || $image["height"] < 1) {
        return $fallback_html;
    }
    $tag = new WP_HTML_Tag_Processor($fallback_html);
    if (!$tag->next_tag() || $tag->get_tag() !== "IMG" || $tag->get_attribute("src") !== $url) {
        return $fallback_html;
    }
    $dimensions = "";
    $width = $tag->get_attribute("width");
    $height = $tag->get_attribute("height");
    if (is_string($width) && is_string($height) && ctype_digit($width) && ctype_digit($height) &&
        (int) $width > 0 && (int) $height > 0) {
        $dimensions = sprintf(' width="%d" height="%d"', (int) $width, (int) $height);
    }
    if ($tag->next_tag()) {
        return $fallback_html;
    }
    $sources = "";
    foreach (["avif" => "image/avif", "webp" => "image/webp"] as $extension => $type) {
        $path = "assets/" . $image["basename"] . ".$extension";
        if (is_file($directory . "/" . $path)) {
            // No width descriptor: an IMG without dimensions keeps its original natural size.
            $sources .= sprintf('<source type="%s" srcset="%s"%s>',
                esc_attr($type), esc_attr(valon_asset_url($path)), $dimensions);
        }
    }
    return $sources ? "<picture>" . $sources . $fallback_html . "</picture>" : $fallback_html;
}

/** Add modern sources for reviewed originals, retaining the original HTML. */
function valon_upload_picture($url, $fallback_html, $sizes)
{
    $remote = valon_remote_picture($url, $fallback_html);
    if ($remote !== $fallback_html) {
        return $remote;
    }
    if (function_exists("valon_animated_picture")) {
        $animated = valon_animated_picture($url, $fallback_html, $sizes);
        if ($animated !== $fallback_html) {
            return $animated;
        }
    }
    if (!is_string($url) || strpbrk($url, "?#") !== false) {
        return $fallback_html;
    }
    $uploads = wp_get_upload_dir();
    $relative = null;
    foreach ([
        ["https://www.valonasani.com/wp-content/uploads/", ""],
        [rtrim($uploads["baseurl"], "/") . "/", ""],
        ["https://www.valonasani.com/wp-content/themes/valon/", "theme:"],
        [rtrim(get_template_directory_uri(), "/") . "/", "theme:"],
    ] as [$root, $prefix]) {
        if (str_starts_with($url, $root)) {
            $relative = $prefix . substr($url, strlen($root));
            break;
        }
    }
    if ($relative === null || preg_match('/\.gif$/i', $relative)) {
        return $fallback_html;
    }
    static $manifest;
    if ($manifest === null) {
        $file = get_template_directory() . "/assets/optimized-uploads.json";
        $manifest = is_readable($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    }
    $image = $manifest[$relative] ?? null;
    if (
        !is_array($image) || !is_string($image["basename"] ?? null) ||
        !preg_match('/^[a-z0-9][a-z0-9-]*$/D', $image["basename"]) ||
        empty($image["width"]) || empty($image["height"]) ||
        empty($image["widths"]) || !is_array($image["widths"])
    ) {
        return $fallback_html;
    }
    // Only wrap an actual matching IMG, never an existing picture or another image.
    $tag = new WP_HTML_Tag_Processor($fallback_html);
    if (!$tag->next_tag() || $tag->get_tag() !== "IMG" || $tag->get_attribute("src") !== $url) {
        return $fallback_html;
    }
    // Keep the IMG's declared box, including body illustrations narrower than the prose.
    $dimensions = "";
    $width_attribute = $tag->get_attribute("width");
    $height_attribute = $tag->get_attribute("height");
    if (
        is_string($width_attribute) && is_string($height_attribute) &&
        ctype_digit($width_attribute) && ctype_digit($height_attribute) &&
        (int) $width_attribute > 0 && (int) $height_attribute > 0
    ) {
        $dimensions = sprintf(' width="%d" height="%d"', (int) $width_attribute, (int) $height_attribute);
    }
    $previous = 0;
    foreach ($image["widths"] as $width) {
        if (!is_int($width) || $width <= $previous || $width > (int) $image["width"]) {
            return $fallback_html;
        }
        $previous = $width;
    }
    $sources_html = "";
    foreach (["avif" => "image/avif", "webp" => "image/webp"] as $extension => $type) {
        $sources = [];
        foreach ($image["widths"] as $width) {
            $path = "assets/" . $image["basename"] . "-$width.$extension";
            if (!is_file(get_template_directory() . "/" . $path)) {
                $sources = [];
                break;
            }
            $sources[] = valon_asset_url($path) . " {$width}w";
        }
        if ($sources) {
            $sources_html .= sprintf(
                '<source type="%s" srcset="%s" sizes="%s"%s>',
                esc_attr($type),
                esc_attr(implode(", ", $sources)),
                esc_attr($sizes ?: "100vw"),
                $dimensions,
            );
        }
    }
    return $sources_html ? "<picture>" . $sources_html . $fallback_html . "</picture>" : $fallback_html;
}

/** Render optimized sources for bundled photos, with a JPEG fallback. */
function valon_static_image($basename, $attributes = [], $sizes = "100vw", $preserve_aspect_ratio = false)
{
    $images = [
        "valon-candid" => [360, 540, [360]],
        "valon-self-respect" => [1024, 767, [480, 768, 1024]],
        "valon-dua" => [1024, 766, [480, 768, 1024]],
        "valon-portrait" => [879, 894, [480, 768, 879]],
        "valon-travel" => [768, 1024, [480, 768]],
        "valon-reading" => [792, 990, [480, 768, 792]],
        "valon-friends" => [1024, 768, [480, 768, 1024]],
    ];
    if (!isset($images[$basename])) {
        return "";
    }
    [$width, $height, $widths] = $images[$basename];
    if ($preserve_aspect_ratio) {
        $sizes = valon_article_cover_sizes($width, $height);
    }
    $asset_dir = get_template_directory() . "/assets/";
    $html = "<picture>";
    foreach (["avif" => "image/avif", "webp" => "image/webp"] as $extension => $type) {
        $sources = [];
        foreach ($widths as $source_width) {
            $filename = $basename . "-" . $source_width . "." . $extension;
            // A partial asset update must still display the original photograph.
            if (!is_file($asset_dir . $filename)) {
                $sources = [];
                break;
            }
            $sources[] = valon_asset_url("assets/" . $filename) . " " . $source_width . "w";
        }
        if ($sources) {
            $html .= sprintf(
                '<source type="%s" srcset="%s" sizes="%s">',
                esc_attr($type),
                esc_attr(implode(", ", $sources)),
                esc_attr($sizes),
            );
        }
    }
    $attributes = array_merge(
        ["alt" => "", "class" => "", "loading" => "lazy", "decoding" => "async"],
        $attributes,
    );
    $html .= sprintf(
        '<img src="%s" width="%d" height="%d"',
        esc_url(valon_asset_url("assets/" . $basename . ".jpeg")),
        $width,
        $height,
    );
    if ($preserve_aspect_ratio) {
        // Resized candidates round pixel heights; retain the original cover box.
        $html .= sprintf(' style="aspect-ratio: %d / %d"', $width, $height);
    }
    foreach (["alt", "class", "loading", "decoding", "fetchpriority"] as $attribute) {
        if (isset($attributes[$attribute])) {
            $html .= sprintf(' %s="%s"', $attribute, esc_attr($attributes[$attribute]));
        }
    }
    return $html . "></picture>";
}
