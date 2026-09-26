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
function get_post_thumbnail_id($id) { return 0; }
function get_post_type($id) { return "post"; }
function get_post_field($field, $id) { return "card-fixture"; }
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
            $image->getAttribute("height") === "894" && $image->getAttribute("style") === "aspect-ratio: 879 / 894",
        "Article cover preserves eager loading, accessible name, class, and original aspect ratio",
    );
    check(
        $article->getElementsByTagName("source")->item(0)->getAttribute("sizes") ===
            "(max-width: 780px) calc(100vw - 44px), (max-width: 964px) calc(100vw - 64px), 900px",
        "Article cover candidates follow the rendered column width",
    );
    $post_meta[43]["_vp_video_id"] = "video-fixture";
    check(str_ends_with(valon_article_image(43)["url"], "video-cover.jpg?ver=1700000000"), "Manifest-backed video covers receive versions");
    ob_start();
    valon_render_article_image(43);
    $original = document(ob_get_clean());
    $image = $original->getElementsByTagName("img")->item(0);
    check(
        $original->getElementsByTagName("picture")->length === 0 &&
            $image->getAttribute("width") === "640" && $image->getAttribute("height") === "360" &&
            $image->getAttribute("alt") === "Fixture article" && $image->getAttribute("loading") === "eager",
        "Non-whitelisted article covers retain their original image and dimensions",
    );
    $post_meta[44]["_valon_legacy_image"] = "https://www.valonasani.com/wp-content/uploads/legacy.jpg";
    check(valon_article_image(44)["url"] === $post_meta[44]["_valon_legacy_image"], "Uploaded image URLs remain unchanged");

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
    echo "Static image cache-version checks passed.\n";
} finally {
    foreach (glob($fixture . "/assets/*") as $file) { unlink($file); }
    rmdir($fixture . "/assets");
    rmdir($fixture);
}
