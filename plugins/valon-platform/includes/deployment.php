<?php
defined("ABSPATH") || exit();

// A bounded migration UI for hosts without SSH. No executable files or arbitrary options are accepted.
add_action("admin_menu", function () {
    add_management_page(
        "Valon website launch",
        "Valon website launch",
        "manage_options",
        "valon-launch",
        "vp_launch_page",
    );
});

function vp_launch_validate($bundle)
{
    $routes = [
        "home",
        "start",
        "writing",
        "watch",
        "about",
        "newsletter",
        "sample-letter",
        "now",
        "media",
        "contact",
        "privacy-policy",
        "check-inbox",
        "welcome",
        "unsubscribed",
    ];
    if (
        !is_array($bundle) ||
        !isset($bundle["pages"]) ||
        !is_array($bundle["pages"])
    ) {
        throw new RuntimeException("Expected a core-page JSON bundle.");
    }
    foreach ($bundle["pages"] as $route => $languages) {
        if (!in_array($route, $routes, true) || !is_array($languages)) {
            throw new RuntimeException("Unknown core route.");
        }
        foreach ($languages as $lang => $item) {
            if (
                !in_array($lang, ["en", "sq", "de"], true) ||
                !is_array($item)
            ) {
                throw new RuntimeException("Unsupported language.");
            }
            foreach (["slug", "title", "content", "description"] as $field) {
                if (!isset($item[$field]) || !is_string($item[$field])) {
                    throw new RuntimeException("Incomplete core page.");
                }
            }
            if (
                !$item["slug"] ||
                sanitize_title($item["slug"]) !== $item["slug"] ||
                strlen($item["content"]) > 150000
            ) {
                throw new RuntimeException("Invalid page slug or size.");
            }
            // Preserve Gutenberg comments, but reject scripts, event handlers and unsafe URLs.
            $bundle["pages"][$route][$lang]["content"] = wp_kses_post(
                $item["content"],
            );
            $bundle["pages"][$route][$lang]["title"] = sanitize_text_field(
                $item["title"],
            );
            $bundle["pages"][$route][$lang][
                "description"
            ] = sanitize_text_field($item["description"]);
        }
    }
    foreach (["en", "sq", "de"] as $lang) {
        foreach (
            [
                "home",
                "start",
                "writing",
                "watch",
                "about",
                "newsletter",
                "contact",
            ]
            as $route
        ) {
            if (empty($bundle["pages"][$route][$lang])) {
                throw new RuntimeException("Missing core language journey.");
            }
        }
    }
    foreach ($bundle["topics"] ?? [] as $topic) {
        if (
            !is_array($topic) ||
            empty($topic["slug"]) ||
            !isset(vp_migration_topics()[$topic["topic"] ?? ""])
        ) {
            throw new RuntimeException("Invalid archive topic.");
        }
    }
    return ["pages" => $bundle["pages"], "topics" => $bundle["topics"] ?? []];
}

function vp_launch_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }
    echo '<div class="wrap"><h1>Valon website launch</h1><p>Stage reviewed English, Albanian and Swiss Standard German core pages. Existing articles and URLs are preserved.</p>';
    $notice = get_transient("vp_launch_notice_" . get_current_user_id());
    if ($notice) {
        echo '<div class="notice notice-info"><p>' .
            esc_html($notice) .
            "</p></div>";
    }
    echo "<p>PHP: " .
        esc_html(PHP_VERSION) .
        " · Polylang: " .
        (function_exists("PLL") ? "active" : "not active") .
        " · Theme: " .
        esc_html(wp_get_theme()->get("Name")) .
        "</p>";
    $owner = (int) get_option("vp_editorial_owner");
    if ($owner && $owner !== get_current_user_id()) {
        echo "<p>Only the configured editorial owner can deploy reviewed pages.</p></div>";
        return;
    }
    foreach (
        [
            "languages" => "1. Prepare three languages",
            "stage" => "2. Stage core-page bundle",
            "publish" => "3. Publish reviewed core pages",
        ]
        as $step => $label
    ) {
        echo "<h2>" .
            esc_html($label) .
            '</h2><form method="post" enctype="multipart/form-data" action="' .
            esc_url(admin_url("admin-post.php")) .
            '"><input type="hidden" name="action" value="vp_launch"><input type="hidden" name="step" value="' .
            esc_attr($step) .
            '">';
        wp_nonce_field("vp_launch_" . $step);
        if ($step === "stage") {
            echo '<p>The included launch copy contains the reviewed core pages in all three languages. Original articles remain in the existing database.</p><p><label>Optional replacement JSON bundle <input name="bundle" type="file" accept="application/json,.json"></label></p>';
        }
        if ($step === "publish") {
            echo '<p><label><input type="checkbox" name="reviewed" value="1" required> I approve the staged core pages listed below for publication.</label></p><p><label><input type="checkbox" name="backup" value="1" required> A complete pre-launch backup is available and its archives have been verified.</label></p>';
        }
        submit_button($label, $step === "publish" ? "primary" : "secondary");
        echo "</form>";
    }
    $ids = get_option("vp_launch_staged_ids", []);
    if ($ids) {
        echo '<h2>Staged core pages</h2><table class="widefat striped"><tr><th>Language</th><th>Page</th><th>Status</th><th>Preview</th></tr>';
        foreach ($ids as $id) {
            $p = get_post($id);
            if (!$p) {
                continue;
            }
            echo "<tr><td>" .
                esc_html(pll_get_post_language($id)) .
                '</td><td><a href="' .
                esc_url(get_edit_post_link($id)) .
                '">' .
                esc_html($p->post_title) .
                "</a></td><td>" .
                esc_html($p->post_status) .
                '</td><td><a target="_blank" rel="noopener" href="' .
                esc_url(get_preview_post_link($id)) .
                '">Preview</a></td></tr>';
        }
        echo "</table>";
    }
    echo "<p>Article translations and newsletter campaigns are never published by this tool. The uploaded JSON is processed privately and is not added to the public media library.</p></div>";
}

function vp_launch_run($step, $bundle = null)
{
    if (!current_user_can("manage_options")) {
        throw new RuntimeException("Administrator access required.");
    }
    $owner = (int) get_option("vp_editorial_owner");
    if ($owner && $owner !== get_current_user_id()) {
        throw new RuntimeException("Editorial owner access required.");
    }
    if (!function_exists("PLL")) {
        throw new RuntimeException("Install and activate Polylang first.");
    }
    if (!$owner) {
        update_option("vp_editorial_owner", get_current_user_id(), false);
    }
    $migration = new VP_Migration($bundle ?? []);
    if ($step === "languages") {
        $migration->languages();
        return "Three languages prepared. Continue with the reviewed bundle in this new request.";
    }
    if ($step === "stage") {
        $bundle = vp_launch_validate($bundle);
        $migration = new VP_Migration($bundle);
        foreach (["en", "sq", "de"] as $lang) {
            if (!PLL()->model->get_language($lang)) {
                throw new RuntimeException("Prepare languages first.");
            }
        }
        // Tag previously unassigned original content, without changing its body, dates or URL.
        foreach (
            get_posts([
                "post_type" => ["post", "page"],
                "post_status" => "any",
                "numberposts" => -1,
                "suppress_filters" => true,
                "lang" => "",
            ])
            as $p
        ) {
            if (!pll_get_post_language($p->ID)) {
                pll_set_post_language($p->ID, "en");
            }
        }
        foreach (
            get_terms([
                "taxonomy" => ["category", "post_tag"],
                "hide_empty" => false,
                "lang" => "",
            ])
            as $term
        ) {
            if (!pll_get_term_language($term->term_id)) {
                pll_set_term_language($term->term_id, "en");
            }
        }
        $migration->scaffold([], []);
        $ids = [];
        $hashes = [];
        foreach (get_option("valon_pages", []) as $lang => $routes) {
            foreach ($routes as $route => $id) {
                if (!isset($bundle["pages"][$route][$lang])) {
                    continue;
                }
                $draft =
                    (int) get_post_meta($id, "_vp_replacement_draft", true) ?:
                    $id;
                $p = get_post($draft);
                if ($p && $p->post_status === "draft") {
                    $ids[] = $draft;
                    $hashes[$draft] = vp_review_hash(
                        $p->post_title,
                        $p->post_content,
                    );
                }
            }
        }
        update_option("vp_launch_staged_ids", $ids, false);
        update_option("vp_launch_staged_hashes", $hashes, false);
        update_option("vp_launch_topics", $bundle["topics"], false);
        return count($ids) .
            " core pages staged as drafts. Existing published page content is unchanged.";
    }
    if ($step === "publish") {
        $ids = get_option("vp_launch_staged_ids", []);
        $hashes = get_option("vp_launch_staged_hashes", []);
        if (!$ids) {
            throw new RuntimeException("No staged pages.");
        }
        foreach ($ids as $id) {
            $p = get_post($id);
            if (
                !$p ||
                $p->post_type !== "page" ||
                $p->post_status !== "draft" ||
                ($hashes[$id] ?? "") !==
                    vp_review_hash($p->post_title, $p->post_content)
            ) {
                throw new RuntimeException(
                    "A staged page changed. Stage and review the bundle again.",
                );
            }
        }
        // Record the exact existing state alongside normal WordPress revisions and the full backup.
        $snapshot = [
            "time" => gmdate("c"),
            "map" => get_option("valon_pages", []),
            "front" => get_option("page_on_front"),
            "show" => get_option("show_on_front"),
            "pages" => [],
        ];
        foreach ($ids as $id) {
            $target = (int) get_post_meta($id, "_vp_replaces", true);
            if ($target) {
                $snapshot["pages"][$target] = get_post($target, ARRAY_A);
            }
        }
        update_option("vp_launch_previous_state", $snapshot, false);
        foreach ($ids as $id) {
            update_post_meta($id, "_vp_approved_hash", $hashes[$id]);
            update_post_meta($id, "_vp_approved_by", get_current_user_id());
        }
        // Restrict this publication to the current reviewed bundle, even if other drafts were approved earlier.
        $migration->promote_pages([], ["apply" => true, "only" => $ids]);
        $topics = get_option("vp_launch_topics", []);
        if ($topics) {
            (new VP_Migration(["topics" => $topics]))->topics(
                [],
                ["apply" => true],
            );
        }
        update_option("blogname", "Valon Asani");
        update_option("blogdescription", "Ideas for a life of your own.");
        update_option("vp_launch_completed", gmdate("c"), false);
        delete_option("vp_launch_staged_ids");
        delete_option("vp_launch_staged_hashes");
        return "Reviewed core pages published in all three languages. Existing articles and original English URLs retained.";
    }
    throw new RuntimeException("Unknown launch step.");
}

add_action("admin_post_vp_launch", function () {
    if (!current_user_can("manage_options")) {
        wp_die("Forbidden", "", ["response" => 403]);
    }
    $step = sanitize_key($_POST["step"] ?? "");
    check_admin_referer("vp_launch_" . $step);
    try {
        $bundle = null;
        if ($step === "stage") {
            $file = $_FILES["bundle"] ?? [];
            if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                $bundle = vp_launch_validate(
                    require VP_DIR . "/includes/launch-copy.php",
                );
            } else {
                if (
                    ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
                    ($file["size"] ?? 0) > 2000000 ||
                    !is_uploaded_file($file["tmp_name"] ?? "")
                ) {
                    throw new RuntimeException(
                        "Upload a JSON bundle smaller than 2 MB.",
                    );
                }
                $bundle = vp_launch_validate(
                    json_decode(file_get_contents($file["tmp_name"]), true),
                );
            }
        }
        if (
            $step === "publish" &&
            (($_POST["reviewed"] ?? "") !== "1" ||
                ($_POST["backup"] ?? "") !== "1")
        ) {
            throw new RuntimeException(
                "Review and verified backup are required.",
            );
        }
        $notice = vp_launch_run($step, $bundle);
    } catch (Throwable $e) {
        $notice = "Launch paused: " . $e->getMessage();
    }
    set_transient(
        "vp_launch_notice_" . get_current_user_id(),
        $notice,
        HOUR_IN_SECONDS,
    );
    wp_safe_redirect(admin_url("tools.php?page=valon-launch"));
    exit();
});
