<?php
/** Read-only integration checks: wp eval-file tools/animated-images-qa.php */
if (PHP_SAPI !== "cli" || !defined("ABSPATH")) {
    exit("Load WordPress first: wp eval-file tools/animated-images-qa.php\n");
}
require_once get_template_directory() . "/includes/animated-images.php";
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
};
$manifest = json_decode(file_get_contents(get_template_directory() . "/assets/optimized-animations.json"), true);
$check(is_array($manifest) && count($manifest) > 0, "Reviewed animation manifest is populated");
$sizes = "(max-width: 780px) calc(100vw - 44px), 720px";
foreach ($manifest as $url => $asset) {
    $fallback = '<img src="' . esc_attr($url) . '" width="391" height="220" alt="Keep &amp; preserve" class="custom-gif" loading="lazy" decoding="async" data-original="yes">';
    $picture = valon_animated_picture($url, $fallback, $sizes);
    $check(str_ends_with($picture, $fallback . "</picture>"), "Original IMG is byte-identical for " . $asset["file"]);
    $source = new WP_HTML_Tag_Processor($picture);
    $check($source->next_tag("SOURCE") && $source->get_attribute("type") === "image/webp" &&
        $source->get_attribute("srcset") === valon_asset_url("assets/" . $asset["file"]) &&
        $source->get_attribute("width") === "391" && $source->get_attribute("height") === "220",
        "Single native-resolution WebP preserves the declared source box");
    $check(!$source->next_tag("SOURCE"), "Only one animated source is emitted");
    $check(hash_file("sha256", get_template_directory() . "/assets/" . $asset["file"]) === $asset["sha256"],
        "Bundled file matches validated output hash");
    $check(valon_animated_picture($url, $picture, $sizes) === $picture, "Existing picture is not nested");
    $check(valon_animated_picture($url, $fallback . $fallback, $sizes) === $fallback . $fallback,
        "Multiple IMG input is left unchanged");
    $other = '<img src="https://example.test/other.gif" loading="lazy">';
    $check(valon_animated_picture($url, $other, $sizes) === $other, "Mismatched fallback cannot receive the selected source");
    foreach ([$url . "#fragment", $url . "&extra=1", str_replace("https://", "http://", $url)] as $unknown) {
        $html = '<img src="' . esc_attr($unknown) . '">';
        $check(valon_animated_picture($unknown, $html, $sizes) === $html, "Nonidentical URL retains original GIF");
    }
    $undeclared = '<img src="' . esc_attr($url) . '" alt="Native size">';
    $native = new WP_HTML_Tag_Processor(valon_animated_picture($url, $undeclared, $sizes));
    $check($native->next_tag("SOURCE") && $native->get_attribute("width") === null && $native->get_attribute("height") === null,
        "Undeclared dimensions retain the native image size");
}

// Preview content may use localhost URLs; exercise the exact public URL through real filters.
$original_query = $GLOBALS["wp_query"];
$original_main_query = $GLOBALS["wp_the_query"];
$original_post = $GLOBALS["post"] ?? null;
try {
    $query = new WP_Query(["post_type" => "post", "name" => "try-me", "posts_per_page" => 1]);
    $check($query->have_posts(), "Animated cover article exists");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    $url = "https://www.valonasani.com/wp-content/uploads/2021/08/try-me.gif";
    $body = '<p><img src="' . $url . '" width="480" height="254" alt="Original animation"></p>';
    $filtered = apply_filters("the_content", $body);
    $check(substr_count($filtered, '<source type="image/webp"') === 1 && str_contains($filtered, 'loading="lazy"'),
        "Real WordPress content filters optimize the exact public GIF and retain lazy loading");
    $check(substr_count(apply_filters("the_content", $filtered), '<picture>') === 1,
        "Repeated whole-content filtering does not nest pictures");
    $existing = '<picture><source type="image/gif" srcset="' . $url . '"><img src="' . $url . '" alt="Existing picture"></picture>';
    $check(substr_count(apply_filters("the_content", $existing), '<picture>') === 1,
        "Existing picture ancestors stay intact through WordPress filters");
} finally {
    $GLOBALS["wp_query"] = $original_query;
    $GLOBALS["wp_the_query"] = $original_main_query;
    $GLOBALS["post"] = $original_post;
    wp_reset_postdata();
}

// Missing files must fall back without changing any deployed asset.
$url = array_key_first($manifest);
$fixture = sys_get_temp_dir() . "/valon-animation-qa-" . bin2hex(random_bytes(6));
$directory_filter = static fn() => $fixture;
mkdir($fixture . "/assets", 0777, true);
file_put_contents($fixture . "/assets/optimized-animations.json", json_encode([$url => $manifest[$url]]));
try {
    add_filter("template_directory", $directory_filter);
    $fallback = '<img src="' . esc_attr($url) . '" loading="eager" fetchpriority="high">';
    $check(valon_animated_picture($url, $fallback, $sizes) === $fallback, "Missing modern asset retains exact eager/high-priority fallback");
} finally {
    remove_filter("template_directory", $directory_filter);
    unlink($fixture . "/assets/optimized-animations.json");
    rmdir($fixture . "/assets");
    rmdir($fixture);
}
echo "Animated image QA complete.\n";
