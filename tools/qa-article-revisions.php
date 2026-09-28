<?php
// Local-only integration test for exact approved draft promotion and translation linking.
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Article revision QA is local-only.");
}
$previous_user = get_current_user_id();
$previous_owner = get_option("vp_editorial_owner");
$source_id = 0;
$draft_id = 0;
$translation_ids = [];
$term_ids = [];
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
        "meta_input" => [
            "_yoast_wpseo_title" => "Old SEO title",
            "_yoast_wpseo_opengraph-title" => "Old social title",
            "_yoast_wpseo_metadesc" => "Old description.",
        ],
    ]);
    unset($GLOBALS["vp_archive_import"]);
    $draft_id = wp_insert_post([
        "post_type" => "post",
        "post_status" => "draft",
        "post_title" => "Reviewed article",
        "post_content" => "New copy with evidence.",
        "post_excerpt" => "Reviewed summary.",
        "meta_input" => ["_yoast_wpseo_title" => "Reviewed SEO title"],
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
        get_post_status($draft_id) !== "private" ||
        get_post_meta($source_id, "_yoast_wpseo_title", true) !== "Reviewed SEO title" ||
        get_post_meta($source_id, "_yoast_wpseo_opengraph-title", true) !== "" ||
        get_post_meta($source_id, "_vp_seo_title_synced", true) !== "1" ||
        get_post_meta($source_id, "_yoast_wpseo_metadesc", true) !== "Reviewed summary."
    ) {
        throw new RuntimeException("Revision did not preserve publication identity, sync SEO fields and archive the draft.");
    }
    WP_CLI::success("Article revision approval, URL/date preservation, SEO sync and draft retirement passed.");

    if (function_exists("pll_save_post_translations")) {
        $en_term = wp_insert_term("Revision QA topic", "category");
        $sq_term = wp_insert_term("Tema QA e rishikimit", "category");
        $term_ids = [$en_term["term_id"], $sq_term["term_id"]];
        pll_set_term_language($term_ids[0], "en");
        pll_set_term_language($term_ids[1], "sq");
        pll_save_term_translations(["en" => $term_ids[0], "sq" => $term_ids[1]]);
        wp_set_post_categories($source_id, [$term_ids[0]]);
        foreach (["first", "second"] as $name) {
            $translation_ids[] = wp_insert_post([
                "post_type" => "post",
                "post_status" => "draft",
                "post_title" => "Artikulli i rishikuem " . $name,
                "post_content" => "Teksti i ri.",
            ]);
        }
        if (!is_wp_error(vp_link_article_translation($source_id, $translation_ids[0], "en"))) {
            throw new RuntimeException("A translation was linked in the article's own language.");
        }
        $linked = vp_link_article_translation($source_id, $translation_ids[0], "sq");
        if (is_wp_error($linked)) {
            throw new RuntimeException("Translation did not link: " . $linked->get_error_message());
        }
        if (
            pll_get_post_language($translation_ids[0]) !== "sq" ||
            (int) (pll_get_post_translations($source_id)["sq"] ?? 0) !== $translation_ids[0] ||
            wp_get_post_categories($translation_ids[0]) !== [$term_ids[1]] ||
            get_post_status($translation_ids[0]) !== "draft"
        ) {
            throw new RuntimeException("Translation language, link, category or draft status is wrong.");
        }
        if (!is_wp_error(vp_link_article_translation($source_id, $translation_ids[1], "sq"))) {
            throw new RuntimeException("A second translation replaced the linked one.");
        }
        WP_CLI::success("Translation linking, category mapping and duplicate protection passed.");
    }
} finally {
    foreach (array_merge([$draft_id, $source_id], $translation_ids) as $id) {
        if ($id) {
            wp_delete_post($id, true);
        }
    }
    foreach ($term_ids as $term_id) {
        wp_delete_term($term_id, "category");
    }
    if ($previous_owner === false) {
        delete_option("vp_editorial_owner");
    } else {
        update_option("vp_editorial_owner", $previous_owner);
    }
    wp_set_current_user($previous_user);
}
