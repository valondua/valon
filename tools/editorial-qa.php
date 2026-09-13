<?php
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Local-only checks.");
}
$check = function ($ok, $name) {
    if (!$ok) {
        throw new RuntimeException($name);
    }
    WP_CLI::line("PASS " . $name);
};
$covers = json_decode(
    file_get_contents(get_template_directory() . "/assets/article-covers.json"),
    true,
);
$check(count($covers) === 78, "Cover manifest includes all 78 legacy articles");
foreach ($covers as $slug => $cover) {
    $check(
        str_starts_with(
            $cover["path"],
            "https://www.valonasani.com/wp-content/uploads/",
        ) || is_file(get_template_directory() . "/" . $cover["path"]),
        "Owned cover: " . $slug,
    );
}
$media = valon_media_data();
$check(count($media["press"]) === 25, "All 25 press destinations retained");
foreach ($media["press"] as $item) {
    foreach (["en", "sq", "de"] as $lang) {
        $check(
            !empty($item["title"][$lang]),
            $item["outlet"] . " label in " . $lang,
        );
    }
    $check(
        !str_contains($item["title"]["de"], "ß"),
        "Swiss Standard German spelling: " . $item["outlet"],
    );
}
$ids = [];
try {
    $source = get_posts([
        "name" => "tomorrow-is-a-lie-and-things-are-not-like-they-seem",
        "post_type" => "post",
        "lang" => "",
        "posts_per_page" => 1,
    ]);
    $check(!empty($source), "Existing source article found");
    $id = wp_insert_post([
        "post_type" => "post",
        "post_status" => "draft",
        "post_title" => "Editorial QA translation",
        "meta_input" => ["_vp_source_article" => $source[0]->ID],
    ]);
    $ids[] = $id;
    $previous_query = $GLOBALS["wp_query"];
    try {
        $GLOBALS["wp_query"] = new WP_Query([
            "p" => $id,
            "post_type" => "post",
            "post_status" => "draft",
            "lang" => "",
        ]);
        $presentation = YoastSEO()->meta->for_post($id);
        $check(
            in_array(
                valon_article_image($id)["url"],
                array_column($presentation->open_graph_images, "url"),
                true,
            ),
            "Yoast emits a cover for an article without a featured or inline image",
        );
    } finally {
        $GLOBALS["wp_query"] = $previous_query;
    }
    $check(
        valon_article_image($id)["url"] ===
            valon_article_image($source[0]->ID)["url"],
        "Unpublished translation shares source cover",
    );
    ob_start();
    valon_render_article_image($id, true);
    $card = ob_get_clean();
    $check(
        str_contains($card, 'alt=""') && str_contains($card, 'loading="lazy"'),
        "Card images are decorative and deferred",
    );
    ob_start();
    valon_render_article_image($id);
    $hero = ob_get_clean();
    $check(
        str_contains($hero, 'class="article-cover"') &&
            str_contains($hero, 'loading="eager"'),
        "Article cover is present and eager",
    );
    update_post_meta(
        $id,
        "_valon_legacy_image",
        "https://www.valonasani.com/wp-content/uploads/2021/09/Radio-Interview-1024x768.jpg",
    );
    $check(
        str_contains(valon_article_image($id)["url"], "Radio-Interview"),
        "Explicit legacy image remains respected",
    );
    $attachment = get_posts([
        "post_type" => "attachment",
        "post_mime_type" => "image",
        "posts_per_page" => 1,
        "lang" => "",
    ]);
    if ($attachment) {
        set_post_thumbnail($id, $attachment[0]->ID);
        $check(
            valon_article_image($id)["attachment"] === $attachment[0]->ID,
            "Editor featured image overrides the fallback",
        );
    }
} finally {
    foreach ($ids as $id) {
        wp_delete_post($id, true);
    }
}
WP_CLI::success("Editorial image and multilingual media checks passed.");
