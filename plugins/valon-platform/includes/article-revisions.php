<?php
defined("ABSPATH") || exit();

/** Apply an exact, owner-approved revision to a legacy post without changing its URL or date. */
add_action("admin_menu", function () {
    add_management_page(
        "Valon article revisions",
        "Valon article revisions",
        "manage_options",
        "valon-article-revisions",
        "vp_article_revisions_page",
    );
});

function vp_article_revisions_page()
{
    if (!vp_can_approve()) {
        wp_die("Editorial owner access required.", "", ["response" => 403]);
    }
    $notice = get_transient("vp_article_revision_notice_" . get_current_user_id());
    echo '<div class="wrap"><h1>Apply a reviewed article revision</h1>';
    if ($notice) {
        echo '<div class="notice notice-info"><p>' . esc_html($notice) . "</p></div>";
    }
    echo '<p>Keep the existing published URL and date. The draft must have the exact approval recorded in Social queue → Connections &amp; review. A full backup and WordPress revisions provide rollback.</p>';
    echo '<form method="post" action="' . esc_url(admin_url("admin-post.php")) . '">';
    echo '<input type="hidden" name="action" value="vp_apply_article_revision">';
    wp_nonce_field("vp_apply_article_revision");
    echo '<p><label>Existing published article ID <input type="number" min="1" required name="source_id" value="' . absint($_GET["source_id"] ?? 0) . '"></label></p>';
    echo '<p><label>Approved revision draft ID <input type="number" min="1" required name="draft_id" value="' . absint($_GET["draft_id"] ?? 0) . '"></label></p>';
    echo '<p><label><input type="checkbox" name="reviewed" value="1" required> I have reviewed this exact draft against the published article.</label></p>';
    echo '<p><label><input type="checkbox" name="backup" value="1" required> A complete backup of the published article and site is available.</label></p>';
    submit_button("Apply approved revision");
    echo "</form></div>";
}

function vp_apply_article_revision($source_id, $draft_id)
{
    if (!vp_can_approve()) {
        return new WP_Error("owner", "Editorial owner access required.");
    }
    $source = get_post($source_id);
    $draft = get_post($draft_id);
    if (
        !$source || !$draft ||
        $source->post_type !== "post" ||
        $source->post_status !== "publish" ||
        $draft->post_type !== "post" ||
        $draft->post_status !== "draft" ||
        $source_id === $draft_id ||
        !current_user_can("edit_post", $source_id) ||
        !current_user_can("edit_post", $draft_id)
    ) {
        return new WP_Error("posts", "Choose one published article and a separate review draft.");
    }
    if (
        function_exists("pll_get_post_language") &&
        pll_get_post_language($source_id) !== pll_get_post_language($draft_id)
    ) {
        return new WP_Error("language", "The article and revision must use the same language.");
    }
    $hash = vp_review_hash($draft->post_title, $draft->post_content);
    if (
        get_post_meta($draft_id, "_vp_approved_by", true) != get_current_user_id() ||
        !hash_equals((string) get_post_meta($draft_id, "_vp_approved_hash", true), $hash)
    ) {
        return new WP_Error("review", "Approve the exact draft before applying it.");
    }

    // WordPress saves the old post as a normal revision; the full site backup is a second restore path.
    wp_save_post_revision($source_id);
    update_post_meta($source_id, "_vp_requires_review", "1");
    update_post_meta($source_id, "_vp_approved_hash", $hash);
    update_post_meta($source_id, "_vp_approved_by", get_current_user_id());
    // The old SEO titles describe the old article; take the draft's or fall back to the site pattern.
    foreach (["_yoast_wpseo_title", "_yoast_wpseo_opengraph-title", "_yoast_wpseo_twitter-title"] as $key) {
        $value = get_post_meta($draft_id, $key, true);
        $value ? update_post_meta($source_id, $key, $value) : delete_post_meta($source_id, $key);
    }
    update_post_meta($source_id, "_vp_seo_title_synced", "1");
    $result = wp_update_post(
        wp_slash([
            "ID" => $source_id,
            "post_title" => $draft->post_title,
            "post_content" => $draft->post_content,
            "post_excerpt" => $draft->post_excerpt,
            "post_status" => "publish",
        ]),
        true,
    );
    if (
        is_wp_error($result) ||
        get_post_status($source_id) !== "publish" ||
        vp_review_hash(get_post($source_id)->post_title, get_post($source_id)->post_content) !== $hash
    ) {
        return new WP_Error("apply", "The revision could not be applied; inspect the published post and restore the previous revision if needed.");
    }
    update_post_meta($source_id, "_valon_substantive_update", "1");
    $description = get_post_meta($draft_id, "_yoast_wpseo_metadesc", true);
    if ($description) {
        update_post_meta($source_id, "_yoast_wpseo_metadesc", $description);
    }
    wp_update_post(["ID" => $draft_id, "post_status" => "private"]);
    return $source_id;
}

add_action("admin_post_vp_apply_article_revision", function () {
    if (!vp_can_approve()) {
        wp_die("Editorial owner access required.", "", ["response" => 403]);
    }
    check_admin_referer("vp_apply_article_revision");
    $source_id = absint($_POST["source_id"] ?? 0);
    $draft_id = absint($_POST["draft_id"] ?? 0);
    if (($_POST["reviewed"] ?? "") !== "1" || ($_POST["backup"] ?? "") !== "1") {
        $result = new WP_Error("confirmation", "Review and backup confirmation are required.");
    } else {
        $result = vp_apply_article_revision($source_id, $draft_id);
    }
    $notice = is_wp_error($result)
        ? "Revision paused: " . $result->get_error_message()
        : "Approved revision applied to article " . $result . ". URL and publication date retained.";
    set_transient("vp_article_revision_notice_" . get_current_user_id(), $notice, HOUR_IN_SECONDS);
    wp_safe_redirect(admin_url("tools.php?page=valon-article-revisions&source_id=" . $source_id . "&draft_id=" . $draft_id));
    exit();
});
