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
