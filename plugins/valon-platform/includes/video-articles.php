<?php
defined("ABSPATH") || exit();

/** Manual, owner-sourced articles work independently of the TikTok API. */
function vp_video_copy($en, $sq, $de, $id = 0)
{
    $lang =
        $id && function_exists("pll_get_post_language")
            ? pll_get_post_language($id)
            : "en";
    return ["en" => $en, "sq" => $sq, "de" => $de][$lang] ?? $en;
}

function vp_video_id($post_id)
{
    $id = get_post_meta($post_id, "_vp_video_id", true);
    return is_string($id) && preg_match('/^\d{19}$/D', $id) ? $id : "";
}

function vp_tiktok_source_type($post_id)
{
    return get_post_meta($post_id, "_vp_source_type", true) === "photo"
        ? "photo"
        : "video";
}

function vp_tiktok_source_url($post_id)
{
    $id = vp_video_id($post_id);
    return $id
        ? "https://www.tiktok.com/@valon_asani/" . vp_tiktok_source_type($post_id) . "/" . $id
        : "";
}

function vp_video_article_player($post_id)
{
    $id = vp_video_id($post_id);
    if (!$id) {
        return "";
    }
    $source = vp_tiktok_source_url($post_id);
    $is_photo = vp_tiktok_source_type($post_id) === "photo";
    $label = vp_video_copy(
        "Watch the original video",
        "Shiko videon origjinale",
        "Originalvideo ansehen",
        $post_id,
    );
    if ($is_photo) {
        $label = vp_video_copy("View the original photo post", "Shiko postimin origjinal me foto", "Originalen Fotobeitrag ansehen", $post_id);
    }
    // Old reviewed batches were Albanian; new sources explicitly use unknown until verified.
    $audio = get_post_meta($post_id, "_vp_audio_language", true) ?: "sq";
    $audio_labels = [
        "sq" => ["Original video in Albanian.", "Videoja origjinale është në shqip.", "Originalvideo auf Albanisch."],
        "en" => ["Original video in English.", "Videoja origjinale është në anglisht.", "Originalvideo auf Englisch."],
        "de" => ["Original video in German.", "Videoja origjinale është në gjermanisht.", "Originalvideo auf Deutsch."],
        "unknown" => ["Original video.", "Videoja origjinale.", "Originalvideo."],
    ];
    $audio_label = $audio_labels[$audio] ?? $audio_labels["unknown"];
    $language = ($is_photo ? "" : vp_video_copy($audio_label[0], $audio_label[1], $audio_label[2], $post_id) . " ") . vp_video_copy(
        "The written article below is available in English, Albanian and German.",
        "Artikulli poshtë është në anglisht, shqip dhe gjermanisht.",
        "Den Text darunter gibt es auf Englisch, Albanisch und Deutsch.",
        $post_id,
    );
    // TikTok's official player supports image galleries as well as video posts.
    $parameters = $is_photo
        ? "description=1&amp;music_info=0&amp;muted=1"
        : "description=1&amp;music_info=0&amp;rel=0&amp;autoplay=0";
    // The main article player is present in HTML, with no click required for discovery.
    // Collection-page players continue to load on interaction.
    return '<figure class="video-article-player"><iframe src="https://www.tiktok.com/player/v1/' .
        esc_attr($id) .
        '?' . $parameters . '" title="' .
        esc_attr($label . ": " . get_the_title($post_id)) .
        '" width="360" height="640" allow="fullscreen; encrypted-media" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe><figcaption><strong>' .
        esc_html($label) .
        "</strong><p>" .
        esc_html($language) .
        '</p><a href="' .
        esc_url($source) .
        '" rel="noopener" target="_blank">' .
        esc_html(
            vp_video_copy(
                "Open on TikTok",
                "Hape në TikTok",
                "Auf TikTok öffnen",
                $post_id,
            ),
        ) .
        " ↗</a></figcaption></figure>";
}

/** Validate the entire batch before writing. Content never receives publish status. */
function vp_validate_video_batch($batch)
{
    if (!is_array($batch) || !$batch || count($batch) > 10) {
        return new WP_Error("batch", "Provide one to ten video groups.");
    }
    $seen = [];
    foreach ($batch as $group) {
        if (
            !is_array($group) ||
            !preg_match('/^\d{19}$/D', (string) ($group["video_id"] ?? "")) ||
            isset($seen[$group["video_id"]])
        ) {
            return new WP_Error(
                "video",
                "Video IDs must be unique, 19-digit TikTok IDs.",
            );
        }
        $seen[$group["video_id"]] = true;
        $source_type = $group["source_type"] ?? "video";
        if (!in_array($source_type, ["video", "photo"], true)) {
            return new WP_Error("source_type", "Choose a video or photo source.");
        }
        if (!in_array($group["audio_language"] ?? "sq", ["en", "sq", "de", "unknown"], true)) {
            return new WP_Error("audio_language", "Choose a verified audio language or unknown.");
        }
        if (
            ($group["source_url"] ?? "") !==
            "https://www.tiktok.com/@valon_asani/" . $source_type . "/" . $group["video_id"]
        ) {
            return new WP_Error(
                "owner",
                "Only the owner’s matching TikTok source URLs are accepted.",
            );
        }
        if (
            !is_string($group["caption"] ?? null) ||
            !trim($group["caption"]) ||
            strlen($group["caption"]) > 12000
        ) {
            return new WP_Error(
                "caption",
                "A verified original caption is required.",
            );
        }
        if (
            !in_array(
                $group["cover"] ?? "",
                [
                    "valon-reading",
                    "valon-travel",
                    "valon-dua",
                    "valon-portrait",
                ],
                true,
            )
        ) {
            return new WP_Error(
                "cover",
                "Choose an existing owned editorial cover.",
            );
        }
        if (
            !in_array(
                $group["topic"] ?? "",
                [
                    "business-technology",
                    "personal-growth",
                    "life-between-cultures",
                    "relationships",
                ],
                true,
            )
        ) {
            return new WP_Error("topic", "Choose an existing topic.");
        }
        if (
            !is_array($group["editions"] ?? null) ||
            count($group["editions"]) !== 3
        ) {
            return new WP_Error(
                "editions",
                "English, Albanian and German editions are required.",
            );
        }
        foreach (["en", "sq", "de"] as $lang) {
            $edition = $group["editions"][$lang] ?? [];
            foreach (["title", "slug", "content", "excerpt"] as $field) {
                if (
                    !is_string($edition[$field] ?? null) ||
                    !trim($edition[$field])
                ) {
                    return new WP_Error(
                        "edition",
                        "Each edition needs a title, slug, content and excerpt.",
                    );
                }
            }
            if (
                strlen($edition["content"]) > 60000 ||
                strlen($edition["title"]) > 300 ||
                strlen($edition["excerpt"]) > 1000 ||
                !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $edition["slug"])
            ) {
                return new WP_Error(
                    "length",
                    "Edition length or slug is invalid.",
                );
            }
            if ($lang === "de" && str_contains(implode(" ", $edition), "ß")) {
                return new WP_Error(
                    "spelling",
                    "Use Swiss Standard German spelling (ss).",
                );
            }
        }
    }
    if (
        !function_exists("pll_set_post_language") ||
        array_diff(["en", "sq", "de"], pll_languages_list())
    ) {
        return new WP_Error(
            "languages",
            "Configure English, Albanian and German in Polylang first.",
        );
    }
    return true;
}

function vp_import_video_drafts($batch)
{
    if (
        !current_user_can("manage_options") ||
        !current_user_can("edit_posts")
    ) {
        return new WP_Error("permission", "Administrator access is required.");
    }
    $valid = vp_validate_video_batch($batch);
    if (is_wp_error($valid)) {
        return $valid;
    }
    // Prevent two simultaneous submissions from creating duplicate translations.
    if (!add_option("vp_video_import_lock", time(), "", false)) {
        return new WP_Error(
            "busy",
            "Another import is in progress. Try again after it finishes.",
        );
    }
    $result = [];
    try {
        foreach ($batch as $group) {
            $translations = [];
            foreach (["en", "sq", "de"] as $lang) {
                $key = $group["video_id"] . ":" . $lang;
                $existing = get_posts([
                    "post_type" => "post",
                    "post_status" => [
                        "draft",
                        "pending",
                        "publish",
                        "future",
                        "private",
                        "trash",
                    ],
                    "posts_per_page" => 1,
                    "meta_key" => "_vp_video_key",
                    "meta_value" => $key,
                    "lang" => "",
                    "suppress_filters" => true,
                ]);
                if ($existing) {
                    // Never overwrite editorial work, approved content, or trashed drafts.
                    $result[] = [
                        "id" => $existing[0]->ID,
                        "language" => $lang,
                        "action" => "kept",
                    ];
                    if (
                        $existing[0]->post_status !== "trash" &&
                        pll_get_post_language($existing[0]->ID) === $lang
                    ) {
                        $translations[$lang] = $existing[0]->ID;
                    }
                    continue;
                }
                $edition = $group["editions"][$lang];
                $category = get_term_by("slug", $group["topic"], "category");
                if ($category && function_exists("pll_get_term")) {
                    $category = get_term(
                        pll_get_term($category->term_id, $lang) ?:
                        $category->term_id,
                        "category",
                    );
                }
                $id = wp_insert_post(
                    wp_slash([
                        "post_type" => "post",
                        "post_status" => "draft",
                        "post_author" => get_current_user_id(),
                        "post_title" => sanitize_text_field($edition["title"]),
                        "post_name" => $edition["slug"],
                        "post_content" => wp_kses_post($edition["content"]),
                        "post_excerpt" => sanitize_textarea_field(
                            $edition["excerpt"],
                        ),
                        "post_category" =>
                            $category && !is_wp_error($category)
                                ? [$category->term_id]
                                : [],
                        "meta_input" => [
                            "_vp_requires_review" => "1",
                            "_vp_video_key" => $key,
                            "_vp_video_id" => $group["video_id"],
                            "_vp_source_type" => $group["source_type"] ?? "video",
                            "_vp_audio_language" => $group["audio_language"] ?? "sq",
                            "_vp_video_caption" => sanitize_textarea_field(
                                $group["caption"],
                            ),
                            "_vp_video_cover" => $group["cover"],
                            "_vp_video_review_note" =>
                                "Caption-based editorial draft. Review the original post, audio language and all factual claims before approving. The editorial cover is an owned website image, not a verified TikTok thumbnail. No VideoObject is emitted.",
                            "_yoast_wpseo_metadesc" => sanitize_textarea_field(
                                $edition["excerpt"],
                            ),
                        ],
                    ]),
                    true,
                );
                if (is_wp_error($id)) {
                    return $id;
                }
                pll_set_post_language($id, $lang);
                $translations[$lang] = $id;
                $result[] = [
                    "id" => $id,
                    "language" => $lang,
                    "action" => "created",
                ];
            }
            // Recover interrupted groups without replacing an editor's translation assignments.
            if (count($translations) === 3) {
                $safe = true;
                foreach ($translations as $id) {
                    foreach (
                        pll_get_post_translations($id)
                        as $lang => $translated_id
                    ) {
                        if (($translations[$lang] ?? 0) !== $translated_id) {
                            $safe = false;
                        }
                    }
                }
                if (!$safe) {
                    continue;
                }
                pll_save_post_translations($translations);
                foreach (["sq", "de"] as $lang) {
                    update_post_meta(
                        $translations[$lang],
                        "_vp_source_article",
                        $translations["en"],
                    );
                }
            }
        }
    } finally {
        delete_option("vp_video_import_lock");
    }
    return $result;
}

add_action("admin_menu", function () {
    add_management_page(
        "Video article drafts",
        "Video article drafts",
        "manage_options",
        "valon-video-drafts",
        "vp_video_drafts_page",
    );
});
function vp_video_drafts_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }
    echo '<div class="wrap"><h1>Video article drafts</h1><p>Import owner-sourced TikTok articles in English, Albanian and Swiss Standard German. Existing articles are never overwritten. Everything remains a draft until editorial approval.</p>';
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        check_admin_referer("vp_import_video_drafts");
        $raw = wp_unslash($_POST["video_batch"] ?? "");
        $result =
            !is_string($raw) || strlen($raw) > 1000000
                ? new WP_Error("size", "Batch exceeds the size limit.")
                : vp_import_video_drafts(json_decode($raw, true));
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' .
                esc_html($result->get_error_message()) .
                "</p></div>";
        } else {
            echo '<div class="notice notice-success"><p>Processed ' .
                count($result) .
                " editions. New editions saved as drafts; existing editions retained.</p></div>";
        }
    }
    echo '<form method="post">';
    wp_nonce_field("vp_import_video_drafts");
    echo '<label for="video-batch"><strong>Video article JSON</strong></label><p><textarea id="video-batch" name="video_batch" rows="10" class="large-text code" required></textarea></p>';
    submit_button("Import video drafts");
    echo '</form><h2>Ready for editorial review</h2><table class="widefat striped"><thead><tr><th>Article</th><th>Language</th><th>Status</th><th>Review</th></tr></thead><tbody>';
    $posts = get_posts([
        "post_type" => "post",
        "post_status" => ["draft", "pending", "publish", "private"],
        "posts_per_page" => 100,
        "meta_key" => "_vp_video_id",
        "lang" => "",
        "suppress_filters" => true,
    ]);
    foreach ($posts as $post) {
        echo "<tr><td>" .
            esc_html($post->post_title) .
            "</td><td>" .
            esc_html(pll_get_post_language($post->ID)) .
            "</td><td>" .
            esc_html($post->post_status) .
            '</td><td><a href="' .
            esc_url(get_edit_post_link($post->ID)) .
            '">Edit</a> · <a href="' .
            esc_url(get_preview_post_link($post)) .
            '">Preview</a></td></tr>';
    }
    echo "</tbody></table></div>";
}
add_action("add_meta_boxes_post", function ($post) {
    if (!vp_video_id($post->ID)) {
        return;
    }
    add_meta_box(
        "vp-video-source",
        "Video source and editorial review",
        function ($post) {
            echo "<p>" .
                esc_html(
                    get_post_meta($post->ID, "_vp_video_review_note", true),
                ) .
                '</p><p><a href="' .
                esc_url(vp_tiktok_source_url($post->ID)) .
                '" target="_blank" rel="noopener">Original TikTok post ↗</a></p><h4>Original caption</h4><p>' .
                nl2br(
                    esc_html(
                        get_post_meta($post->ID, "_vp_video_caption", true),
                    ),
                ) .
                "</p>";
        },
    );
});
