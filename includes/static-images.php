<?php
defined("ABSPATH") || exit();

/** Render optimized sources for a small set of bundled photos, with a JPEG fallback. */
function valon_static_image($basename, $attributes = [], $sizes = "100vw")
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
    $asset_url = get_template_directory_uri() . "/assets/";
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
            $sources[] = $asset_url . $filename . " " . $source_width . "w";
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
        esc_url($asset_url . $basename . ".jpeg"),
        $width,
        $height,
    );
    foreach (["alt", "class", "loading", "decoding", "fetchpriority"] as $attribute) {
        if (isset($attributes[$attribute])) {
            $html .= sprintf(' %s="%s"', $attribute, esc_attr($attributes[$attribute]));
        }
    }
    return $html . "></picture>";
}
