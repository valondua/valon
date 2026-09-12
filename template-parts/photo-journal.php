<?php
$photos = [
    [
        "valon-dua.jpeg",
        1024,
        766,
        "Building things.",
        "Tue ndërtu.",
        "Valon at the dua.com office",
        "Valoni në zyrën e dua.com",
    ],
    [
        "valon-friends.jpeg",
        1024,
        768,
        "Good company.",
        "Në shoqëri të mirë.",
        "Valon in a group selfie",
        "Valoni në një selfie në grup",
    ],
    [
        "valon-family.jpeg",
        886,
        886,
        "Life beyond work.",
        "Jeta përtej punës.",
        "Valon holding a baby",
        "Valoni me një foshnjë në krahë",
    ],
    [
        "valon-curiosity.jpeg",
        768,
        1024,
        "Room for curiosity.",
        "Vend për kureshtje.",
        "Valon laughing while trying on a cowboy hat and boots",
        "Valoni duke qeshur, me kapelë dhe çizme kauboji",
    ],
]; ?>
<section class="photo-journal" aria-labelledby="photo-journal-title">
    <div class="section-heading">
        <div><p class="eyebrow"><?php echo esc_html(
            valon_text("Beyond the bio", "Përtej biografisë"),
        ); ?></p>
        <h2 id="photo-journal-title"><?php echo esc_html(
            valon_text(
                "A few moments along the way.",
                "Disa momente përgjatë rrugës.",
            ),
        ); ?></h2></div>
    </div>
    <div class="photo-journal-grid">
        <?php foreach ($photos as $photo): ?>
            <figure>
                <img src="<?php echo esc_url(
                    get_template_directory_uri() . "/assets/" . $photo[0],
                ); ?>" width="<?php echo esc_attr(
    $photo[1],
); ?>" height="<?php echo esc_attr($photo[2]); ?>" alt="<?php echo esc_attr(
    valon_text($photo[5], $photo[6]),
); ?>" loading="lazy" decoding="async">
                <figcaption><?php echo esc_html(
                    valon_text($photo[3], $photo[4]),
                ); ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
