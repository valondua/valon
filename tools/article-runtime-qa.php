<?php
/** Read-only integration checks: wp --skip-plugins=complianz-gdpr eval-file tools/article-runtime-qa.php */
if (PHP_SAPI !== "cli" || !defined("ABSPATH") || function_exists("cmplz_placeholder")) {
    exit("Load WordPress from the command line with Complianz skipped.\n");
}
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
};
$original_query = $GLOBALS["wp_query"];
$original_main = $GLOBALS["wp_the_query"];
$original_post = $GLOBALS["post"] ?? null;
$original_scripts = $GLOBALS["wp_scripts"] ?? null;
$video = get_posts(["post_type" => "post", "posts_per_page" => 1, "fields" => "ids", "meta_key" => "_vp_video_id", "meta_compare" => "EXISTS"]);
$check((bool) $video, "Representative video article exists");
$query = new WP_Query(["p" => $video[0], "post_type" => "post"]);
$GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
$query->the_post();
try {
    $check(valon_video_placeholder_url() === "", "Inactive consent plugin produces no preload");
    $GLOBALS["valon_qa_placeholder_enabled"] = true;
    $GLOBALS["valon_qa_placeholder_url"] = home_url("/wp-content/plugins/complianz-gdpr/assets/images/placeholders/tiktok-minimal.jpg");
    // These stand-ins exercise the theme boundary using WordPress's real HTML parser.
    if (!function_exists("cmplz_placeholder")) {
        function cmplz_placeholder($type, $src) {
            $GLOBALS["valon_qa_placeholder_args"] = [$type, $src];
            return $GLOBALS["valon_qa_placeholder_url"];
        }
        function cmplz_use_placeholder($src) { return $GLOBALS["valon_qa_placeholder_enabled"]; }
    }
    $expected = $GLOBALS["valon_qa_placeholder_url"];
    $check(valon_video_placeholder_url() === $expected, "Video preload uses the plugin-resolved same-origin image");
    $player = vp_video_article_player($video[0]);
    $iframe = new WP_HTML_Tag_Processor($player);
    $iframe->next_tag("IFRAME");
    $check($GLOBALS["valon_qa_placeholder_args"] === ["tiktok", $iframe->get_attribute("src")], "Placeholder resolution receives the exact rendered player URL");
    ob_start();
    valon_preload_video_placeholder();
    $preload = ob_get_clean();
    $check(str_contains($preload, 'rel="preload" as="image"') && str_contains($preload, 'fetchpriority="high"') && str_contains($preload, esc_url($expected)), "Placeholder is discoverable in HTML at high priority");
    $GLOBALS["valon_qa_placeholder_enabled"] = false;
    $check(valon_video_placeholder_url() === "", "Disabled placeholders preserve the plugin fallback");
    $GLOBALS["valon_qa_placeholder_enabled"] = true;
    foreach (["https://external.test/image.jpg", "//external.test/image.jpg", "data:image/gif;base64,AA", "javascript:alert(1)", "", false, str_replace("://", "://user:password@", home_url("/image.jpg"))] as $invalid) {
        $GLOBALS["valon_qa_placeholder_url"] = $invalid;
        $check(valon_video_placeholder_url() === "", "Unsafe or external placeholder is not preloaded");
    }
    $GLOBALS["valon_qa_placeholder_url"] = home_url("/custom-consent-image.webp?style=light&v=2");
    $check(valon_video_placeholder_url() === $GLOBALS["valon_qa_placeholder_url"], "Same-origin custom placeholders retain their exact URL");
    $image = '<img src="https://www.valonasani.com/wp-content/uploads/2025/06/image-1.png" width="360" height="360" loading="eager" fetchpriority="high" alt="Fixture">';
    $picture = valon_article_body_pictures($image);
    $check(str_starts_with($picture, "<picture>") && str_contains($picture, "360px"), "Allowlisted video body images use a source sized to their original width");
    $check(str_contains($picture, $image) && valon_article_body_image_loading($image, "the_content") === $image, "Video body fallback attributes and loading priority are unchanged");
    $check(valon_article_body_pictures($player) === $player && vp_video_article_player($video[0]) === $player, "Player markup and consent integration are unchanged");
    foreach (["1024" => "720px", "" => "720px", "0" => "720px", "50%" => "720px"] as $width => $maximum) {
        $result = valon_article_body_pictures(str_replace('width="360"', 'width="' . $width . '"', $image));
        $check(str_contains($result, $maximum), "Body responsive sizes have a bounded fallback");
    }
    $unknown = str_replace("image-1.png", "unknown.png", $image);
    $check(valon_article_body_pictures($unknown) === $unknown, "Unlisted video body images retain the original HTML");
    $query->in_the_loop = false;
    $check(valon_article_body_pictures($image) === $image, "Metadata extraction is not rewritten");
    $query->in_the_loop = true;

    $GLOBALS["wp_scripts"] = new WP_Scripts();
    wp_register_script("wp-consent-api", home_url("/consent.js"), [], null, true);
    wp_register_script("valon-platform", home_url("/platform.js"), ["wp-consent-api"], null, true);
    wp_register_script("valon-site", home_url("/site.js"), [], null, true);
    wp_register_script("googlesitekit-consent-mode", home_url("/sitekit.js"), [], null, true);
    wp_add_inline_script("valon-platform", "window.valonPlatform={};", "before");
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "defer" && wp_scripts()->get_data("valon-platform", "strategy") === "defer", "Only owned article scripts request deferred execution");
    $check(!wp_scripts()->get_data("wp-consent-api", "strategy") && !wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Consent and Site Kit execution strategies are unchanged");
    ob_start();
    wp_scripts()->do_items(["valon-platform", "valon-site"], 1);
    $scripts = ob_get_clean();
    $check(substr_count($scripts, " defer") === 2 && strpos($scripts, 'id="wp-consent-api-js"') < strpos($scripts, 'id="valon-platform-js"'), "WordPress emits both defer attributes after the blocking consent dependency");
    $check(strpos($scripts, "window.valonPlatform={};") < strpos($scripts, 'id="valon-platform-js"'), "Inline configuration remains before its deferred consumer");

    $query = new WP_Query(["post_type" => "post", "name" => "building-in-public-get-ready-for-the-energy-vampires", "posts_per_page" => 1]);
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    $check(valon_video_placeholder_url() === "", "Ordinary articles do not preload the video placeholder");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = new WP_Query(["post_type" => "page", "posts_per_page" => 1]);
    wp_script_add_data("valon-site", "strategy", "async");
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "async" && valon_video_placeholder_url() === "", "Other page types keep their existing script and image loading");
} finally {
    $GLOBALS["wp_query"] = $original_query;
    $GLOBALS["wp_the_query"] = $original_main;
    $GLOBALS["post"] = $original_post;
    $GLOBALS["wp_scripts"] = $original_scripts;
}
