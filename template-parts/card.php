<article <?php post_class("article-card"); ?>><?php if (
    has_post_thumbnail()
): ?><a class="card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail(
    "valon-card",
    ["alt" => "", "loading" => "lazy"],
); ?></a><?php elseif (
    $legacy_image = get_post_meta(get_the_ID(), "_valon_legacy_image", true)
): ?><a class="card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(
    $legacy_image,
); ?>" alt="" loading="lazy" width="840" height="620"></a><?php elseif (
    str_starts_with(
        get_post_field("post_name", get_the_ID()),
        "three-steps-to-exceptional-results",
    )
): ?>
<a class="card-image card-scene" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(
    get_template_directory_uri() . "/assets/valon-whiteboard.jpeg",
); ?>" alt="" loading="lazy" width="874" height="898"></a>
<?php elseif (
    in_array(
        get_post_field("post_name", get_the_ID()),
        [
            "its-about-building-community-before-building-a-product",
            "dont-go-all-in-with-someone-who-isnt-all-in-with-you",
        ],
        true,
    )
):
    $photo = str_starts_with(
        get_post_field("post_name", get_the_ID()),
        "its-about",
    )
        ? "valon-dua.jpeg"
        : "valon-candid.jpeg"; ?><a class="card-image card-photo" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url(
    get_template_directory_uri() . "/assets/" . $photo,
); ?>" alt="" loading="lazy" width="600" height="450"></a><?php
elseif (is_front_page()):

    $cover_categories = get_the_category();
    $cover_slug = $cover_categories ? $cover_categories[0]->slug : "";
    $cover_kind = str_contains($cover_slug, "business")
        ? "business"
        : (str_contains($cover_slug, "personal")
            ? "growth"
            : (str_contains($cover_slug, "cultures")
                ? "cultures"
                : "relationships"));
    ?>
<div class="card-illustration <?php echo esc_attr(
    $cover_kind,
); ?>" aria-hidden="true"><svg viewBox="0 0 400 240" fill="none" stroke="currentColor" stroke-width="2">
<?php if (
    $cover_kind === "business"
) { ?><path d="M65 205V125H145V205M165 205V75H245V205M265 205V25H345V205"/><path d="M30 215H375M85 85L195 35L310 0"/>
<?php } elseif (
    $cover_kind === "growth"
) { ?><circle cx="200" cy="120" r="35"/><circle cx="200" cy="120" r="65"/><circle cx="200" cy="120" r="95"/><circle cx="200" cy="120" r="125"/>
<?php } else { ?><circle cx="155" cy="120" r="85"/><circle cx="245" cy="120" r="85"/><circle cx="155" cy="120" r="64"/><circle cx="245" cy="120" r="64"/><?php } ?>
</svg></div><?php
endif; ?>
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
