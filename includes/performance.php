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

function valon_is_article_body_context()
{
    return is_singular("post") && in_the_loop() && is_main_query() &&
        get_the_ID() === get_queried_object_id() &&
        !(function_exists("vp_video_id") && vp_video_id(get_the_ID()));
}

/** The template cover is the priority image; body illustrations follow it. */
function valon_article_body_image_loading($html, $context)
{
    if ($context !== "the_content" || !valon_is_article_body_context()) {
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

/** Wrap standalone body images after WordPress has supplied their responsive attributes. */
function valon_article_body_pictures($html)
{
    if (!valon_is_article_body_context() || stripos($html, "<img") === false) {
        return $html;
    }
    $sizes = "(max-width: 780px) min(calc(100vw - 44px), 720px), min(calc(100vw - 64px), 720px)";
    $marker = "data-valon-upload-token";
    while (stripos($html, $marker) !== false) {
        $marker .= "-x";
    }
    $tags = new WP_HTML_Tag_Processor($html);
    $picture_depth = 0;
    $urls = [];
    while ($tags->next_tag(["tag_closers" => "visit"])) {
        if ($tags->get_tag() === "PICTURE") {
            $picture_depth = max(0, $picture_depth + ($tags->is_tag_closer() ? -1 : 1));
            continue;
        }
        if ($tags->get_tag() !== "IMG" || $picture_depth > 0) {
            continue;
        }
        $url = $tags->get_attribute("src");
        if (!is_string($url)) {
            continue;
        }
        $probe = '<img src="' . esc_url($url) . '">';
        if (valon_upload_picture($url, $probe, $sizes) === $probe) {
            continue;
        }
        $id = (string) count($urls);
        $urls[$id] = $url;
        $tags->set_attribute($marker, $id);
    }
    if (!$urls) {
        return $html;
    }
    // Temporary markers locate raw tags without reserializing the document or attributes.
    // The full HTML parser above supplies context, including pictures, comments and scripts.
    $result = preg_replace_callback(
        '~<img\b(?:[^>\'"]|"[^"]*"|\'[^\']*\')*>~i',
        function ($match) use ($marker, $urls, $sizes) {
            $tag = new WP_HTML_Tag_Processor($match[0]);
            if (!$tag->next_tag("IMG")) {
                return $match[0];
            }
            $id = $tag->get_attribute($marker);
            if (!is_string($id) || !isset($urls[$id])) {
                return $match[0];
            }
            $original = str_replace(' ' . $marker . '="' . $id . '"', "", $match[0]);
            return valon_upload_picture($urls[$id], $original, $sizes);
        },
        $tags->get_updated_html(),
    );
    // If an unusual token could not be recovered exactly, preserve the original document.
    return is_string($result) && !str_contains($result, $marker) ? $result : $html;
}
add_filter("the_content", "valon_article_body_pictures", 13);
