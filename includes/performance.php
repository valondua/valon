<?php
defined("ABSPATH") || exit();

/** Keep YARPP's small related-post styles in their original cascade position. */
function valon_inline_related_styles($html, $handle, $href, $media)
{
    if (!is_singular("post") || $handle !== "yarppRelatedCss") {
        return $html;
    }
    $relative = "/yet-another-related-posts-plugin/style/related.css";
    $expected = plugins_url($relative);
    foreach ([PHP_URL_HOST, PHP_URL_PATH] as $component) {
        if (wp_parse_url($href, $component) !== wp_parse_url($expected, $component)) {
            return $html;
        }
    }
    $file = WP_PLUGIN_DIR . $relative;
    if (!is_readable($file) || filesize($file) > 4096) {
        return $html;
    }
    $css = file_get_contents($file);
    // Preserve the external link if a future plugin release needs URL resolution.
    if (!$css || preg_match('/url\s*\(|@import|<\/style/i', $css)) {
        return $html;
    }
    return sprintf('<style id="%s" media="%s">%s</style>', esc_attr($handle . "-css"), esc_attr($media), $css);
}
add_filter("style_loader_tag", "valon_inline_related_styles", 10, 4);

/** The template cover is the priority image; body illustrations follow it. */
function valon_article_body_image_loading($html, $context)
{
    if (
        $context !== "the_content" || !is_singular("post") ||
        !in_the_loop() || !is_main_query() ||
        get_the_ID() !== get_queried_object_id() ||
        (function_exists("vp_video_id") && vp_video_id(get_the_ID()))
    ) {
        return $html;
    }
    $tag = new WP_HTML_Tag_Processor($html);
    if ($tag->next_tag("IMG")) {
        $tag->set_attribute("loading", "lazy");
        $tag->remove_attribute("fetchpriority");
    }
    return $tag->get_updated_html();
}
add_filter("wp_content_img_tag", "valon_article_body_image_loading", 20, 2);
