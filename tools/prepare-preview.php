<?php
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Local preview only.");
}
$GLOBALS["vp_preview_pages"] = true;
foreach ([1, 2, 3] as $id) {
    $p = get_post($id);
    if (
        $p &&
        !get_post_meta($id, "_valon_legacy_id", true) &&
        in_array(
            $p->post_name,
            ["hello-world", "sample-page", "privacy-policy"],
            true,
        )
    ) {
        wp_delete_post($id, true);
    }
}
$map = get_option("valon_pages", []);
$privacy = $map["en"]["privacy-policy"];
$legacy = get_posts([
    "post_type" => "page",
    "post_status" => "any",
    "numberposts" => 1,
    "meta_key" => "_valon_legacy_id",
    "meta_value" => 1268,
    "suppress_filters" => true,
]);
if ($legacy && $legacy[0]->ID !== $privacy) {
    wp_update_post([
        "ID" => $legacy[0]->ID,
        "post_status" => "draft",
        "post_name" => "privacy-policy-original",
    ]);
}
wp_update_post(["ID" => $privacy, "post_name" => "privacy-policy"]);
update_option("wp_page_for_privacy_policy", $privacy);
flush_rewrite_rules();
WP_CLI::success("Local seed cleanup and privacy URL repaired.");
