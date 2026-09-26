<?php
/** Standalone checks for the related-post stylesheet's safe inline boundary. */
if (PHP_SAPI !== "cli" || defined("ABSPATH")) {
    exit("Run standalone: php tools/article-styles-qa.php\n");
}
define("ABSPATH", __DIR__ . "/../");
$fixture = sys_get_temp_dir() . "/valon-styles-" . bin2hex(random_bytes(6));
define("WP_PLUGIN_DIR", $fixture);
$single = true;
function is_singular($type) { return $GLOBALS["single"] && $type === "post"; }
function plugins_url($path) { return "https://example.test/wp-content/plugins" . $path; }
function wp_parse_url($url, $part) { return parse_url($url, $part); }
function add_filter(...$arguments) {}
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES); }
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
}
require __DIR__ . "/../includes/performance.php";
$file = $fixture . "/yet-another-related-posts-plugin/style/related.css";
mkdir(dirname($file), 0700, true);
$href = plugins_url("/yet-another-related-posts-plugin/style/related.css?ver=5.30.11");
$link = '<link rel="stylesheet" href="' . $href . '">';
try {
    $css = ".yarpp-related { margin: 1em 0; }";
    file_put_contents($file, $css);
    $render = static fn($handle = "yarppRelatedCss", $url = null) => valon_inline_related_styles($link, $handle, $url ?? $href, "screen");
    check($render() === '<style id="yarppRelatedCss-css" media="screen">' . $css . '</style>', "Same CSS, ID and media are retained at the original position");
    check($render("cmplz-banner-1-optin") === $link, "Consent styles remain external");
    check($render("other") === $link, "Other plugin styles remain unchanged");
    check($render("yarppRelatedCss", str_replace("example.test", "cdn.example.test", $href)) === $link, "Custom CDN source retains its link");
    check($render("yarppRelatedCss", str_replace("related.css", "custom.css", $href)) === $link, "Overridden file retains its link");
    $single = false;
    check($render() === $link, "Archives and pages retain their existing styles");
    $single = true;
    foreach (["", "@import 'other.css';", ".x{background:url(image.png)}", "</style>", str_repeat(" ", 4097)] as $unsupported) {
        file_put_contents($file, $unsupported);
        clearstatcache();
        check($render() === $link, "Unsupported plugin CSS preserves the external fallback");
    }
    unlink($file);
    check($render() === $link, "Missing plugin file preserves the external fallback");
} finally {
    if (is_file($file)) { unlink($file); }
    rmdir(dirname($file));
    rmdir(dirname(dirname($file)));
    rmdir($fixture);
}
