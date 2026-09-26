<?php
/** Read-only WordPress checks for exact reviewed remote originals. No database writes. */
if (PHP_SAPI !== "cli" || !defined("ABSPATH")) { exit("Load WordPress first: wp eval-file tools/remote-images-qa.php\n"); }
$check = static function ($ok, $message) { if (!$ok) { throw new RuntimeException($message); } echo "PASS $message\n"; };
$types = static function ($html) { $tags = new WP_HTML_Tag_Processor($html); $found = []; while ($tags->next_tag("SOURCE")) { $found[] = $tags->get_attribute("type"); } return $found; };
$theme = get_template_directory();
$manifest = json_decode(file_get_contents($theme . "/assets/optimized-remote-images.json"), true);
$urls = array_keys($manifest);
$original_query = $GLOBALS["wp_query"];
$original_main = $GLOBALS["wp_the_query"];
$original_post = $GLOBALS["post"] ?? null;
$fixture = sys_get_temp_dir() . "/valon-remote-qa-" . bin2hex(random_bytes(6));
$directory_filter = static fn() => $fixture;
try {
    $query = new WP_Query(["post_type" => "post", "name" => "why-not-eating-a-croissant-every-morning-can-change-your-life", "posts_per_page" => 1]);
    $check($query->have_posts(), "Croissant article exists in real WordPress");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query; $query->the_post();
    $post_id = get_the_ID(); $content = get_post_field("post_content", $post_id); $editorial = valon_article_image($post_id);
    foreach ($urls as $url) {
        $fallback = '<img loading="lazy" decoding="async" src="' . $url . '" alt=\'A > B &amp; C\' style="width:635px;height:auto" />';
        $picture = valon_upload_picture($url, $fallback, "720px");
        $check($types($picture) === ["image/avif", "image/webp"] && str_ends_with($picture, $fallback . "</picture>"), "Both native formats retain original IMG bytes and attributes");
        $tag = new WP_HTML_Tag_Processor($picture);
        while ($tag->next_tag("SOURCE")) {
            $check($tag->get_attribute("sizes") === null && !preg_match('/\s[0-9]+w/', $tag->get_attribute("srcset")) && $tag->get_attribute("width") === null,
                "Native1x source keeps original natural sizing without width descriptors or injected dimensions");
        }
        $sized = '<img src="' . $url . '" width="300" height="200">';
        $tag = new WP_HTML_Tag_Processor(valon_remote_picture($url, $sized)); $tag->next_tag("SOURCE");
        $check($tag->get_attribute("width") === "300" && $tag->get_attribute("height") === "200", "Explicit original dimensions also apply to the source");
        foreach ([$url . "?x=1", $url . "#fragment", $url . "-unknown", str_replace("https://", "http://", $url), str_replace("googleusercontent.com/", "googleusercontent.com.evil.test/", $url)] as $unknown) {
            $other = '<img src="' . $unknown . '">';
            $check(valon_upload_picture($unknown, $other, "720px") === $other, "Unknown URL, origin, query or fragment remains unchanged");
        }
        $check(valon_remote_picture($url, $picture) === $picture && valon_remote_picture($url, $fallback . $fallback) === $fallback . $fallback,
            "Existing picture and multiple IMG input stay unchanged");
        $other = '<img src="https://example.test/other.png">';
        $check(valon_remote_picture($url, $other) === $other, "Exact IMG source identity is required");
    }
    $filtered = wp_filter_content_tags($content, "the_content");
    $wrapped = valon_article_body_pictures($filtered);
    $check(count($types($wrapped)) === 4 && !str_contains($wrapped, "<picture><picture>"), "Real article wraps exactly the two reviewed body images");
    $check(valon_article_body_pictures($wrapped) === $wrapped, "Repeated body filtering is idempotent");
    preg_match_all('~<img\b(?:[^>\'\"]|"[^"]*"|\'[^\']*\')*>~i', $filtered, $old);
    preg_match_all('~<img\b(?:[^>\'\"]|"[^"]*"|\'[^\']*\')*>~i', $wrapped, $new);
    $check($old[0] === $new[0], "Every original real-content IMG byte is retained");
    mkdir($fixture . "/assets", 0700, true);
    copy($theme . "/assets/optimized-remote-images.json", $fixture . "/assets/optimized-remote-images.json");
    $webp = $fixture . "/assets/" . $manifest[$urls[0]]["basename"] . ".webp";
    file_put_contents($webp, "temporary source-presence fixture");
    add_filter("template_directory", $directory_filter);
    $fallback = '<img src="' . $urls[0] . '" loading="lazy">';
    $check($types(valon_remote_picture($urls[0], $fallback)) === ["image/webp"], "Missing AVIF keeps WebP and original fallback");
    unlink($webp); clearstatcache();
    $check(valon_remote_picture($urls[0], $fallback) === $fallback, "Missing both assets returns exact original IMG");
    remove_filter("template_directory", $directory_filter);
    $check(get_post_field("post_content", $post_id) === $content && valon_article_image($post_id) === $editorial, "Stored content and editorial/SEO identity remain unchanged");
    echo "Remote image integration checks passed.\n";
} finally {
    remove_filter("template_directory", $directory_filter);
    $GLOBALS["wp_query"] = $original_query; $GLOBALS["wp_the_query"] = $original_main; $GLOBALS["post"] = $original_post;
    if (is_dir($fixture . "/assets")) { foreach (glob($fixture . "/assets/*") as $file) { unlink($file); } rmdir($fixture . "/assets"); rmdir($fixture); }
}
