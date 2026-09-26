<?php
defined("ABSPATH") || exit();

/** Add a reviewed animated WebP while retaining the original GIF markup verbatim. */
function valon_animated_picture($url, $fallback_html, $sizes)
{
    if (!is_string($url) || !is_string($fallback_html)) {
        return $fallback_html;
    }
    // Keys are complete original URLs, including their query strings. No remote lookup.
    static $manifests = [];
    $directory = get_template_directory();
    $manifest_file = $directory . "/assets/optimized-animations.json";
    if (!array_key_exists($manifest_file, $manifests)) {
        $decoded = is_readable($manifest_file)
            ? json_decode(file_get_contents($manifest_file), true)
            : null;
        $manifests[$manifest_file] = is_array($decoded) ? $decoded : [];
    }
    $image = $manifests[$manifest_file][$url] ?? null;
    if (
        !is_array($image) || !is_string($image["file"] ?? null) ||
        !preg_match('/^valon-animation-[a-f0-9]{16}\.webp$/D', $image["file"]) ||
        !is_int($image["width"] ?? null) || $image["width"] < 1 ||
        !is_int($image["height"] ?? null) || $image["height"] < 1 ||
        !is_int($image["frames"] ?? null) || $image["frames"] < 2 ||
        !is_file($directory . "/assets/" . $image["file"])
    ) {
        return $fallback_html;
    }
    $tag = new WP_HTML_Tag_Processor($fallback_html);
    if (!$tag->next_tag() || $tag->get_tag() !== "IMG" || $tag->get_attribute("src") !== $url) {
        return $fallback_html;
    }
    $dimensions = "";
    $width = $tag->get_attribute("width");
    $height = $tag->get_attribute("height");
    if (
        is_string($width) && is_string($height) && ctype_digit($width) && ctype_digit($height) &&
        (int) $width > 0 && (int) $height > 0
    ) {
        $dimensions = sprintf(' width="%d" height="%d"', (int) $width, (int) $height);
    }
    if ($tag->next_tag()) {
        return $fallback_html;
    }
    // A single native-resolution source keeps intrinsic sizing identical to the GIF.
    // The shared helper signature accepts sizes, but no responsive width hint is needed.
    return sprintf(
        '<picture><source type="image/webp" srcset="%s"%s>%s</picture>',
        esc_attr(valon_asset_url("assets/" . $image["file"])),
        $dimensions,
        $fallback_html,
    );
}
