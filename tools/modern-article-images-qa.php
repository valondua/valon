<?php
/** Read-only real-WordPress checks for the complete reviewed first-party image registry. */
if (PHP_SAPI !== "cli" || !defined("ABSPATH")) {
    exit("Load WordPress first: wp eval-file tools/modern-article-images-qa.php\n");
}
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
};
$manifest = json_decode(file_get_contents(get_template_directory() . "/assets/optimized-uploads.json"), true);
$seen = [];
foreach ($manifest as $key => $image) {
    $is_theme = str_starts_with($key, "theme:");
    $relative = $is_theme ? substr($key, 6) : $key;
    $production = "https://www.valonasani.com/wp-content/" . ($is_theme ? "themes/valon/" : "uploads/") . $relative;
    $current = rtrim($is_theme ? get_template_directory_uri() : wp_get_upload_dir()["baseurl"], "/") . "/" . $relative;
    $check(!isset($seen[$image["basename"]]), "Output basename is unique: $key");
    $seen[$image["basename"]] = true;
    foreach (array_unique([$production, $current]) as $url) {
        $fallback = '<img src="' . esc_url($url) . '" width="360" height="640" alt="Preserve &amp; review" loading="lazy" decoding="async" data-original="yes">';
        $html = valon_upload_picture($url, $fallback, "(max-width: 780px) min(calc(100vw - 44px), 360px), 360px");
        $check(str_ends_with($html, $fallback . "</picture>"), "Original IMG survives byte for byte: $key");
        $tag = new WP_HTML_Tag_Processor($html); $types = [];
        while ($tag->next_tag("SOURCE")) {
            $types[] = $tag->get_attribute("type");
            preg_match_all('/ ([0-9]+)w/', $tag->get_attribute("srcset"), $widths);
            $check(array_map("intval", $widths[1]) === $image["widths"], "Every reviewed candidate width is emitted: $key");
            $check($tag->get_attribute("width") === "360" && $tag->get_attribute("height") === "640", "SOURCE retains the declared IMG box, not larger original dimensions: $key");
        }
        $check($types === ["image/avif", "image/webp"], "Both modern source sets are complete: $key");
    }
}
$theme_path = "assets/video-covers/7597539248341847317.jpg";
$theme_url = "https://www.valonasani.com/wp-content/themes/valon/" . $theme_path;
foreach ([
    str_replace("www.valonasani.com", "www.valonasani.com.evil.test", $theme_url),
    str_replace("/valon/", "/other/", $theme_url),
    str_replace("/themes/valon/", "/uploads/", $theme_url),
    str_replace(".jpg", "-360x640.jpg", $theme_url),
    $theme_url . "?ver=123", $theme_url . "#preview",
    "https://www.valonasani.com/wp-content/uploads/2021/08/unlisted-animation.gif",
] as $url) {
    $fallback = '<img src="' . esc_url($url) . '">';
    $check(valon_upload_picture($url, $fallback, "100vw") === $fallback, "Unknown root, namespace, crop, suffix or animation stays unchanged");
}
$without_dimensions = '<img src="' . $theme_url . '" alt="Unchanged">';
$html = valon_upload_picture($theme_url, $without_dimensions, "360px");
$tag = new WP_HTML_Tag_Processor($html);
while ($tag->next_tag("SOURCE")) {
    $check($tag->get_attribute("width") === null && $tag->get_attribute("height") === null, "No image dimensions are invented when the IMG has none");
}
echo "Modern article registry checks passed: " . count($manifest) . " exact sources.\n";
