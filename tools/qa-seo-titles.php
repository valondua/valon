<?php
// Local-only fixtures for stale SEO titles and copied descriptions. Run "setup", fetch the pages, then "cleanup".
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("SEO title QA is local-only.");
}
$mode = $args[0] ?? "";
$fixtures = get_posts([
    "post_type" => "post",
    "post_status" => "any",
    "numberposts" => 20,
    "meta_key" => "_vp_seo_title_qa",
    "meta_value" => "1",
    "fields" => "ids",
]);
if ($mode === "cleanup") {
    foreach ($fixtures as $id) {
        wp_delete_post($id, true);
    }
    WP_CLI::success(count($fixtures) . " SEO title fixtures removed.");
    return;
}
if ($mode !== "setup" || $fixtures) {
    WP_CLI::error("Use setup on a clean site, then cleanup.");
}
$GLOBALS["vp_archive_import"] = true;
$make = function ($slug, $title, $seo, $description, $body, $lang, $meta = []) {
    $content = "<p>" . $body . "</p>";
    $id = wp_insert_post([
        "post_type" => "post",
        "post_status" => "publish",
        "post_name" => $slug,
        "post_title" => $title,
        "post_content" => $content,
        "post_date" => "2021-08-24 12:00:00",
        "meta_input" => $meta + [
            "_vp_seo_title_qa" => "1",
            "_vp_requires_review" => "1",
            "_vp_approved_hash" => vp_review_hash($title, $content),
            "_yoast_wpseo_title" => $seo,
            "_yoast_wpseo_metadesc" => $description,
        ],
    ]);
    pll_set_post_language($id, $lang);
    return $id;
};
$revised = $make("seo-qa-revised", "Revised essay heading", "Old legacy SEO title", "Old English description.", "Revised English body.", "en", ["_valon_substantive_update" => "1"]);
$revised_sq = $make("seo-qa-revised-sq", "Titulli i esesë së rishikuar", "Old legacy SEO title", "Old English description.", "Teksti i rishikuar shqip.", "sq");
pll_save_post_translations(["en" => $revised, "sq" => $revised_sq]);
$curated = $make("seo-qa-curated", "Video article heading", "Curated video SEO title", "Curated English description.", "Video body.", "en");
$curated_sq = $make("seo-qa-curated-sq", "Titulli i artikullit me video", "Titull SEO i kuruar", "Përshkrim i kuruar.", "Teksti i videos.", "sq");
pll_save_post_translations(["en" => $curated, "sq" => $curated_sq]);
unset($GLOBALS["vp_archive_import"]);
$expected = [
    $revised => ["Revised essay heading - Valon Asani", "Old English description."],
    $revised_sq => ["Titulli i esesë së rishikuar - Valon Asani", "Teksti i rishikuar shqip."],
    $curated => ["Curated video SEO title", "Curated English description."],
    $curated_sq => ["Titull SEO i kuruar", "Përshkrim i kuruar."],
];
$out = [];
foreach ($expected as $id => [$title, $description]) {
    $out[] = ["url" => get_permalink($id), "title" => $title, "description" => $description];
}
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
