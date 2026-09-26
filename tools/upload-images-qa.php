<?php
/** Read-only WordPress integration checks; temporary asset fixtures only, no database writes. */
if (PHP_SAPI !== "cli" || !defined("ABSPATH")) {
    exit("Load WordPress first: wp eval-file tools/upload-images-qa.php\n");
}
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
};
$source_types = static function ($html) {
    $tag = new WP_HTML_Tag_Processor($html);
    $types = [];
    while ($tag->next_tag("SOURCE")) { $types[] = $tag->get_attribute("type"); }
    return $types;
};
$image_tag = static function ($html) {
    $tag = new WP_HTML_Tag_Processor($html);
    if (!$tag->next_tag("IMG")) { throw new RuntimeException("Expected an image"); }
    return $tag;
};
$root = "https://www.valonasani.com/wp-content/uploads/";
$url = $root . "2025/06/image-1.png";
$companion = $root . "2025/06/image.png";
$sizes = valon_article_cover_sizes(1024, 1024);
$body_sizes = "(max-width: 780px) min(calc(100vw - 44px), 720px), min(calc(100vw - 64px), 720px)";
$uploads_root = rtrim(wp_get_upload_dir()["baseurl"], "/") . "/";
$theme = get_template_directory();
$manifest = json_decode(file_get_contents($theme . "/assets/optimized-uploads.json"), true);
$original_query = $GLOBALS["wp_query"];
$original_main = $GLOBALS["wp_the_query"];
$original_post = $GLOBALS["post"] ?? null;
$fixture = sys_get_temp_dir() . "/valon-upload-qa-" . bin2hex(random_bytes(6));
$directory_filter = static fn() => $fixture;
$fixture_files = [];

try {
    $query = new WP_Query(["post_type" => "post", "name" => "building-in-public-get-ready-for-the-energy-vampires", "posts_per_page" => 1]);
    $check($query->have_posts(), "Representative article exists");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    $post_id = get_the_ID();
    $original_content = get_post_field("post_content", $post_id);
    $check(valon_article_image($post_id)["url"] === $url, "Editorial and SEO image identity retain the original upload URL");

    $fallback = str_replace($uploads_root, $root, wp_get_attachment_image(2332, "full", false, [
        "class" => "article-cover", "alt" => "Original & reviewed", "loading" => "eager",
        "fetchpriority" => "high", "sizes" => $sizes,
    ]));
    $check($fallback !== "" && $image_tag($fallback)->get_attribute("src") === $url, "Real WordPress attachment HTML uses the reviewed original");
    $picture = valon_upload_picture($url, $fallback, $sizes);
    $check($source_types($picture) === ["image/avif", "image/webp"] && str_ends_with($picture, $fallback . "</picture>"),
        "Modern sources wrap the complete original WordPress IMG byte for byte");
    $sources = new WP_HTML_Tag_Processor($picture);
    while ($sources->next_tag("SOURCE")) {
        preg_match_all('/ ([0-9]+)w/', $sources->get_attribute("srcset"), $widths);
        $check($widths[1] === ["480", "768", "1024"] && $sources->get_attribute("sizes") === $sizes,
            "Cover source widths and 640px painted-width hint are correct");
    }
    $local_url = $uploads_root . "2025/06/image-1.png";
    $local_fallback = str_replace($root, $uploads_root, $fallback);
    $check($source_types(valon_upload_picture($local_url, $local_fallback, $sizes)) === ["image/avif", "image/webp"],
        "The current WordPress uploads origin resolves the same exact allowlist key");
    $companion_html = '<img src="' . $companion . '" width="736" height="736" loading="lazy">';
    $check($source_types(valon_upload_picture($companion, $companion_html, $body_sizes)) === ["image/avif", "image/webp"],
        "The second reviewed upload receives its own source set");

    foreach ([
        "https://cdn.example.test/wp-content/uploads/2025/06/image-1.png",
        "https://www.valonasani.com.evil.test/wp-content/uploads/2025/06/image-1.png",
        $root . "2024/06/image-1.png", $root . "2025/06/image-1-768x768.png",
        $root . "2025/06/image-1-e1234567890123.png", $root . "2021/08/try-me.gif",
        $url . "?ver=123", $url . "#preview",
    ] as $other_url) {
        $other = '<img src="' . esc_url($other_url) . '" alt="Untouched">';
        $check(valon_upload_picture($other_url, $other, $sizes) === $other,
            "Unknown origins, basename collisions, crops, edits, GIFs and URL suffixes stay unchanged");
    }
    $check(valon_upload_picture($url, $companion_html, $sizes) === $companion_html,
        "An allowlisted URL cannot substitute sources for a different fallback IMG");
    $check(valon_upload_picture($url, $picture, $sizes) === $picture, "Existing picture input is not nested");

    ob_start(); valon_render_article_image($post_id); $cover = ob_get_clean();
    $cover_tag = $image_tag($cover);
    $check($source_types($cover) === ["image/avif", "image/webp"] &&
        $cover_tag->get_attribute("src") === $url && $cover_tag->get_attribute("srcset") !== null &&
        $cover_tag->get_attribute("fetchpriority") === "high" && $cover_tag->get_attribute("loading") === "eager",
        "Template cover retains its responsive PNG fallback and high priority");
    ob_start(); valon_render_article_image($post_id, true); $card = ob_get_clean();
    $check(!str_contains($card, "<picture>") && $image_tag($card)->get_attribute("loading") === "lazy",
        "Card rendering remains unchanged");

    $body = str_replace('class="article-cover"', 'class="wp-image-2332"', $fallback);
    $loaded = valon_article_body_image_loading($body, "the_content");
    $content = "<figure>\n" . $loaded . "\n<figcaption>Keep this caption.</figcaption></figure>";
    $wrapped = valon_article_body_pictures($content);
    $check($source_types($wrapped) === ["image/avif", "image/webp"] && str_contains($wrapped, $loaded),
        "Body wrapping preserves all original IMG attributes, responsive fallback and surrounding content");
    $source = new WP_HTML_Tag_Processor($wrapped); $source->next_tag("SOURCE");
    $check($source->get_attribute("sizes") === $body_sizes && $image_tag($wrapped)->get_attribute("sizes") === $image_tag($loaded)->get_attribute("sizes"),
        "Only modern body sources use the measured 720px content width");
    $once = valon_article_body_pictures(wp_filter_content_tags($content, "the_content"));
    $twice = valon_article_body_pictures(wp_filter_content_tags($once, "the_content"));
    $check($once === $twice && valon_article_body_pictures($wrapped) === $wrapped,
        "Repeated WordPress image filtering and the full-content wrapping pass are idempotent");
    $existing = '<picture data-note="A > B"><source type="image/webp" srcset="existing.webp">' . $loaded . '</picture>';
    $check(valon_article_body_pictures($existing) === $existing, "Existing pictures with quoted greater-than attributes remain byte-identical");
    $quoted = '<img alt=\'A > B &amp; C\' data-note="x > y" src="' . $url . '" width="1024" height="1024" />';
    $quoted_result = valon_article_body_pictures($quoted);
    $check($source_types($quoted_result) === ["image/avif", "image/webp"] && str_contains($quoted_result, $quoted),
        "Quoted greater-than attributes and self-closing syntax survive exactly");
    $inert = '<!-- <picture>' . $quoted . '</picture> --><script>const example = ' . json_encode($quoted) . ';</script>';
    $check(valon_article_body_pictures($inert) === $inert, "Image-like text in comments and scripts is untouched");
    $mixed = $inert . $existing . '<p>Before</p>' . $quoted . '<p>After</p>';
    $mixed_result = valon_article_body_pictures($mixed);
    $check(str_starts_with($mixed_result, $inert . $existing . '<p>Before</p>') &&
        str_contains($mixed_result, $quoted) && str_ends_with($mixed_result, '<p>After</p>') &&
        !str_contains($mixed_result, "data-valon-upload-token"),
        "Context-aware wrapping preserves surrounding bytes and removes every temporary marker");
    $query->in_the_loop = false;
    $check(valon_article_body_pictures($content) === $content, "Outside-loop content is not wrapped");
    $query->in_the_loop = true;

    mkdir($fixture . "/assets", 0700, true);
    foreach ($manifest["2025/06/image-1.png"]["widths"] as $width) {
        $file = $fixture . "/assets/valon-energy-boundaries-$width.webp";
        file_put_contents($file, "temporary source-presence fixture");
        $fixture_files[] = $file;
    }
    add_filter("template_directory", $directory_filter);
    $partial = valon_upload_picture($url, $fallback, $sizes);
    $check($source_types($partial) === ["image/webp"] && str_ends_with($partial, $fallback . "</picture>"),
        "An incomplete AVIF deployment keeps the complete WebP set and original fallback");
    unlink($fixture_files[0]); clearstatcache();
    $check(valon_upload_picture($url, $fallback, $sizes) === $fallback,
        "Incomplete source sets in both formats return the exact original HTML");
    foreach ($fixture_files as $file) { if (is_file($file)) { unlink($file); } }
    clearstatcache();
    $check(valon_upload_picture($url, $fallback, $sizes) === $fallback && valon_article_body_pictures($content) === $content,
        "Missing all modern assets preserves cover and body PNG fallback without a wrapper");
    $check(get_post_field("post_content", $post_id) === $original_content && valon_article_image($post_id)["url"] === $url,
        "Stored article content and editorial/SEO image URLs are unchanged");
    echo "Upload picture integration checks passed.\n";
} finally {
    remove_filter("template_directory", $directory_filter);
    $GLOBALS["wp_query"] = $original_query;
    $GLOBALS["wp_the_query"] = $original_main;
    $GLOBALS["post"] = $original_post;
    foreach ($fixture_files as $file) { if (is_file($file)) { unlink($file); } }
    if (is_dir($fixture . "/assets")) { rmdir($fixture . "/assets"); }
    if (is_dir($fixture)) { rmdir($fixture); }
}
