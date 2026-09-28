<?php
// Local-only integration test for exact approved draft promotion.
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Article revision QA is local-only.");
}
$previous_user = get_current_user_id();
$previous_owner = get_option("vp_editorial_owner");
$source_id = 0;
$draft_id = 0;
try {
    wp_set_current_user(1);
    update_option("vp_editorial_owner", 1);
    $GLOBALS["vp_archive_import"] = true;
    $source_id = wp_insert_post([
        "post_type" => "post",
        "post_status" => "publish",
        "post_title" => "Original article",
        "post_name" => "revision-qa-original",
        "post_content" => "Original copy",
        "post_date" => "2025-01-18 12:00:00",
    ]);
    unset($GLOBALS["vp_archive_import"]);
    $draft_id = wp_insert_post([
        "post_type" => "post",
        "post_status" => "draft",
        "post_title" => "Reviewed article",
        "post_content" => "New copy with evidence.",
    ]);
    if (function_exists("pll_set_post_language")) {
        pll_set_post_language($source_id, "en");
        pll_set_post_language($draft_id, "en");
    }
    $original = get_post($source_id);
    if (!is_wp_error(vp_apply_article_revision($source_id, $draft_id))) {
        throw new RuntimeException("An unapproved draft was applied.");
    }
    if (get_post($source_id)->post_content !== "Original copy") {
        throw new RuntimeException("An unapproved draft changed the live article.");
    }
    update_post_meta($draft_id, "_vp_approved_by", 1);
    update_post_meta($draft_id, "_vp_approved_hash", vp_review_hash("Reviewed article", "New copy with evidence."));
    $applied = vp_apply_article_revision($source_id, $draft_id);
    if (is_wp_error($applied) || $applied !== $source_id) {
        throw new RuntimeException("Approved revision did not apply: " . (is_wp_error($applied) ? $applied->get_error_message() : "wrong ID"));
    }
    $result = get_post($source_id);
    if (
        $result->post_status !== "publish" ||
        $result->post_name !== $original->post_name ||
        $result->post_date !== $original->post_date ||
        $result->post_title !== "Reviewed article" ||
        $result->post_content !== "New copy with evidence." ||
        get_post_meta($source_id, "_valon_substantive_update", true) !== "1" ||
        get_post_status($draft_id) !== "private"
    ) {
        throw new RuntimeException("Revision did not preserve publication identity and archive the draft.");
    }
    WP_CLI::success("Article revision approval, URL/date preservation and draft retirement passed.");
} finally {
    if ($draft_id) {
        wp_delete_post($draft_id, true);
    }
    if ($source_id) {
        wp_delete_post($source_id, true);
    }
    if ($previous_owner === false) {
        delete_option("vp_editorial_owner");
    } else {
        update_option("vp_editorial_owner", $previous_owner);
    }
    wp_set_current_user($previous_user);
}
