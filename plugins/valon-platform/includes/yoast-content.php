<?php
defined('ABSPATH') || exit();

/**
 * Supply the visible body of pages whose content is rendered by theme templates.
 * No scores or assessment thresholds are changed. Ordinary articles keep Yoast's
 * normal editor analysis. The homepage's editable slot remains live in the editor.
 */
function vp_yoast_template_content($id, $fresh = false)
{
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
        return null;
    }
    $route = get_post_meta($id, '_valon_route', true);
    $archive = (int) get_option('page_for_posts') === (int) $id;
    if (!in_array($route, ['home', 'media', 'newsletter'], true) && !$archive) {
        return null;
    }
    $key = 'vp_yoast_template_' . $id . '_' . md5($post->post_content);
    if (!$fresh && ($cached = get_transient($key)) !== false) {
        return $cached;
    }
    $url = get_permalink($id);
    if (wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
        return null;
    }
    $response = wp_remote_get(add_query_arg('vp_yoast_read', md5($post->post_content), $url), [
        'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 1048576,
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }
    return vp_yoast_extract_template(wp_remote_retrieve_body($response), $route, $key, trim($post->post_content) === '');
}

function vp_yoast_extract_template($html, $route, $cache_key = '', $editor_is_empty = false)
{
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $main = $loaded ? $doc->getElementById('main') : null;
    if (!$main) return null;
    $xpath = new DOMXPath($doc);
    $slot = '<!-- valon-yoast-editor-content -->';
    $fallback = '';
    if ($route === 'home') {
        $identity = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " hero-identity ")]', $main)->item(0);
        $newsletter = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " hero-newsletter ")]', $main)->item(0);
        if (!$identity || !$newsletter || $identity->parentNode !== $newsletter->parentNode) return null;
        $cursor = $identity->nextSibling;
        while ($cursor && $cursor !== $newsletter) {
            if ($editor_is_empty) $fallback .= $doc->saveHTML($cursor);
            $cursor = $cursor->nextSibling;
        }
        if ($cursor !== $newsletter) return null;
        // This is exactly the the_content() slot between the two template nodes.
        while ($identity->nextSibling && $identity->nextSibling !== $newsletter) {
            $identity->parentNode->removeChild($identity->nextSibling);
        }
        $identity->parentNode->insertBefore($doc->createComment(' valon-yoast-editor-content '), $newsletter);
    } else {
        // The primary page heading is supplied separately as Yoast's text title.
        // Keep any additional H1s so Yoast can still report duplicate headings.
        $heading = $xpath->query('.//h1', $main)->item(0);
        if ($heading) $heading->parentNode->removeChild($heading);
    }
    // Site controls and hidden content are not editorial page copy.
    $remove = $xpath->query('.//script | .//style | .//noscript | .//svg | .//form | .//nav | .//*[@hidden] | .//*[@aria-hidden="true"]', $main);
    foreach (iterator_to_array($remove) as $node) {
        if ($node->parentNode) $node->parentNode->removeChild($node);
    }
    // The press archive is visually a grid of separate rows. Represent those
    // existing block boundaries for the text parser, which cannot read CSS.
    $archive_rows = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " media-archive-list ")]/a', $main);
    foreach (iterator_to_array($archive_rows) as $link) {
        $paragraph = $doc->createElement('p');
        $link->parentNode->insertBefore($paragraph, $link);
        $paragraph->appendChild($link);
    }
    $body = '';
    foreach ($main->childNodes as $node) $body .= $doc->saveHTML($node);
    $result = ['html' => $body, 'slot' => $route === 'home' ? $slot : null, 'route' => $route, 'emptyEditorFallback' => $fallback];
    if ($cache_key) set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
    return $result;
}

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true) || !defined('WPSEO_VERSION')) return;
    $post = get_post();
    if (!$post || !current_user_can('edit_post', $post->ID)) return;
    $content = vp_yoast_template_content($post->ID);
    if (!$content) return;
    wp_enqueue_script('valon-yoast-content', plugins_url('assets/yoast-content.js', VP_DIR . '/valon-platform.php'), ['jquery'], filemtime(VP_DIR . '/assets/yoast-content.js'), true);
    wp_add_inline_script('valon-yoast-content', 'window.valonYoastTemplate = ' . wp_json_encode($content) . ';', 'before');
});
