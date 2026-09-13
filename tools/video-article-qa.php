<?php
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Local checks only.");
}
$passed = 0;
$check = function ($condition, $label) use (&$passed) {
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $passed++;
    WP_CLI::line("PASS " . $label);
};
wp_set_current_user(1);
$fixture = [
    [
        "video_id" => "9999999999999999999",
        "source_url" =>
            "https://www.tiktok.com/@valon_asani/video/9999999999999999999",
        "caption" => "Original caption",
        "cover" => "valon-reading",
        "topic" => "personal-growth",
        "editions" => array_fill_keys(
            ["en", "sq", "de"],
            [
                "title" => "Fixture",
                "slug" => "video-qa-fixture",
                "content" => "<h2>A useful idea</h2><p>Editorial fixture.</p>",
                "excerpt" => "A fixture.",
            ],
        ),
    ],
];
$created = [];
try {
    $bad = $fixture;
    $bad[0]["source_url"] =
        "https://www.tiktok.com/@other/video/9999999999999999999";
    $check(
        is_wp_error(vp_import_video_drafts($bad)),
        "Reject another creator before writing",
    );
    $bad = $fixture;
    unset($bad[0]["editions"]["sq"]);
    $check(
        is_wp_error(vp_import_video_drafts($bad)),
        "Require all three editions",
    );
    $bad = $fixture;
    $bad[0]["editions"]["de"]["content"] = "<p>groß</p>";
    $check(
        is_wp_error(vp_import_video_drafts($bad)),
        "Require Swiss German ss spelling",
    );
    wp_set_current_user(0);
    $check(
        is_wp_error(vp_import_video_drafts($fixture)),
        "Anonymous import rejected",
    );
    wp_set_current_user(1);
    $bad = $fixture;
    $bad[0]["source_type"] = "photo";
    $check(is_wp_error(vp_validate_video_batch($bad)), "Reject a source type and URL mismatch");
    $bad = $fixture;
    $bad[0]["audio_language"] = "invented";
    $check(is_wp_error(vp_validate_video_batch($bad)), "Reject unsupported audio labels");
    $r = vp_import_video_drafts($fixture);
    $check(!is_wp_error($r) && count($r) === 3, "Create three editions");
    $created = array_column($r, "id");
    $old_query = $GLOBALS["wp_query"];
    $old_post = $GLOBALS["post"] ?? null;
    $GLOBALS["wp_query"] = new WP_Query([
        "p" => $created[0],
        "post_type" => "post",
        "post_status" => "draft",
        "lang" => "",
    ]);
    $GLOBALS["wp_query"]->is_preview = true;
    $GLOBALS["post"] = get_post($created[0]);
    ob_start();
    valon_languages();
    $preview_nav = ob_get_clean();
    $check(
        substr_count($preview_nav, "preview=true") === 3,
        "Authorized preview navigation links all draft editions",
    );
    $check(
        apply_filters("noyarpp", false) === true,
        "Video journey suppresses the unlocalized related-post block",
    );
    wp_set_current_user(0);
    ob_start();
    valon_languages();
    $public_nav = ob_get_clean();
    $check(
        !str_contains($public_nav, "preview=true"),
        "Anonymous navigation never exposes draft previews",
    );
    wp_set_current_user(1);
    $GLOBALS["wp_query"] = $old_query;
    $GLOBALS["post"] = $old_post;
    foreach ($r as $entry) {
        $id = $entry["id"];
        $check(
            get_post_status($id) === "draft",
            "Edition remains a draft: " . $entry["language"],
        );
        $check(
            pll_get_post_language($id) === $entry["language"],
            "Language assigned: " . $entry["language"],
        );
        $check(
            count(pll_get_post_translations($id)) === 3,
            "Real translation links: " . $entry["language"],
        );
        $player = vp_video_article_player($id);
        $check(
            substr_count($player, "<iframe") === 1 &&
                str_contains($player, "/player/v1/9999999999999999999") &&
                !str_contains($player, 'loading="lazy"'),
            "One immediately discoverable player: " . $entry["language"],
        );
        $check(
            str_contains(valon_article_image($id)["url"], "valon-reading.jpeg"),
            "Owned editorial cover: " . $entry["language"],
        );
        wp_update_post(["ID" => $id, "post_status" => "publish"]);
        $check(
            get_post_status($id) === "draft",
            "Approval guard prevents publishing: " . $entry["language"],
        );
    }
    $r2 = vp_import_video_drafts($fixture);
    $check(
        array_column($r2, "id") === $created &&
            array_unique(array_column($r2, "action")) === ["kept"],
        "Repeat import is idempotent",
    );
    wp_update_post([
        "ID" => $created[0],
        "post_content" => "<p>Owner edited this.</p>",
    ]);
    vp_import_video_drafts($fixture);
    $check(
        get_post_field("post_content", $created[0]) ===
            "<p>Owner edited this.</p>",
        "Repeat import preserves owner edits",
    );
    update_post_meta($created[0], "_vp_video_id", '123" onload="alert(1)');
    $check(
        vp_video_article_player($created[0]) === "",
        "Invalid player identifier cannot inject markup",
    );
    add_option("vp_video_import_lock", time(), " ", false);
    $check(
        is_wp_error(vp_import_video_drafts($fixture)),
        "Concurrent import rejected",
    );
    delete_option("vp_video_import_lock");
    // Partial group recovery: remove only an isolated QA fixture edition.
    wp_delete_post($created[2], true);
    $r3 = vp_import_video_drafts($fixture);
    $created = array_unique(array_merge($created, array_column($r3, "id")));
    $check(
        count(pll_get_post_translations($r3[0]["id"])) === 3,
        "Retry repairs an interrupted translation group",
    );
    $photo = $fixture;
    $photo[0]["video_id"] = "9999999999999999998";
    $photo[0]["source_type"] = "photo";
    $photo[0]["audio_language"] = "unknown";
    $photo[0]["source_url"] = "https://www.tiktok.com/@valon_asani/photo/9999999999999999998";
    $photos = vp_import_video_drafts($photo);
    $check(!is_wp_error($photos) && count($photos) === 3, "Import all photo article editions");
    $created = array_merge($created, array_column($photos, "id"));
    foreach ($photos as $entry) {
        $markup = vp_video_article_player($entry["id"]);
        $check(substr_count($markup, "<iframe") === 1 && str_contains($markup, "/photo/9999999999999999998") && str_contains($markup, "muted=1") && !str_contains($markup, "autoplay="), "Photo article has the official gallery, muted audio and correct source: " . $entry["language"]);
        $check(get_post_status($entry["id"]) === "draft", "Photo edition is private until approval: " . $entry["language"]);
    }
    $english = $fixture;
    $english[0]["video_id"] = "9999999999999999997";
    $english[0]["source_url"] = "https://www.tiktok.com/@valon_asani/video/9999999999999999997";
    $english[0]["audio_language"] = "en";
    $english_posts = vp_import_video_drafts($english);
    $created = array_merge($created, array_column($english_posts, "id"));
    $expected = ["en" => "in English.", "sq" => "në anglisht.", "de" => "auf Englisch."];
    foreach ($english_posts as $entry) {
        $check(str_contains(vp_video_article_player($entry["id"]), $expected[$entry["language"]]), "Audio language is accurate: " . $entry["language"]);
        update_post_meta($entry["id"], "_vp_audio_language", "unknown");
        $check(!str_contains(vp_video_article_player($entry["id"]), $expected[$entry["language"]]), "Unverified audio language is not guessed: " . $entry["language"]);
    }
} finally {
    delete_option("vp_video_import_lock");
    foreach ($created as $id) {
        wp_delete_post($id, true);
    }
}
WP_CLI::success($passed . " video article checks passed.");
