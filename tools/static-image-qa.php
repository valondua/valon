<?php
/** Standalone helper regression checks: php tools/static-image-qa.php */
if (PHP_SAPI !== "cli" || defined("ABSPATH")) {
    exit("Run this standalone from the command line.\n");
}
define("ABSPATH", __DIR__ . "/../");
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});
$fixture = sys_get_temp_dir() . "/valon-image-qa-" . bin2hex(random_bytes(6));
$actions = [];
$filters = [];
$front_page = true;
$post_meta = [];
$post_slugs = [];
$source_posts = [];
$thumbnail_ids = [];
$upload_ids = [];
$upload_meta = [];
$upload_lookups = [];
$upload_srcsets = [];
function get_template_directory() { return $GLOBALS["fixture"]; }
function get_template_directory_uri() { return "https://example.test/wp-content/themes/valon"; }
function add_query_arg($key, $value, $url) { return $url . "?" . http_build_query([$key => $value]); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, "UTF-8"); }
function esc_url($value) { return esc_attr($value); }
function esc_url_raw($value) { return $value; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function add_action($hook, $callback, ...$args) { $GLOBALS["actions"][$hook][] = $callback; }
function add_filter($hook, $callback, ...$args) { $GLOBALS["filters"][$hook][] = $callback; }
function is_front_page() { return $GLOBALS["front_page"]; }
function get_theme_mod($name) { return false; }
function get_post_meta($id, $key, $single) { return $GLOBALS["post_meta"][$id][$key] ?? ""; }
function get_post_thumbnail_id($id) { return $GLOBALS["thumbnail_ids"][$id] ?? 0; }
function get_post_type($id) { return "post"; }
function get_post_field($field, $id) { return $GLOBALS["post_slugs"][$id] ?? "card-fixture"; }
function pll_get_post($id, $language) { return $GLOBALS["source_posts"][$id] ?? 0; }
function wp_get_upload_dir() { return ["baseurl" => "https://example.test/wp-content/uploads"]; }
function attachment_url_to_postid($url) {
    $GLOBALS["upload_lookups"][] = $url;
    return $GLOBALS["upload_ids"][$url] ?? 0;
}
function wp_get_attachment_metadata($id) { return $GLOBALS["upload_meta"][$id] ?? false; }
function get_post_mime_type($id) { return $GLOBALS["upload_meta"][$id]["mime"] ?? "image/png"; }
function wp_calculate_image_srcset($size, $url, $meta, $id) { return $GLOBALS["upload_srcsets"][$id] ?? false; }
function wp_get_attachment_image_url($id, $size) { return "https://example.test/wp-content/uploads/featured-$id.png"; }
function wp_get_attachment_image_src($id, $size) {
    $meta = wp_get_attachment_metadata($id);
    return [wp_get_attachment_image_url($id, $size), $meta["width"], $meta["height"]];
}
function wp_get_attachment_image($id, $size, $icon, $attrs) {
    $html = '<img src="' . wp_get_attachment_image_url($id, $size) . '"';
    foreach ($attrs as $key => $value) { $html .= ' ' . $key . '="' . esc_attr($value) . '"'; }
    return $html . '>';
}
function get_the_title($id) { return "Fixture article"; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS " . $message . "\n";
}
function document($html) {
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    return $doc;
}
function check_srcset($srcset, $widths) {
    $candidates = explode(", ", $srcset);
    check(count($candidates) === count($widths), "Responsive source count is preserved");
    foreach ($candidates as $index => $candidate) {
        check(
            preg_match('~^https://example\.test/\S+\?ver=([0-9]+) ([0-9]+)w$~', $candidate, $matches) === 1 &&
                (int) $matches[2] === $widths[$index],
            "Responsive candidate has a versioned URL and valid width descriptor",
        );
    }
}

mkdir($fixture . "/assets", 0700, true);
try {
    $files = ["valon-portrait.jpeg", "valon-hero.jpeg", "video-cover.jpg"];
    $additional_photos = [
        "valon-travel" => [768, 1024, [480, 768]],
        "valon-reading" => [792, 990, [480, 768, 792]],
        "valon-friends" => [1024, 768, [480, 768, 1024]],
    ];
    foreach ($additional_photos as $name => [$width, $height, $widths]) {
        $files[] = "$name.jpeg";
        foreach (["avif", "webp"] as $extension) {
            foreach ($widths as $candidate_width) { $files[] = "$name-$candidate_width.$extension"; }
        }
    }
    foreach (["avif", "webp"] as $extension) {
        foreach ([480, 768, 879] as $width) {
            $files[] = "valon-portrait-$width.$extension";
        }
        foreach ([480, 768, 1024, 1586] as $width) {
            $files[] = "valon-hero-$width.$extension";
        }
    }
    foreach ($files as $file) {
        file_put_contents($fixture . "/assets/" . $file, "fixture");
        touch($fixture . "/assets/" . $file, 1700000000);
    }
    file_put_contents($fixture . "/assets/article-covers.json", json_encode([
        "card-fixture" => ["path" => "assets/valon-portrait.jpeg", "kind" => "portrait"],
        "uploaded-fixture" => ["path" => "https://www.valonasani.com/wp-content/uploads/2025/03/cover-1024x576.png", "kind" => "original"],
    ]));
    file_put_contents($fixture . "/assets/video-covers.json", json_encode([
        "video-fixture" => ["path" => "assets/video-cover.jpg", "width" => 640, "height" => 360],
    ]));
    require __DIR__ . "/../functions.php";

    $related_style = $filters["yarpp_enqueue_related_style"][0];
    check($related_style(true) === false, "Homepage does not request unused related-post styles");
    $front_page = false;
    check($related_style(true) === true && $related_style(false) === false, "Other pages preserve the related-post stylesheet decision");
    $front_page = true;

    $portrait_url = valon_asset_url("assets/valon-portrait.jpeg");
    check(str_ends_with($portrait_url, "?ver=1700000000"), "Bundled image URL uses its file timestamp");
    check(valon_asset_url("/assets/valon-portrait.jpeg") === $portrait_url, "Leading slash resolves to the same bundled file");
    check(!str_contains(valon_asset_url("assets/missing.jpeg"), "?"), "Missing file does not invent a version or emit a filesystem warning");
    touch($fixture . "/assets/valon-portrait.jpeg", 1700000060);
    clearstatcache();
    check(valon_asset_url("assets/valon-portrait.jpeg") !== $portrait_url, "Changed file timestamp produces a fresh browser-cache URL");

    $hero = valon_home_portrait_sources();
    check_srcset($hero["srcset"], [480, 768, 1024, 1586]);
    check_srcset($hero["avif_srcset"], [480, 768, 1024, 1586]);
    check(str_ends_with($hero["src"], "valon-hero.jpeg?ver=1700000000"), "Hero JPEG fallback is versioned");
    ob_start();
    foreach ($actions["wp_head"] as $callback) { $callback(); }
    $preload = document(ob_get_clean())->getElementsByTagName("link")->item(0);
    check(
        $preload->getAttribute("imagesrcset") === $hero["avif_srcset"] &&
            $preload->getAttribute("imagesizes") === $hero["sizes"],
        "Hero preload uses the same versioned candidates and sizes as the picture helper",
    );

    ob_start();
    valon_render_article_image(42, true);
    $card = document(ob_get_clean());
    check($card->getElementsByTagName("picture")->length === 1, "Versioned JPEG cover still selects optimized article-card rendering");
    $sources = $card->getElementsByTagName("source");
    check($sources->length === 2, "Article card retains AVIF and WebP sources");
    foreach ($sources as $source) { check_srcset($source->getAttribute("srcset"), [480, 768, 879]); }
    $image = $card->getElementsByTagName("img")->item(0);
    check(
        $image->getAttribute("src") === valon_asset_url("assets/valon-portrait.jpeg") &&
            $image->getAttribute("loading") === "lazy" && $image->getAttribute("alt") === "",
        "Article-card fallback keeps its version, lazy loading, and decorative alt text",
    );
    check(!$image->hasAttribute("fetchpriority"), "Lazy cards do not compete with the article cover for priority");
    ob_start();
    valon_render_article_image(42);
    $article = document(ob_get_clean());
    $image = $article->getElementsByTagName("img")->item(0);
    check(
        $article->getElementsByTagName("picture")->length === 1 &&
            $article->getElementsByTagName("source")->length === 2,
        "Article cover selects the existing AVIF and WebP sources",
    );
    check(
        $image->getAttribute("class") === "article-cover" && $image->getAttribute("loading") === "eager" &&
            $image->getAttribute("alt") === "Valon Asani" && $image->getAttribute("width") === "879" &&
            $image->getAttribute("height") === "894" && $image->getAttribute("style") === "aspect-ratio: 879 / 894" &&
            $image->getAttribute("fetchpriority") === "high",
        "Article cover preserves eager loading, accessible name, class, and original aspect ratio",
    );
    foreach ($additional_photos as $name => [$width, $height, $widths]) {
        $photo = document(valon_static_image($name, ["loading" => "eager", "fetchpriority" => "high"], "900px", true));
        check($photo->getElementsByTagName("source")->length === 2, "$name supplies both modern formats");
        foreach ($photo->getElementsByTagName("source") as $source) { check_srcset($source->getAttribute("srcset"), $widths); }
        $img = $photo->getElementsByTagName("img")->item(0);
        check($img->getAttribute("width") === (string) $width && $img->getAttribute("height") === (string) $height &&
            $img->getAttribute("style") === "aspect-ratio: $width / $height", "$name preserves its full photograph and natural aspect ratio");
    }
    check(
        $article->getElementsByTagName("source")->item(0)->getAttribute("sizes") ===
            "(max-width: 780px) min(calc(100vw - 44px), 629px), (max-width: 964px) min(calc(100vw - 64px), 629px), 629px",
        "Portrait cover candidates follow the painted width inside the contained image box",
    );
    check(valon_article_cover_sizes(1024, 1024) ===
        "(max-width: 780px) min(calc(100vw - 44px), 640px), (max-width: 964px) min(calc(100vw - 64px), 640px), 640px",
        "Square covers select sources for the 640px height limit without changing their layout");
    check(valon_article_cover_sizes(1024, 576) ===
        "(max-width: 780px) calc(100vw - 44px), (max-width: 964px) calc(100vw - 64px), 900px",
        "Wide covers retain the 900px maximum column width and mobile gutters");
    $post_meta[43]["_vp_video_id"] = "video-fixture";
    check(str_ends_with(valon_article_image(43)["url"], "video-cover.jpg?ver=1700000000"), "Manifest-backed video covers receive versions");
    ob_start();
    valon_render_article_image(43);
    $original = document(ob_get_clean());
    $image = $original->getElementsByTagName("img")->item(0);
    check(
        $original->getElementsByTagName("picture")->length === 0 &&
            $image->getAttribute("width") === "640" && $image->getAttribute("height") === "360" &&
            $image->getAttribute("alt") === "Fixture article" && $image->getAttribute("loading") === "eager" &&
            $image->getAttribute("fetchpriority") === "high",
        "Non-whitelisted article covers retain their original image and dimensions",
    );
    $post_meta[44]["_valon_legacy_image"] = "https://www.valonasani.com/wp-content/uploads/legacy.jpg";
    check(valon_article_image(44)["url"] === $post_meta[44]["_valon_legacy_image"], "Uploaded image URLs remain unchanged");

    $upload_ids["https://example.test/wp-content/uploads/2025/03/cover.png"] = 501;
    $upload_meta[501] = ["file" => "2025/03/cover.png", "width" => 3840, "height" => 2160, "sizes" => [
        "large" => ["file" => "cover-1024x576.png", "width" => 1024, "height" => 576],
        "medium_large" => ["file" => "cover-768x432.png", "width" => 768, "height" => 432],
    ]];
    $upload_srcsets[501] = "https://example.test/wp-content/uploads/2025/03/cover-1024x576.png 1024w, https://example.test/wp-content/uploads/2025/03/cover-768x432.png 768w, https://example.test/wp-content/uploads/2025/03/cover-2048x1152.png 2048w";
    $uploaded_url = "https://www.valonasani.com/wp-content/uploads/2025/03/cover-1024x576.png";
    $resolved_cover = valon_uploaded_cover_sources($uploaded_url);
    check($resolved_cover["width"] === 1024 && $resolved_cover["height"] === 576 &&
        str_contains($resolved_cover["srcset"], "768w") && !str_contains($resolved_cover["srcset"], "2048w"),
        "Verified legacy sizes remain responsive without downloading above the original cover width");
    check(!str_contains($resolved_cover["srcset"], "example.test"), "Preview resolves local metadata while preserving the public image origin");
    $lookups_before = count($upload_lookups);
    check(valon_uploaded_cover_sources($uploaded_url) === $resolved_cover && count($upload_lookups) === $lookups_before,
        "Repeated cover resolutions reuse the request cache");
    check(valon_uploaded_cover_sources("https://elsewhere.test/wp-content/uploads/2025/03/cover-1024x576.png") === false &&
        valon_uploaded_cover_sources("https://www.valonasani.com.evil.test/wp-content/uploads/2025/03/cover-1024x576.png") === false &&
        count($upload_lookups) === $lookups_before, "External origins cannot trigger attachment lookup or thumbnail substitution");
    check(valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/2025/03/cover-400x225.png") === false,
        "A guessed suffix is rejected unless that exact filename exists in attachment metadata");
    $upload_ids["https://example.test/wp-content/uploads/2024/03/cover.png"] = 501;
    check(valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/2024/03/cover.png") === false,
        "A matching filename in another upload directory is not substituted");
    $lookups_before = count($upload_lookups);
    check(valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/2025/03/animation.gif") === false &&
        count($upload_lookups) === $lookups_before, "Animated GIF URLs retain the original image without flattened thumbnails");
    $upload_ids["https://example.test/wp-content/uploads/2025/03/mislabeled.png"] = 502;
    $upload_meta[502] = ["file" => "2025/03/mislabeled.png", "width" => 480, "height" => 254, "mime" => "image/gif"];
    check(valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/2025/03/mislabeled.png") === false,
        "GIF attachment MIME types also retain their original file regardless of URL extension");
    check(valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/missing.png") === false,
        "Unknown uploads retain the original fallback");
    $lookups_before = count($upload_lookups);
    valon_uploaded_cover_sources("https://www.valonasani.com/wp-content/uploads/missing.png");
    check(count($upload_lookups) === $lookups_before, "Failed upload lookups are also cached for the request");
    $post_slugs[50] = "uploaded-fixture";
    $source_posts[51] = 50;
    ob_start(); valon_render_article_image(51); $translated_cover = document(ob_get_clean())->getElementsByTagName("img")->item(0);
    check($translated_cover->getAttribute("src") === $uploaded_url && $translated_cover->hasAttribute("srcset") &&
        $translated_cover->getAttribute("width") === "1024" && $translated_cover->getAttribute("height") === "576" &&
        $translated_cover->getAttribute("fetchpriority") === "high", "Translated articles reuse their source cover with verified responsive sizes");
    $post_meta[52]["_valon_legacy_image"] = $uploaded_url;
    ob_start(); valon_render_article_image(52); $legacy_cover = document(ob_get_clean())->getElementsByTagName("img")->item(0);
    check($legacy_cover->getAttribute("srcset") === $translated_cover->getAttribute("srcset"), "Legacy metadata and manifest covers receive the same responsive candidates");
    $post_meta[54]["_vp_source_article"] = 50;
    check(valon_article_image(54)["url"] === $uploaded_url, "Explicit source-article metadata also retains the shared cover");
    ob_start(); valon_render_article_image(52, true); $legacy_card = document(ob_get_clean())->getElementsByTagName("img")->item(0);
    check(!$legacy_card->hasAttribute("srcset") && !$legacy_card->hasAttribute("fetchpriority") && $legacy_card->getAttribute("loading") === "lazy",
        "Legacy cards retain their existing crop and lazy-loading behavior");
    $thumbnail_ids[53] = 501;
    ob_start(); valon_render_article_image(53); $featured_cover = document(ob_get_clean())->getElementsByTagName("img")->item(0);
    check($featured_cover->getAttribute("fetchpriority") === "high" && $featured_cover->getAttribute("sizes") ===
        $translated_cover->getAttribute("sizes"), "Featured attachments receive the same cover priority and accurate column sizes");

    unlink($fixture . "/assets/valon-portrait-480.avif");
    clearstatcache();
    $partial = document(valon_static_image("valon-portrait"));
    $sources = $partial->getElementsByTagName("source");
    check(
        $sources->length === 1 && $sources->item(0)->getAttribute("type") === "image/webp" &&
            $partial->getElementsByTagName("img")->item(0)->getAttribute("src") === valon_asset_url("assets/valon-portrait.jpeg"),
        "Missing AVIF candidate preserves complete WebP sources and JPEG fallback",
    );
    ob_start();
    valon_render_article_image(42);
    $article = document(ob_get_clean());
    check(
        $article->getElementsByTagName("source")->length === 1 &&
            $article->getElementsByTagName("source")->item(0)->getAttribute("type") === "image/webp" &&
            $article->getElementsByTagName("img")->item(0)->getAttribute("loading") === "eager",
        "Article cover retains its eager WebP/JPEG fallback when AVIF is incomplete",
    );
    check(valon_static_image("unknown-photo") === "", "Unsupported optimized image still falls back to the caller");
    echo "Static and article image checks passed.\n";
} finally {
    foreach (glob($fixture . "/assets/*") as $file) { unlink($file); }
    rmdir($fixture . "/assets");
    rmdir($fixture);
}
