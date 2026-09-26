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
    $check(wp_scripts()->get_data("valon-site", "strategy") === "defer" && wp_scripts()->get_data("valon-platform", "strategy") === "defer", "Owned article scripts request deferred execution");
    $check(!wp_scripts()->get_data("wp-consent-api", "strategy") && !wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Enqueue hook leaves the unrecognized consent integration unchanged");
    ob_start();
    wp_scripts()->do_items(["valon-platform", "valon-site"], 1);
    $scripts = ob_get_clean();
    $check(substr_count($scripts, " defer") === 2 && strpos($scripts, 'id="wp-consent-api-js"') < strpos($scripts, 'id="valon-platform-js"'), "WordPress emits both defer attributes after the blocking consent dependency");
    $check(strpos($scripts, "window.valonPlatform={};") < strpos($scripts, 'id="valon-platform-js"'), "Inline configuration remains before its deferred consumer");

    // Reproduce the installed Complianz callback without loading the real plugin or changing consent.
    class cmplz_banner_loader {
        public $defer = true;
        public function add_asyncdefer_attribute($tag, $handle) {
            return $this->defer && $handle === "cmplz-cookiebanner" ? preg_replace('/^<script /', '<script defer ', $tag) : $tag;
        }
    }
    $cmp = new cmplz_banner_loader();
    add_filter("script_loader_tag", [$cmp, "add_asyncdefer_attribute"], 10, 2);
    $consent_fixture = static function () {
        $GLOBALS["wp_scripts"] = new WP_Scripts();
        foreach ([
            "googlesitekit-consent-mode" => "/google-site-kit/dist/assets/js/googlesitekit-consent-mode-755f1678e260138d789e.js",
            "wp-consent-api" => "/wp-consent-api/assets/js/wp-consent-api.min.js",
            "cmplz-cookiebanner" => "/complianz-gdpr/cookiebanner/js/complianz.min.js",
        ] as $handle => $relative) {
            wp_enqueue_script($handle, plugins_url($relative), [], null, true);
        }
        wp_localize_script("wp-consent-api", "consent_api", ["waitfor_consent_hook" => false]);
        wp_localize_script("cmplz-cookiebanner", "complianz", ["consenttype" => "optin"]);
    };
    $print_footer = static function () {
        ob_start();
        do_action("wp_print_footer_scripts");
        return ob_get_clean();
    };
    $script_attributes = static function ($html) {
        $tags = new WP_HTML_Tag_Processor($html);
        $result = [];
        while ($tags->next_tag("SCRIPT")) {
            $result[$tags->get_attribute("id")] = ["defer" => $tags->get_attribute("defer") !== null, "async" => $tags->get_attribute("async") !== null];
        }
        return $result;
    };
    $consent_fixture();
    $html = $print_footer();
    $attrs = $script_attributes($html);
    $check($attrs["googlesitekit-consent-mode-js"]["defer"] && !$attrs["wp-consent-api-js"]["defer"] && $attrs["cmplz-cookiebanner-js"]["defer"], "Recognized footer integration defers only Site Kit beside the existing deferred CMP");
    $check(strpos($html, 'id="googlesitekit-consent-mode-js"') < strpos($html, 'id="wp-consent-api-js"') && strpos($html, 'id="wp-consent-api-js"') < strpos($html, 'id="cmplz-cookiebanner-js"'), "Site Kit listeners precede CMP while the consent API remains parser-blocking");
    $check(strpos($html, 'var consent_api =') < strpos($html, 'id="wp-consent-api-js"') && strpos($html, 'var complianz =') < strpos($html, 'id="cmplz-cookiebanner-js"'), "Localized consent configuration remains before its consumers");
    $check(!wp_scripts()->get_data("wp-consent-api", "strategy") && !wp_scripts()->get_data("cmplz-cookiebanner", "strategy"), "The optimization never sets API or CMP strategies");

    $negative_cases = [
        "missing CMP" => static function () { wp_dequeue_script("cmplz-cookiebanner"); },
        "new Site Kit bundle" => static function () { wp_scripts()->registered["googlesitekit-consent-mode"]->src = plugins_url("/google-site-kit/dist/assets/js/googlesitekit-consent-mode-new.js"); },
        "foreign consent API" => static function () { wp_scripts()->registered["wp-consent-api"]->src = "https://unknown.example/wp-consent-api.min.js"; },
        "API in head" => static function () { wp_script_add_data("wp-consent-api", "group", 0); },
        "reordered CMP" => static function () { wp_scripts()->queue = ["cmplz-cookiebanner", "googlesitekit-consent-mode", "wp-consent-api"]; },
        "dependency pulls CMP ahead" => static function () { wp_enqueue_script("earlier-consumer", home_url("/early.js"), ["cmplz-cookiebanner"], null, true); array_unshift(wp_scripts()->queue, "earlier-consumer"); },
        "changed Site Kit dependencies" => static function () { wp_scripts()->registered["googlesitekit-consent-mode"]->deps = ["wp-consent-api"]; },
        "already printed CMP" => static function () { wp_scripts()->done[] = "cmplz-cookiebanner"; },
        "API async strategy" => static function () { wp_script_add_data("wp-consent-api", "strategy", "async"); },
        "CMP async strategy" => static function () { wp_script_add_data("cmplz-cookiebanner", "strategy", "async"); },
    ];
    foreach (["googlesitekit-consent-mode", "wp-consent-api", "cmplz-cookiebanner"] as $handle) {
        foreach (["before", "after"] as $position) {
            $negative_cases["$handle inline $position"] = static function () use ($handle, $position) { wp_add_inline_script($handle, "window.consentFixture=true;", $position); };
        }
    }
    foreach ($negative_cases as $label => $mutate) {
        $consent_fixture();
        $mutate();
        $print_footer();
        $check(!wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Unknown integration falls back: $label");
    }
    $consent_fixture();
    $cmp->defer = false;
    $print_footer();
    $check(!wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "A CMP callback that stops deferring disables the optimization");
    $cmp->defer = true;
    remove_filter("script_loader_tag", [$cmp, "add_asyncdefer_attribute"], 10);
    $consent_fixture();
    $print_footer();
    $check(!wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Absent recognized CMP callback preserves blocking Site Kit");
    add_filter("script_loader_tag", [$cmp, "add_asyncdefer_attribute"], 10, 2);
    $consent_fixture();
    wp_script_add_data("googlesitekit-consent-mode", "strategy", "async");
    $print_footer();
    $check(wp_scripts()->get_data("googlesitekit-consent-mode", "strategy") === "async", "Existing third-party Site Kit strategy is never overwritten");
    $consent_fixture();
    wp_enqueue_script("blocking-consumer", home_url("/consumer.js"), ["googlesitekit-consent-mode"], null, true);
    $attrs = $script_attributes($print_footer());
    $check(!$attrs["googlesitekit-consent-mode-js"]["defer"], "Native WordPress falls back to blocking for an enqueued blocking dependent");
    $consent_fixture();
    wp_scripts()->do_items([], 0);
    $check(!wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Head processing does not alter consent strategies");

    $query = new WP_Query(["post_type" => "post", "name" => "building-in-public-get-ready-for-the-energy-vampires", "posts_per_page" => 1]);
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    wp_register_script("valon-site", home_url("/site.js"), [], null, true);
    wp_register_script("valon-platform", home_url("/platform.js"), [], null, true);
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "defer" && wp_scripts()->get_data("valon-platform", "strategy") === "defer", "Ordinary articles retain deferred owned scripts");
    $consent_fixture();
    $print_footer();
    $check(wp_scripts()->get_data("googlesitekit-consent-mode", "strategy") === "defer", "Ordinary articles use the same guarded consent listener strategy");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = new WP_Query(["post_type" => "page", "posts_per_page" => 1]);
    $consent_fixture();
    $print_footer();
    $check(!wp_scripts()->get_data("googlesitekit-consent-mode", "strategy"), "Pages retain the plugin's consent loading policy");
    wp_register_script("valon-site", home_url("/site.js"), [], null, true);
    wp_script_add_data("valon-site", "strategy", "async");
    valon_defer_article_scripts();
    $check(wp_scripts()->get_data("valon-site", "strategy") === "async", "Other page types keep their existing script loading");
} finally {
    if (isset($cmp)) {
        remove_filter("script_loader_tag", [$cmp, "add_asyncdefer_attribute"], 10);
    }
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
