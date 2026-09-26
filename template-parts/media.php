<?php
defined("ABSPATH") || exit();
$media = valon_media_data();
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
$languages = [
    "en" => "English",
    "sq" => "Shqip",
    "de" => "Deutsch",
    "de-CH" => $t("Swiss German", "Gjermanisht zvicerane", "Schweizerdeutsch"),
    "fr" => "Français",
    "und" => $t("Original recording", "Incizimi origjinal", "Originalaufnahme"),
];
?>
<div class="media-page">
<section class="media-hero" aria-labelledby="media-title">
    <figure><img src="<?php echo esc_url(
        valon_asset_url("assets/valon-portrait.jpeg"),
    ); ?>" width="879" height="894" alt="Valon Asani" fetchpriority="high"><figcaption>Zürich ↔ Prishtina</figcaption></figure>
    <div class="media-hero-copy"><p class="eyebrow"><?php echo esc_html(
        $t(
            "Interviews, podcasts & press",
            "Intervista, podkaste & media",
            "Interviews, Podcasts & Medien",
        ),
    ); ?></p>
    <h1 id="media-title"><?php echo esc_html(
        $t(
            "Good conversations. Real connections.",
            "Biseda të mira. Lidhje të vërteta.",
            "Gute Gespräche. Echte Verbindungen.",
        ),
    ); ?></h1>
    <p><?php echo esc_html(
        $t(
            "Building companies. Connecting people. Figuring out life between Switzerland and Kosovo. These are some of the conversations along the way.",
            "Tue ndërtu kompani. Tue i lidhë njerëzit. Tue e kuptu jetën mes Zvicrës dhe Kosovës. Këto janë disa prej bisedave gjatë rrugës.",
            "Unternehmen aufbauen. Menschen verbinden. Das Leben zwischen der Schweiz und dem Kosovo verstehen. Hier findest du einige Gespräche auf diesem Weg.",
        ),
    ); ?></p>
    <div class="media-actions"><a class="button" href="#interviews"><?php echo esc_html(
        $t(
            "Explore the interviews",
            "Zbulo intervistat",
            "Interviews entdecken",
        ),
    ); ?> ↓</a><a href="#media-contact"><?php echo esc_html(
     $t(
         "Invite me to a conversation",
         "Më fto në një bisedë",
         "Mich zu einem Gespräch einladen",
     ),
 ); ?> ↗</a></div></div>
</section>
<div class="press-strip media-press-strip"><span><?php echo esc_html(
    $t("In the media", "Në media", "In den Medien"),
); ?></span><a href="#coverage" class="press-nzz">NZZ</a><a href="#coverage" class="press-srf">SRF</a><a href="#coverage" class="press-watson">watson</a><a href="#coverage" class="press-balkan">Balkan Insight</a></div>

<section class="section" id="interviews"><div class="section-heading"><div><p class="eyebrow"><?php echo esc_html(
    $t("Watch & listen", "Shiko & dëgjo", "Anschauen & zuhören"),
); ?></p><h2><?php echo esc_html(
    $t(
        "A seat at the conversation.",
        "Bëhu pjesë e bisedës.",
        "Mitten im Gespräch.",
    ),
); ?></h2><p><?php echo esc_html(
    $t(
        "Interviews and longer conversations, in their original language.",
        "Intervista dhe biseda ma të gjata, në gjuhën origjinale.",
        "Interviews und längere Gespräche in ihrer Originalsprache.",
    ),
); ?></p></div></div>
<div class="interview-grid media-interviews"><?php foreach (
    $media["videos"]
    as $i => $video
): ?>
<article class="interview-card"><div class="interview-cover" data-video-container>
<button class="media-video-play" type="button" data-youtube="<?php echo esc_attr(
    $video["id"],
); ?>" aria-label="<?php echo esc_attr(
    $t("Play interview: ", "Lësho intervistën: ", "Interview abspielen: ") .
        $video["outlet"],
); ?>"><img src="<?php echo esc_url(
    "https://i.ytimg.com/vi/" . $video["id"] . "/hqdefault.jpg",
); ?>" alt="" width="480" height="360" loading="lazy"><span class="interview-play" aria-hidden="true">▶</span></button>
</div><div class="interview-body"><p><?php echo esc_html(
    $languages[$video["language"]],
); ?></p><h3><?php echo esc_html(
    $video["outlet"],
); ?></h3><a class="text-link" href="<?php echo esc_url(
    "https://www.youtube.com/watch?v=" . $video["id"],
); ?>"><?php echo esc_html(
    $t("Watch on YouTube", "Shiko në YouTube", "Auf YouTube anschauen"),
); ?> ↗</a></div></article>
<?php endforeach; ?></div><p class="form-note"><?php echo esc_html(
    $t(
        "Press play to load YouTube. The original recording is not translated.",
        "Shtype butonin për me e ngarku YouTube-in. Incizimi origjinal nuk është i përkthyer.",
        "Mit «Abspielen» wird YouTube geladen. Die Originalaufnahme ist nicht übersetzt.",
    ),
); ?></p></section>

<section class="section media-coverage" id="coverage"><div class="section-heading"><div><p class="eyebrow"><?php echo esc_html(
    $t("Stories in the press", "Histori në media", "Geschichten in den Medien"),
); ?></p><h2><?php echo esc_html(
    $t(
        "Different perspectives. One journey.",
        "Këndvështrime të ndryshme. Një rrugëtim.",
        "Verschiedene Perspektiven. Ein Weg.",
    ),
); ?></h2></div></div>
<div class="media-coverage-grid"><?php foreach ([0, 1, 9, 12, 7, 14] as $index):
    $item = $media["press"][$index]; ?>
<a class="media-coverage-card" href="<?php echo esc_url(
    $item["url"],
); ?>"><p class="eyebrow"><?php echo esc_html(
    $item["outlet"],
); ?> <span><?php echo esc_html(
     $languages[$item["language"]],
 ); ?></span></p><h3><?php echo esc_html(
    valon_localized($item["title"]),
); ?></h3><span class="text-link"><?php echo esc_html(
    $t("Read the story", "Lexoje shkrimin", "Beitrag lesen"),
); ?> ↗</span></a>
<?php
endforeach; ?></div>
<details class="media-archive"><summary><?php echo esc_html(
    $t(
        "Explore the full press archive",
        "Zbulo krejt arkivin e mediave",
        "Das ganze Medienarchiv entdecken",
    ),
); ?> <span>25 ↘</span></summary><div class="media-archive-list"><?php foreach (
     $media["press"]
     as $item
 ): ?><a href="<?php echo esc_url($item["url"]); ?>"><span><?php echo esc_html(
    $item["outlet"],
); ?> · <?php echo esc_html(
     $languages[$item["language"]],
 ); ?></span><strong><?php echo esc_html(
    valon_localized($item["title"]),
); ?></strong><span aria-hidden="true">↗</span></a><?php endforeach; ?>
<?php foreach (
    $media["archive_videos"]
    as $video
): ?><a href="<?php echo esc_url(
    "https://www.youtube.com/watch?v=" . $video["id"],
); ?>"><span>YouTube</span><strong><?php echo esc_html(
    $t(
        "Additional archive recording — playback could not be verified",
        "Incizim tjetër prej arkivit — lëshimi nuk u verifikua",
        "Weitere Archivaufnahme – Wiedergabe nicht bestätigt",
    ),
); ?></strong><span aria-hidden="true">↗</span></a><?php endforeach; ?>
</div></details>
<p class="form-note"><?php echo esc_html(
    $t(
        "The summaries are translated. Each publication opens in its original language.",
        "Përmbledhjet janë të përkthyera. Çdo publikim hapet në gjuhën origjinale.",
        "Die Zusammenfassungen sind übersetzt. Die Beiträge öffnen sich in ihrer Originalsprache.",
    ),
); ?></p></section>

<section class="section media-moments"><div class="section-heading"><div><p class="eyebrow"><?php echo esc_html(
    $t("Behind the conversations", "Prapa bisedave", "Hinter den Gesprächen"),
); ?></p><h2><?php echo esc_html(
    $t(
        "People. Places. A few milestones.",
        "Njerëz. Vende. Disa momente të veçanta.",
        "Menschen. Orte. Ein paar Meilensteine.",
    ),
); ?></h2></div></div><div class="media-moment-grid">
<?php foreach (
    [
        [
            "https://www.valonasani.com/wp-content/uploads/2021/09/Interview-GITR.jpg",
            $t(
                "After Get in the Ring",
                "Pas garës Get in the Ring",
                "Nach Get in the Ring",
            ),
        ],
        [
            "https://www.valonasani.com/wp-content/uploads/2021/09/Radio-Interview-1024x768.jpg",
            $t("On the radio", "Në radio", "Im Radio"),
        ],
        [
            "https://www.valonasani.com/wp-content/uploads/2021/09/dua-Team-1024x683.jpg",
            $t(
                "The people behind dua.com",
                "Njerëzit prapa dua.com",
                "Die Menschen hinter dua.com",
            ),
        ],
    ]
    as [$photo, $caption]
): ?><figure><img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(
    $caption,
); ?>" width="1024" height="768" loading="lazy"><figcaption><?php echo esc_html(
    $caption,
); ?></figcaption></figure><?php endforeach; ?></div></section>

<section class="media-contact" id="media-contact"><div><p class="eyebrow"><?php echo esc_html(
    $t("Let’s talk", "Hajde të flasim", "Lass uns reden"),
); ?></p><h2><?php echo esc_html(
    $t(
        "Have a good question?",
        "E ki një pyetje të mirë?",
        "Hast du eine gute Frage?",
    ),
); ?></h2><p><?php echo esc_html(
    $t(
        "For podcasts, interviews and speaking invitations, send me the topic, format, audience and timing.",
        "Për podkaste, intervista dhe ftesa për me folë, ma dërgo temën, formatin, audiencën dhe kohën.",
        "Für Podcasts, Interviews und Vorträge: Schick mir das Thema, das Format, das Publikum und den Termin.",
    ),
); ?></p><a class="button" href="mailto:hello@valonasani.com">hello@valonasani.com ↗</a></div><div class="media-bio"><h3><?php echo esc_html(
    $t(
        "A short introduction",
        "Një prezantim i shkurtë",
        "Eine kurze Vorstellung",
    ),
); ?></h3><p><?php echo esc_html(
    $t(
        "Valon Asani is a Swiss-Albanian entrepreneur and founder of dua.com and MIK Group. He is also building bethe.one and working with Spotted. He lives and works between Zürich and Prishtina.",
        "Valon Asani është ndërmarrës shqiptaro-zviceran dhe themelues i dua.com dhe MIK Group. Po ndërton edhe bethe.one dhe po punon me Spotted. Jeton e punon mes Zürichut dhe Prishtinës.",
        "Valon Asani ist ein schweizerisch-albanischer Unternehmer und Gründer von dua.com und MIK Group. Er baut auch bethe.one auf und arbeitet mit Spotted. Er lebt und arbeitet zwischen Zürich und Prishtina.",
    ),
); ?></p><a href="<?php echo esc_url(
    valon_url("about"),
); ?>"><?php echo esc_html(
    $t("More about me", "Ma shumë për mua", "Mehr über mich"),
); ?> ↗</a></div></section>
<?php get_template_part("template-parts/connect"); ?>
</div>
