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
$original_server_name = $_SERVER["SERVER_NAME"] ?? null;
$video = get_posts(["post_type" => "post", "posts_per_page" => 1, "fields" => "ids", "meta_key" => "_vp_video_id", "meta_compare" => "EXISTS"]);
$check((bool) $video, "Representative video article exists");
$query = new WP_Query(["p" => $video[0], "post_type" => "post"]);
$GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
$query->the_post();
try {
    // Emulate an enabled same-origin placeholder so restoring the old preload hook fails this check.
    $GLOBALS["valon_qa_placeholder_url"] = home_url("/wp-content/plugins/complianz-gdpr/assets/images/placeholders/tiktok-minimal.jpg");
    if (!function_exists("cmplz_placeholder")) {
        function cmplz_placeholder($type, $src) { return $GLOBALS["valon_qa_placeholder_url"]; }
        function cmplz_use_placeholder($src) { return true; }
    }
    $player = vp_video_article_player($video[0]);
    $iframe = new WP_HTML_Tag_Processor($player);
    $check($iframe->next_tag("IFRAME") && wp_parse_url($iframe->get_attribute("src"), PHP_URL_HOST) === "www.tiktok.com", "Video article retains its TikTok player URL");
    $_SERVER["SERVER_NAME"] = wp_parse_url(home_url("/"), PHP_URL_HOST);
    ob_start();
    do_action("wp_head");
    $head = new WP_HTML_Tag_Processor(ob_get_clean());
    $placeholder_preloaded = false;
    while ($head->next_tag("LINK")) {
        if ($head->get_attribute("rel") === "preload" && $head->get_attribute("as") === "image" &&
            $head->get_attribute("href") === $GLOBALS["valon_qa_placeholder_url"]) {
            $placeholder_preloaded = true;
        }
    }
    $check(!$placeholder_preloaded, "wp_head leaves consent-placeholder loading to Complianz without a theme image preload");
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
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "defer" && wp_scripts()->get_data("valon-platform", "strategy") === "defer", "Ordinary articles retain deferred owned scripts");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = new WP_Query(["post_type" => "page", "posts_per_page" => 1]);
    wp_script_add_data("valon-site", "strategy", "async");
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "async", "Other page types keep their existing script loading");
} finally {
    $GLOBALS["wp_query"] = $original_query;
    $GLOBALS["wp_the_query"] = $original_main;
    $GLOBALS["post"] = $original_post;
    $GLOBALS["wp_scripts"] = $original_scripts;
    if ($original_server_name === null) {
        unset($_SERVER["SERVER_NAME"]);
    } else {
        $_SERVER["SERVER_NAME"] = $original_server_name;
    }
}
