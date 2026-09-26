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

/** Render optimized sources for a small set of bundled photos, with a JPEG fallback. */
function valon_static_image($basename, $attributes = [], $sizes = "100vw", $preserve_aspect_ratio = false)
{
    $images = [
        "valon-candid" => [360, 540, [360]],
        "valon-self-respect" => [1024, 767, [480, 768, 1024]],
        "valon-dua" => [1024, 766, [480, 768, 1024]],
        "valon-portrait" => [879, 894, [480, 768, 879]],
    ];
    if (!isset($images[$basename])) {
        return "";
    }
    [$width, $height, $widths] = $images[$basename];
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
