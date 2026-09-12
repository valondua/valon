<?php $search_id = wp_unique_id("site-search-"); ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(
    valon_url(),
); ?>"><label class="screen-reader-text" for="<?php echo esc_attr(
    $search_id,
); ?>"><?php echo esc_html(
    valon_text("Search writing", "Kërko shkrime"),
); ?></label><input id="<?php echo esc_attr(
    $search_id,
); ?>" type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php echo esc_attr(
    valon_text("Search for an idea…", "Kërko një ide…"),
); ?>"><input type="hidden" name="post_type" value="post"><button type="submit"><?php echo esc_html(
    valon_text("Search", "Kërko"),
); ?> ↗</button></form>
