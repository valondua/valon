<?php
/** Read-only integration checks: wp eval-file tools/article-loading-qa.php */
if (PHP_SAPI !== "cli" || !defined("ABSPATH")) {
    exit("Load WordPress from the command line first.\n");
}
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS $message\n";
};
$original_query = $GLOBALS["wp_query"];
$original_main = $GLOBALS["wp_the_query"];
$original_post = $GLOBALS["post"] ?? null;
$html = '<img src="https://example.test/diagram.png" srcset="https://example.test/diagram-400.png 400w, https://example.test/diagram.png 800w" width="800" height="600" alt="Diagram" fetchpriority="high" decoding="async">';
$query = new WP_Query(["post_type" => "post", "name" => "building-in-public-get-ready-for-the-energy-vampires", "posts_per_page" => 1]);
try {
    $check($query->have_posts(), "Representative ordinary article exists");
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    $result = valon_article_body_image_loading($html, "the_content");
    $tag = new WP_HTML_Tag_Processor($result);
    $tag->next_tag("IMG");
    $check($tag->get_attribute("loading") === "lazy" && $tag->get_attribute("fetchpriority") === null, "Body image yields priority to the template cover");
    $check($tag->get_attribute("alt") === "Diagram" && (int) $tag->get_attribute("width") === 800 && (int) $tag->get_attribute("height") === 600 && $tag->get_attribute("srcset") !== null, "Image identity, dimensions and responsive candidates survive");
    $check(valon_article_body_image_loading($html, "widget_text") === $html, "Other rendering contexts are unchanged");
    $query->in_the_loop = false;
    $check(valon_article_body_image_loading($html, "the_content") === $html, "Metadata extraction outside the loop is unchanged");
    $query->in_the_loop = true;
    $GLOBALS["wp_the_query"] = $original_main;
    $check(valon_article_body_image_loading($html, "the_content") === $html, "Secondary queries are unchanged");
    $video = get_posts(["post_type" => "post", "posts_per_page" => 1, "fields" => "ids", "meta_key" => "_vp_video_id", "meta_compare" => "EXISTS"]);
    $check((bool) $video, "Representative video article exists");
    $query = new WP_Query(["p" => $video[0], "post_type" => "post"]);
    $GLOBALS["wp_query"] = $GLOBALS["wp_the_query"] = $query;
    $query->the_post();
    $check(valon_article_body_image_loading($html, "the_content") === $html, "Video articles retain their player loading behavior");
} finally {
    $GLOBALS["wp_query"] = $original_query;
    $GLOBALS["wp_the_query"] = $original_main;
    $GLOBALS["post"] = $original_post;
}
