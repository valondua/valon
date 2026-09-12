<?php
$route = $args["route"] ?? "";
$photos = [
    "start" => [
        "valon-self-respect.jpeg",
        1024,
        767,
        "Valon beside a whiteboard of ideas about self-respect",
        "Valoni pranë një tabele me ide për respektin ndaj vetes",
    ],
    "newsletter" => [
        "valon-reading.jpeg",
        792,
        990,
        "Valon surrounded by books",
        "Valoni i rrethuar nga libra",
    ],
    "about" => [
        "valon-portrait.jpeg",
        879,
        894,
        "Valon Asani smiling in a white shirt",
        "Valon Asani duke buzëqeshur, me këmishë të bardhë",
    ],
    "now" => [
        "valon-travel.jpeg",
        768,
        1024,
        "A travel photo from Valon's camera roll",
        "Një foto udhëtimi nga albumi i Valonit",
    ],
];
$photo = $photos[$route] ?? null;
$has_photo = $photo || has_post_thumbnail();
?>
<div class="page-intro <?php echo esc_attr(
    $has_photo ? "page-intro-with-photo page-intro-" . $route : "",
); ?>">
    <div class="page-intro-copy">
        <header class="page-heading">
            <p class="eyebrow">VALON ASANI / <?php echo esc_html(
                ["en" => "ENGLISH", "sq" => "SHQIP", "de" => "DEUTSCH"][
                    valon_lang()
                ] ?? "ENGLISH",
            ); ?></p>
            <h1><?php the_title(); ?></h1>
        </header>
        <div class="prose"><?php
        the_content();
        wp_link_pages();
        ?></div>
        <?php if ($route === "newsletter"): ?>
            <div class="page-signup"><?php valon_newsletter($route); ?></div>
        <?php endif; ?>
    </div>
    <?php if ($has_photo): ?>
        <figure class="page-portrait">
            <?php if (has_post_thumbnail()):
                the_post_thumbnail("large", ["loading" => "eager"]);
            else:
                 ?>
                <img src="<?php echo esc_url(
                    get_template_directory_uri() . "/assets/" . $photo[0],
                ); ?>" width="<?php echo esc_attr(
    $photo[1],
); ?>" height="<?php echo esc_attr($photo[2]); ?>" alt="<?php echo esc_attr(
    valon_text($photo[3], $photo[4]),
); ?>" decoding="async">
            <?php
            endif; ?>
            <?php if ($route === "now"): ?>
                <figcaption><?php echo esc_html(
                    valon_text("From the camera roll.", "Nga albumi i fotove."),
                ); ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php endif; ?>
</div>
