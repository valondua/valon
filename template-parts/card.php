<article <?php post_class("article-card"); ?>><?php if (
    has_post_thumbnail()
): ?><a class="card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail(
    "valon-card",
    ["alt" => "", "loading" => "lazy"],
); ?></a><?php elseif (
    $legacy_image = get_post_meta(get_the_ID(), "_valon_legacy_image", true)
): ?><a class="card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(
    $legacy_image,
); ?>" alt="" loading="lazy" width="840" height="620"></a><?php endif; ?>
<div class="card-body"><p class="eyebrow"><?php
$cats = get_the_category();
echo esc_html($cats ? $cats[0]->name : valon_text("Reflections", "Mendime"));
?></p><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p class="card-excerpt"><?php echo esc_html(
    wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 26),
); ?></p><div class="card-meta"><time datetime="<?php echo esc_attr(
    get_the_date("c"),
); ?>"><?php echo esc_html(
    get_the_date("M j, Y"),
); ?></time><span>·</span><span><?php echo esc_html(
    valon_reading_time(),
); ?></span><span class="card-arrow" aria-hidden="true">↗</span></div></div></article>
