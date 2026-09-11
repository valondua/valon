<?php
defined("ABSPATH") || exit();
final class VP_CLI
{
    private function content($name, $assoc = [])
    {
        $dir = $assoc["content-dir"] ?? vp_secret("VP_EDITORIAL_DIR");
        if (!$dir && wp_get_environment_type() === "local") {
            $dir = WP_CONTENT_DIR . "/themes/valon/review/content";
        }
        if (!$dir || !is_readable(rtrim($dir, "/") . "/" . $name . ".json")) {
            WP_CLI::error(
                "Provide --content-dir pointing to the private editorial bundle, stored outside the public web root.",
            );
        }
        $rows = json_decode(
            file_get_contents(rtrim($dir, "/") . "/" . $name . ".json"),
            true,
        );
        if (!is_array($rows)) {
            WP_CLI::error("Invalid editorial manifest.");
        }
        return $rows;
    }

    /** Create the English/Albanian language structure. */
    function languages()
    {
        if (!function_exists("PLL")) {
            WP_CLI::error("Install and activate Polylang first.");
        }
        foreach (
            [
                [
                    "slug" => "en",
                    "name" => "English",
                    "locale" => "en_US",
                    "flag" => "gb",
                ],
                [
                    "slug" => "sq",
                    "name" => "Shqip",
                    "locale" => "sq",
                    "flag" => "al",
                ],
            ]
            as $args
        ) {
            if (!PLL()->model->get_language($args["slug"])) {
                $result = PLL()->model->add_language($args);
                if (is_wp_error($result)) {
                    WP_CLI::error($result->get_error_message());
                }
            }
        }
        $options = get_option("polylang", []);
        $options["default_lang"] = "en";
        $options["hide_default"] = 1;
        $options["force_lang"] = 1;
        $options["rewrite"] = 1;
        $options["redirect_lang"] = 1;
        $options["browser"] = 0;
        update_option("polylang", $options);
        WP_CLI::success(
            "Languages ready. Run scaffold in a fresh CLI process.",
        );
    }
    /** Import the public archive into LOCAL preview only. Usage: wp valon import_public <directory> */
    function import_public($args)
    {
        if (wp_get_environment_type() !== "local") {
            WP_CLI::error(
                "Public import is restricted to local previews. Production retains its database.",
            );
        }
        if (empty($args[0])) {
            WP_CLI::error("Source directory required.");
        }
        $GLOBALS["vp_archive_import"] = true;
        $count = 0;
        foreach (["posts" => "post", "pages" => "page"] as $file => $type) {
            $data = json_decode(
                file_get_contents(rtrim($args[0], "/") . "/" . $file . ".json"),
                true,
            );
            if (!is_array($data)) {
                WP_CLI::error("Invalid archive.");
            }
            foreach ($data as $source) {
                $found = get_posts([
                    "post_type" => $type,
                    "post_status" => "any",
                    "numberposts" => 1,
                    "meta_key" => "_valon_legacy_id",
                    "meta_value" => $source["id"],
                    "fields" => "ids",
                    "suppress_filters" => true,
                ]);
                if ($found) {
                    continue;
                }
                $content = $source["content"]["rendered"];
                $id = wp_insert_post(
                    wp_slash([
                        "post_type" => $type,
                        "post_status" => "publish",
                        "post_name" => $source["slug"],
                        "post_title" => html_entity_decode(
                            $source["title"]["rendered"],
                            ENT_QUOTES,
                            "UTF-8",
                        ),
                        "post_content" => $content,
                        "post_excerpt" => wp_strip_all_tags(
                            $source["excerpt"]["rendered"] ?? "",
                        ),
                        "post_date" => $source["date"],
                        "post_date_gmt" => $source["date_gmt"],
                        "post_modified" => $source["modified"],
                        "post_modified_gmt" => $source["modified_gmt"],
                        "meta_input" => [
                            "_valon_legacy_id" => $source["id"],
                            "_valon_legacy_url" => $source["link"],
                        ],
                    ]),
                    true,
                );
                if (is_wp_error($id)) {
                    WP_CLI::error($id->get_error_message());
                }
                pll_set_post_language($id, "en");
                $media = $source["_embedded"]["wp:featuredmedia"][0] ?? [];
                if (!empty($media["source_url"])) {
                    update_post_meta(
                        $id,
                        "_valon_legacy_image",
                        esc_url_raw($media["source_url"]),
                    );
                }
                $count++;
            }
        }
        WP_CLI::success(
            "Imported " .
                $count .
                " original archive records; slugs and publication dates preserved.",
        );
    }
    /** Stage new pages as drafts. --preview publishes core PAGES only on localhost. */
    function scaffold($args, $assoc)
    {
        if (!function_exists("pll_set_post_language")) {
            WP_CLI::error("Polylang is required.");
        }
        $preview = isset($assoc["preview"]);
        if ($preview && wp_get_environment_type() !== "local") {
            WP_CLI::error("Preview publication is local-only.");
        }
        $GLOBALS["vp_preview_pages"] = $preview;
        $manifest = $this->content("pages", $assoc);
        $map = get_option("valon_pages", []);
        foreach ($manifest as $route => $langs) {
            $translations = [];
            foreach ($langs as $lang => $item) {
                $known = $map[$lang][$route] ?? 0;
                if (!$known) {
                    $existing = get_posts([
                        "post_type" => "page",
                        "post_status" => "any",
                        "name" => $item["slug"],
                        "lang" => $lang,
                        "numberposts" => 1,
                    ]);
                    $known = $existing ? $existing[0]->ID : 0;
                }
                if (
                    $known &&
                    !$preview &&
                    get_post_status($known) === "publish"
                ) {
                    // Preserve the live page; proposed replacement is a separate review draft.
                    $published = $known;
                    $known = (int) get_post_meta(
                        $published,
                        "_vp_replacement_draft",
                        true,
                    );
                } else {
                    $published = 0;
                }
                $content = $item["content"];
                $post = [
                    "post_type" => "page",
                    "post_name" => $item["slug"],
                    "post_title" => $item["title"],
                    "post_content" => $content,
                    "post_status" => $preview ? "publish" : "draft",
                ];
                if ($known) {
                    $post["ID"] = $known;
                }
                $id = wp_insert_post(wp_slash($post), true);
                if (is_wp_error($id)) {
                    WP_CLI::error($id->get_error_message());
                }
                update_post_meta($id, "_valon_route", $route);
                update_post_meta($id, "_vp_requires_review", "1");
                update_post_meta(
                    $id,
                    "_yoast_wpseo_metadesc",
                    $item["description"],
                );
                if ($published) {
                    update_post_meta($published, "_vp_replacement_draft", $id);
                    update_post_meta($id, "_vp_replaces", $published);
                    $map[$lang][$route] = $published;
                } else {
                    $map[$lang][$route] = $id;
                }
                pll_set_post_language($id, $lang);
                $translations[$lang] = $id;
            }
            pll_save_post_translations($translations);
        }
        update_option("valon_pages", $map);
        if ($preview) {
            update_option("show_on_front", "page");
            update_option("page_on_front", $map["en"]["home"]);
            update_option("blogname", "Valon Asani");
            update_option("blogdescription", "Ideas for a life of your own.");
            update_option("blog_public", 0);
            update_option("permalink_structure", "/%postname%/");
            update_option("vp_editorial_owner", 1);
            wp_update_user(["ID" => 1, "display_name" => "Valon Asani"]);
        }
        foreach (valon_topics() as $slug => $labels) {
            $translation = [];
            foreach (["en" => 0, "sq" => 1] as $lang => $n) {
                $s = $slug . ($lang === "sq" ? "-sq" : "");
                $term = get_term_by("slug", $s, "category");
                $id = $term
                    ? $term->term_id
                    : wp_insert_term($labels[$n], "category", ["slug" => $s])[
                        "term_id"
                    ];
                pll_set_term_language($id, $lang);
                $translation[$lang] = $id;
            }
            pll_save_term_translations($translation);
        }
        flush_rewrite_rules();
        WP_CLI::success(
            $preview
                ? "Local bilingual preview pages ready; all article translations remain drafts."
                : "New and replacement pages are staged as drafts. Existing published pages are unchanged.",
        );
    }
    /** Create eight reviewed-source translation drafts, never publish. */
    function translations($args, $assoc)
    {
        $rows = $this->content("translations", $assoc);
        $created = 0;
        foreach ($rows as $row) {
            $source = get_posts([
                "post_type" => "post",
                "name" => $row["source_slug"],
                "numberposts" => 1,
                "lang" => "en",
            ]);
            if (!$source) {
                WP_CLI::warning("Missing source: " . $row["source_slug"]);
                continue;
            }
            $source = $source[0];
            if (pll_get_post($source->ID, "sq")) {
                continue;
            }
            $id = wp_insert_post(
                wp_slash([
                    "post_type" => "post",
                    "post_status" => "draft",
                    "post_title" => $row["title"],
                    "post_name" => $row["slug"],
                    "post_content" => $row["content"],
                    "meta_input" => [
                        "_vp_requires_review" => "1",
                        "_vp_source_article" => $source->ID,
                        "_vp_editorial_note" => $row["review_note"],
                    ],
                ]),
                true,
            );
            if (is_wp_error($id)) {
                WP_CLI::error($id->get_error_message());
            }
            pll_set_post_language($id, "sq");
            pll_save_post_translations(["en" => $source->ID, "sq" => $id]);
            $created++;
        }
        WP_CLI::success(
            $created . " Albanian article drafts prepared for Valon’s review.",
        );
    }

    /** Apply the reviewed topic map; preserves content, slugs and dates. Default is dry run. */
    function topics($args, $assoc)
    {
        $rows = $this->content("topics", $assoc);
        $count = 0;
        foreach ($rows as $row) {
            $source = get_posts([
                "post_type" => "post",
                "post_status" => "publish",
                "name" => $row["slug"],
                "lang" => "en",
                "numberposts" => 1,
            ]);
            if (!$source) {
                continue;
            }
            $post = $source[0];
            $term = get_term_by("slug", $row["topic"], "category");
            if (!$term) {
                WP_CLI::error("Run scaffold to create topic terms first.");
            }
            if (isset($assoc["apply"])) {
                wp_set_post_categories($post->ID, [$term->term_id]);
                update_post_meta(
                    $post->ID,
                    "_valon_featured",
                    $row["feature"] ? "1" : "0",
                );
                $sq = pll_get_post($post->ID, "sq");
                $sqterm = pll_get_term($term->term_id, "sq");
                if ($sq && $sqterm) {
                    wp_set_post_categories($sq, [$sqterm]);
                }
            }
            $count++;
        }
        WP_CLI::success(
            $count .
                " articles " .
                (isset($assoc["apply"])
                    ? "assigned to topics."
                    : "matched. Inspect the private topics.json; pass --apply to assign."),
        );
    }
    /** Apply owner-approved core page drafts, retaining existing published page IDs and slugs. */
    function promote_pages($args, $assoc)
    {
        $owner = (int) get_option("vp_editorial_owner");
        if (!$owner) {
            WP_CLI::error(
                "Set vp_editorial_owner to Valon’s existing admin user ID first.",
            );
        }
        $pages = get_posts([
            "post_type" => "page",
            "post_status" => ["draft", "pending"],
            "numberposts" => 100,
            "meta_key" => "_valon_route",
            "suppress_filters" => true,
        ]);
        $eligible = [];
        foreach ($pages as $p) {
            if (
                (int) get_post_meta($p->ID, "_vp_approved_by", true) !==
                    $owner ||
                get_post_meta($p->ID, "_vp_approved_hash", true) !==
                    vp_review_hash($p->post_title, $p->post_content)
            ) {
                continue;
            }
            $eligible[] = $p;
        }
        if (!isset($assoc["apply"])) {
            foreach ($eligible as $p) {
                WP_CLI::line($p->ID . " " . $p->post_title);
            }
            WP_CLI::success(
                count($eligible) .
                    " owner-approved pages ready. Pass --apply after backup.",
            );
            return;
        }
        $map = get_option("valon_pages", []);
        foreach ($eligible as $p) {
            $route = get_post_meta($p->ID, "_valon_route", true);
            $lang = pll_get_post_language($p->ID);
            $target =
                (int) get_post_meta($p->ID, "_vp_replaces", true) ?: $p->ID;
            if ($target !== $p->ID) {
                update_post_meta($target, "_vp_requires_review", "1");
                update_post_meta(
                    $target,
                    "_vp_approved_hash",
                    vp_review_hash($p->post_title, $p->post_content),
                );
                update_post_meta($target, "_vp_approved_by", $owner);
            }
            $result = wp_update_post(
                wp_slash([
                    "ID" => $target,
                    "post_title" => $p->post_title,
                    "post_content" => $p->post_content,
                    "post_status" => "publish",
                ]),
                true,
            );
            if (
                is_wp_error($result) ||
                get_post_status($target) !== "publish"
            ) {
                WP_CLI::error("Could not apply page " . $p->ID);
            }
            update_post_meta($target, "_valon_route", $route);
            update_post_meta(
                $target,
                "_yoast_wpseo_metadesc",
                get_post_meta($p->ID, "_yoast_wpseo_metadesc", true),
            );
            $map[$lang][$route] = $target;
            if ($target !== $p->ID) {
                wp_update_post(["ID" => $p->ID, "post_status" => "private"]);
            }
        }
        foreach ($map["en"] ?? [] as $route => $en) {
            $sq = $map["sq"][$route] ?? 0;
            if ($sq) {
                pll_save_post_translations(["en" => $en, "sq" => $sq]);
            }
        }
        update_option("valon_pages", $map);
        if (
            !empty($map["en"]["home"]) &&
            get_post_status($map["en"]["home"]) === "publish"
        ) {
            update_option("show_on_front", "page");
            update_option("page_on_front", $map["en"]["home"]);
        }
        flush_rewrite_rules();
        WP_CLI::success(
            "Applied " .
                count($eligible) .
                " owner-approved core pages. No articles or newsletters were published.",
        );
    }

    /** Run the owned-content sync, manually or from a server scheduler. */
    function sync($args)
    {
        if ($args) {
            $r = VP_Social::sync($args[0], true);
            if (is_wp_error($r)) {
                WP_CLI::error($r->get_error_message());
            }
            WP_CLI::success("Processed " . $r . " items.");
        } else {
            VP_Social::sync_all();
        }
    }
    /** Report connection health without printing credentials. */
    function status()
    {
        foreach (["tiktok", "instagram", "facebook"] as $p) {
            $s = get_option("vp_status_" . $p, []);
            $c = VP_Social::config($p);
            WP_CLI::line(
                $p .
                    ": " .
                    ($c["token"] && $c["id"]
                        ? $s["message"] ?? "Configured, unverified"
                        : "Not connected"),
            );
        }
        WP_CLI::line(
            "Next scheduled sync: " .
                (wp_next_scheduled("vp_hourly_sync")
                    ? gmdate("c", wp_next_scheduled("vp_hourly_sync"))
                    : "MISSING"),
        );
    }
}
WP_CLI::add_command("valon", VP_CLI::class);
