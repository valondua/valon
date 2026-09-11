<?php
/**
 * Plugin Name: Valon Platform
 * Description: Owned social feeds, editorial review, Mailchimp double opt-in and personal identity.
 * Version: 1.0.0
 * Requires PHP: 8.1
 */
defined("ABSPATH") || exit();
define("VP_DIR", __DIR__);
function vp_text($en, $sq, $lang = "")
{
    return ($lang ?:
        (function_exists("pll_current_language")
            ? pll_current_language("slug")
            : "en")) === "sq"
        ? $sq
        : $en;
}
function vp_secret($name)
{
    $value = defined($name) ? constant($name) : getenv($name);
    return is_string($value) ? $value : "";
}
require_once VP_DIR . "/includes/social.php";
require_once VP_DIR . "/includes/admin.php";
require_once VP_DIR . "/includes/newsletter.php";
require_once VP_DIR . "/includes/seo.php";
add_action("init", ["VP_Social", "register"]);
add_action("vp_hourly_sync", ["VP_Social", "sync_all"]);
register_activation_hook(__FILE__, function () {
    VP_Social::register();
    if (!wp_next_scheduled("vp_hourly_sync")) {
        wp_schedule_event(time() + 60, "hourly", "vp_hourly_sync");
    }
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook("vp_hourly_sync");
});
add_action("wp_enqueue_scripts", function () {
    wp_enqueue_script(
        "valon-platform",
        plugins_url("assets/platform.js", __FILE__),
        [],
        filemtime(VP_DIR . "/assets/platform.js"),
        true,
    );
    wp_localize_script("valon-platform", "valonPlatform", [
        "subscribe" => rest_url("valon/v1/subscribe"),
        "embed" => rest_url("valon/v1/embed/"),
        "isLocal" => wp_get_environment_type() === "local",
    ]);
});
if (defined("WP_CLI") && WP_CLI) {
    require_once VP_DIR . "/includes/cli.php";
}
