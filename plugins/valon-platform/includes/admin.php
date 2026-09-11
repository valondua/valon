<?php
defined("ABSPATH") || exit();
add_action("admin_menu", function () {
    add_submenu_page(
        "edit.php?post_type=valon_social",
        "Connections & review",
        "Connections & review",
        "manage_options",
        "valon-connections",
        "vp_admin_page",
    );
});
function vp_admin_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }
    echo '<div class="wrap"><h1>Valon Platform</h1><p>Only your own connected public content enters the feed. Articles and translations require editorial approval.</p><table class="widefat striped"><thead><tr><th>Source</th><th>Status</th><th>Last successful sync</th><th>Action</th></tr></thead><tbody>';
    foreach (["tiktok", "instagram", "facebook"] as $p) {
        $c = VP_Social::config($p);
        $s = get_option("vp_status_" . $p, []);
        $ready = $c["token"] && $c["id"];
        echo "<tr><td>" .
            esc_html(ucfirst($p)) .
            "</td><td>" .
            esc_html(
                $ready
                    ? $s["message"] ?? "Configured; not verified yet."
                    : "Not connected — configure server credentials.",
            ) .
            "</td><td>" .
            esc_html(
                !empty($s["last_success"])
                    ? wp_date("Y-m-d H:i", $s["last_success"])
                    : "Never",
            ) .
            "</td><td>";
        echo '<form method="post" action="' .
            esc_url(admin_url("admin-post.php")) .
            '"><input type="hidden" name="action" value="vp_sync"><input type="hidden" name="platform" value="' .
            esc_attr($p) .
            '">';
        wp_nonce_field("vp_sync");
        submit_button("Sync now", "secondary", "submit", false, [
            "disabled" => !$ready,
        ]);
        echo "</form></td></tr>";
    }
    echo "</tbody></table><h2>Scheduler</h2><p>" .
        (defined("DISABLE_WP_CRON") && DISABLE_WP_CRON
            ? "WordPress traffic-triggered cron is disabled. Configure a system scheduler to run wp cron event run --due-now every minute."
            : "Configure a system scheduler and DISABLE_WP_CRON so imports do not depend on traffic.") .
        "</p><h2>Newsletter</h2><p>" .
        (vp_secret("VP_MAILCHIMP_API_KEY") && vp_secret("VP_MAILCHIMP_LIST_ID")
            ? "Server credentials configured; delivery still needs an authorized end-to-end test."
            : "Mailchimp is not connected. Signup remains unavailable until the personal audience and server credentials are configured.") .
        "</p>";
    echo '<h2>Add a selected post</h2><form method="post" action="' .
        esc_url(admin_url("admin-post.php")) .
        '"><input type="hidden" name="action" value="vp_add_social">';
    wp_nonce_field("vp_add_social");
    echo '<p><label>Platform <select name="platform"><option value="linkedin">LinkedIn</option><option value="x">X</option><option value="tiktok">TikTok</option></select></label></p><p><label>Public post URL <input type="url" name="url" required class="regular-text"></label></p><p><label>Caption in your own words<br><textarea name="caption" rows="3" cols="70"></textarea></label></p><p><label>Original publication date <input type="date" name="published_at" required></label></p><p><label>Language <select name="language"><option value="sq">Shqip</option><option value="en">English</option></select></label></p><p><label><input type="checkbox" name="owned" value="1" required> I confirm this is Valon’s own public post.</label></p>';
    submit_button("Add selected post");
    echo "</form>";
    echo "<h2>Awaiting your review</h2><p>Approvals are restricted to the configured editorial owner. Approving unlocks the normal WordPress publish action; it does not publish automatically.</p>";
    $drafts = get_posts([
        "post_type" => ["post", "page"],
        "post_status" => ["draft", "pending"],
        "numberposts" => 100,
        "meta_key" => "_vp_requires_review",
        "meta_value" => "1",
        "suppress_filters" => true,
    ]);
    $ordinary = get_posts([
        "post_type" => "post",
        "post_status" => ["draft", "pending"],
        "numberposts" => 100,
        "suppress_filters" => true,
    ]);
    $drafts = array_values(
        array_column(array_merge($drafts, $ordinary), null, "ID"),
    );
    foreach ($drafts as $d) {
        echo '<p><a href="' .
            esc_url(get_edit_post_link($d->ID)) .
            '">' .
            esc_html($d->post_title) .
            "</a> ";
        if (vp_can_approve()) {
            echo '<a class="button" href="' .
                esc_url(
                    wp_nonce_url(
                        admin_url(
                            "admin-post.php?action=vp_approve&id=" . $d->ID,
                        ),
                        "vp_approve_" . $d->ID,
                    ),
                ) .
                '">Approve reviewed content</a>';
        }
        echo "</p>";
    }
    echo "</div>";
}
function vp_can_approve()
{
    return current_user_can("manage_options") &&
        get_current_user_id() === (int) get_option("vp_editorial_owner", 0);
}
add_action("admin_post_vp_sync", function () {
    if (!current_user_can("manage_options")) {
        wp_die("Forbidden", 403);
    }
    check_admin_referer("vp_sync");
    VP_Social::sync(sanitize_key($_POST["platform"] ?? ""), true);
    wp_safe_redirect(
        admin_url("edit.php?post_type=valon_social&page=valon-connections"),
    );
    exit();
});
add_action("admin_post_vp_add_social", function () {
    if (!current_user_can("manage_options")) {
        wp_die("Forbidden", 403);
    }
    check_admin_referer("vp_add_social");
    if (empty($_POST["owned"])) {
        wp_die("Ownership confirmation required.");
    }
    $p = sanitize_key($_POST["platform"] ?? "");
    $url = esc_url_raw(wp_unslash($_POST["url"] ?? ""));
    if (!in_array($p, ["linkedin", "x", "tiktok"], true)) {
        wp_die("Unsupported manual source.");
    }
    $date = sanitize_text_field($_POST["published_at"] ?? "");
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        wp_die("A valid publication date is required.");
    }
    $url = preg_replace('/[?#].*$/', "", $url);
    $path = trim(wp_parse_url($url, PHP_URL_PATH) ?? "", "/");
    $external = basename($path);
    $id = VP_Social::upsert($p, [
        "id" => $external,
        "url" => $url,
        "caption" => wp_unslash($_POST["caption"] ?? ""),
        "language" => sanitize_key($_POST["language"] ?? "und"),
        "date" => $date . " 00:00:00",
        "type" => "link",
    ]);
    if (is_wp_error($id)) {
        wp_die(esc_html($id->get_error_message()));
    }
    wp_safe_redirect(get_edit_post_link($id, "raw"));
    exit();
});
add_action("add_meta_boxes", function () {
    add_meta_box(
        "vp-social-controls",
        "Feed & editorial controls",
        "vp_social_metabox",
        "valon_social",
        "normal",
        "high",
    );
});
function vp_social_metabox($post)
{
    wp_nonce_field("vp_social_" . $post->ID, "vp_social_nonce");
    echo '<p><a href="' .
        esc_url(get_post_meta($post->ID, "_vp_source_url", true)) .
        '" target="_blank" rel="noopener">Open original post ↗</a></p><p>' .
        nl2br(esc_html(get_post_meta($post->ID, "_vp_caption", true))) .
        "</p>";
    foreach (
        [
            "hidden" => "Hide from website",
            "featured" => "Feature in editorial selection",
        ]
        as $key => $label
    ) {
        echo '<p><label><input type="checkbox" name="vp_' .
            $key .
            '" value="1" ' .
            checked(get_post_meta($post->ID, "_vp_" . $key, true), "1", false) .
            "> " .
            esc_html($label) .
            "</label></p>";
    }
    echo '<p><label>Source language <select name="vp_language">';
    foreach (
        [
            "und" => "Original language (unclassified)",
            "sq" => "Shqip",
            "en" => "English",
        ]
        as $v => $l
    ) {
        echo '<option value="' .
            $v .
            '" ' .
            selected(
                get_post_meta($post->ID, "_vp_language", true),
                $v,
                false,
            ) .
            ">" .
            $l .
            "</option>";
    }
    echo "</select></label></p>";
    echo '<p><label>Related article ID <input type="number" min="0" name="vp_article_id" value="' .
        absint(get_post_meta($post->ID, "_vp_article_id", true)) .
        '"></label></p><p><a class="button" href="' .
        esc_url(
            wp_nonce_url(
                admin_url(
                    "admin-post.php?action=vp_create_draft&id=" . $post->ID,
                ),
                "vp_draft_" . $post->ID,
            ),
        ) .
        '">Create article draft</a></p><p>Drafts start with a source link and an editorial brief. Embed metadata is not used to generate articles.</p>';
}
add_action("save_post_valon_social", function ($id) {
    if (
        empty($_POST["vp_social_nonce"]) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST["vp_social_nonce"])),
            "vp_social_" . $id,
        ) ||
        !current_user_can("edit_post", $id) ||
        wp_is_post_revision($id)
    ) {
        return;
    }
    foreach (["hidden", "featured"] as $key) {
        update_post_meta(
            $id,
            "_vp_" . $key,
            empty($_POST["vp_" . $key]) ? "0" : "1",
        );
    }
    $lang = sanitize_key($_POST["vp_language"] ?? "und");
    update_post_meta(
        $id,
        "_vp_language",
        in_array($lang, ["en", "sq"], true) ? $lang : "und",
    );
    $article = absint($_POST["vp_article_id"] ?? 0);
    if (!$article || get_post_type($article) === "post") {
        update_post_meta($id, "_vp_article_id", $article);
    }
});
function vp_make_draft($id)
{
    if (get_post_type($id) !== "valon_social") {
        return new WP_Error("not_found", "Unknown social post.");
    }
    $existing = absint(get_post_meta($id, "_vp_article_id", true));
    if ($existing && get_post($existing)) {
        return $existing;
    }
    $url = get_post_meta($id, "_vp_source_url", true);
    $post = wp_insert_post(
        [
            "post_type" => "post",
            "post_status" => "draft",
            "post_title" => "Editorial draft — " . get_the_title($id),
            "post_content" =>
                '<!-- wp:paragraph --><p>Source: <a href="' .
                esc_url($url) .
                '">Original social post</a></p><!-- /wp:paragraph -->\n<!-- wp:paragraph --><p>Editorial brief: add Valon’s original recording, reviewed transcript or notes. Develop the idea in his voice, verify factual claims, and request approval before publishing.</p><!-- /wp:paragraph -->',
            "meta_input" => [
                "_vp_requires_review" => "1",
                "_vp_source_social" => $id,
            ],
        ],
        true,
    );
    if (!is_wp_error($post)) {
        update_post_meta($id, "_vp_article_id", $post);
        $lang = get_post_meta($id, "_vp_language", true);
        if (
            function_exists("pll_set_post_language") &&
            in_array($lang, ["en", "sq"], true)
        ) {
            pll_set_post_language($post, $lang);
        }
    }
    return $post;
}
add_action("admin_post_vp_create_draft", function () {
    $id = absint($_GET["id"] ?? 0);
    if (
        !current_user_can("edit_post", $id) ||
        !current_user_can("edit_posts")
    ) {
        wp_die("Forbidden", 403);
    }
    check_admin_referer("vp_draft_" . $id);
    $draft = vp_make_draft($id);
    if (is_wp_error($draft)) {
        wp_die(esc_html($draft->get_error_message()));
    }
    wp_safe_redirect(get_edit_post_link($draft, "raw"));
    exit();
});
function vp_review_hash($title, $content)
{
    return hash("sha256", $title . "\n" . $content);
}
add_action("admin_post_vp_approve", function () {
    $id = absint($_GET["id"] ?? 0);
    if (!vp_can_approve() || !current_user_can("edit_post", $id)) {
        wp_die("Only the editorial owner can approve.", 403);
    }
    check_admin_referer("vp_approve_" . $id);
    $p = get_post($id);
    if (!$p) {
        wp_die("Not found", 404);
    }
    update_post_meta(
        $id,
        "_vp_approved_hash",
        vp_review_hash($p->post_title, $p->post_content),
    );
    update_post_meta($id, "_vp_approved_by", get_current_user_id());
    wp_safe_redirect(get_edit_post_link($id, "raw"));
    exit();
});
add_filter(
    "wp_insert_post_data",
    function ($data, $postarr) {
        $id = absint($postarr["ID"] ?? 0);
        if (
            defined("WP_CLI") &&
            WP_CLI &&
            wp_get_environment_type() === "local" &&
            (!empty($GLOBALS["vp_archive_import"]) ||
                (!empty($GLOBALS["vp_preview_pages"]) &&
                    $data["post_type"] === "page"))
        ) {
            return $data;
        }
        $new_article =
            $data["post_type"] === "post" &&
            (!$id || get_post_status($id) !== "publish");
        $needs =
            $new_article ||
            ($postarr["meta_input"]["_vp_requires_review"] ??
                get_post_meta($id, "_vp_requires_review", true)) ===
                "1";
        if (
            $needs &&
            in_array($data["post_status"], ["publish", "future"], true)
        ) {
            $approved = get_post_meta($id, "_vp_approved_hash", true);
            if (
                !$approved ||
                !hash_equals(
                    $approved,
                    vp_review_hash(
                        wp_unslash($data["post_title"]),
                        wp_unslash($data["post_content"]),
                    ),
                )
            ) {
                $data["post_status"] = "draft";
            }
        }
        return $data;
    },
    10,
    2,
);

add_filter("manage_valon_social_posts_columns", function ($columns) {
    return [
        "cb" => $columns["cb"],
        "title" => "Original caption",
        "vp_platform" => "Platform",
        "vp_published" => "Original date",
        "vp_state" => "Visibility",
        "vp_article" => "Article",
    ];
});
add_action(
    "manage_valon_social_posts_custom_column",
    function ($column, $id) {
        if ($column === "vp_platform") {
            echo esc_html(get_post_meta($id, "_vp_platform", true));
        }
        if ($column === "vp_published") {
            echo esc_html(get_post_meta($id, "_vp_published_at", true));
        }
        if ($column === "vp_state") {
            echo esc_html(
                get_post_meta($id, "_vp_hidden", true) === "1"
                    ? "Hidden"
                    : get_post_meta($id, "_vp_availability", true),
            );
        }
        if ($column === "vp_article") {
            $article = (int) get_post_meta($id, "_vp_article_id", true);
            if ($article && get_post($article)) {
                echo '<a href="' .
                    esc_url(get_edit_post_link($article)) .
                    '">' .
                    esc_html(get_the_title($article)) .
                    "</a>";
            }
        }
    },
    10,
    2,
);
add_action("add_meta_boxes", function () {
    add_meta_box(
        "valon-featured",
        "Homepage selection",
        function ($post) {
            wp_nonce_field(
                "valon_featured_" . $post->ID,
                "valon_featured_nonce",
            );
            echo '<label><input type="checkbox" name="valon_featured" value="1" ' .
                checked(
                    get_post_meta($post->ID, "_valon_featured", true),
                    "1",
                    false,
                ) .
                "> Include in selected writing</label>";
        },
        "post",
        "side",
    );
});
add_action("save_post_post", function ($id) {
    if (
        empty($_POST["valon_featured_nonce"]) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST["valon_featured_nonce"])),
            "valon_featured_" . $id,
        ) ||
        !current_user_can("edit_post", $id) ||
        wp_is_post_revision($id)
    ) {
        return;
    }
    update_post_meta(
        $id,
        "_valon_featured",
        empty($_POST["valon_featured"]) ? "0" : "1",
    );
});

function vp_rest_review_guard($prepared, $request)
{
    if (is_wp_error($prepared)) {
        return $prepared;
    }
    $id = (int) $request->get_param("id");
    $old = $id ? get_post($id) : null;
    $type = $old ? $old->post_type : $prepared->post_type ?? "post";
    $status = $prepared->post_status ?? ($old ? $old->post_status : "draft");
    $title = $prepared->post_title ?? ($old ? $old->post_title : "");
    $content = $prepared->post_content ?? ($old ? $old->post_content : "");
    $needs =
        $type === "post" ||
        ($id && get_post_meta($id, "_vp_requires_review", true) === "1");
    if ($needs && in_array($status, ["publish", "future"], true)) {
        // Unchanged existing archive posts may retain their original publication state.
        $unchanged =
            $old &&
            $old->post_status === "publish" &&
            $old->post_title === $title &&
            $old->post_content === $content;
        $hash = $id ? get_post_meta($id, "_vp_approved_hash", true) : "";
        if (
            !$unchanged &&
            (!$hash || !hash_equals($hash, vp_review_hash($title, $content)))
        ) {
            return new WP_Error(
                "valon_review_required",
                "Save a draft and obtain Valon’s approval in Social queue → Connections & review before publishing. For a live article, prepare a separate draft so the published version stays available.",
                ["status" => 409],
            );
        }
    }
    return $prepared;
}
add_filter("rest_pre_insert_post", "vp_rest_review_guard", 10, 2);
add_filter("rest_pre_insert_page", "vp_rest_review_guard", 10, 2);
