<?php
if (wp_get_environment_type() !== 'local') { WP_CLI::error('Local only.'); }
$manifest = json_decode(file_get_contents(get_template_directory() . '/assets/video-covers.json'), true);
$hashes = [];
foreach ($manifest as $id => $cover) {
    $path = get_template_directory() . '/' . $cover['path'];
    $size = getimagesize($path);
    if (!$size || $size[0] !== $cover['width'] || $size[1] !== $cover['height']) {
        WP_CLI::error('Invalid image dimensions: ' . $id);
    }
    $hashes[] = hash_file('sha256', $path);
}
if (count(array_unique($hashes)) !== count($manifest)) { WP_CLI::error('Duplicate source cover.'); }
$posts = get_posts(['post_type'=>'post','post_status'=>['publish','draft'],'posts_per_page'=>-1,'lang'=>'','meta_key'=>'_vp_video_id']);
$count = 0;
foreach ($posts as $post) {
    $video = get_post_meta($post->ID, '_vp_video_id', true);
    if (!isset($manifest[$video])) { continue; }
    $cover = valon_article_image($post->ID);
    if (!get_post_thumbnail_id($post->ID) && !str_ends_with($cover['url'], $manifest[$video]['path'])) { WP_CLI::error('Wrong source cover: ' . $post->ID); }
    ob_start(); valon_render_article_image($post->ID, true); $html = ob_get_clean();
    if (!str_contains($html, 'loading="lazy"') || !str_contains($html, 'alt=""')) { WP_CLI::error('Card accessibility/loading regression.'); }
    $count++;
}
if (!$count) { WP_CLI::error('No local article editions exercised.'); }
WP_CLI::success(count($manifest) . ' unique valid images; ' . $count . ' language editions resolve and render correctly.');
