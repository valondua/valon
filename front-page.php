<?php get_header(); ?>
<main id="main">
<section class="home-hero" aria-labelledby="hero-title">
    <figure class="home-portrait">
        <?php
        $portrait_sources = valon_home_portrait_sources();
        if ($portrait = get_theme_mod("valon_portrait")) {
            echo wp_get_attachment_image($portrait, "full", false, [
                "fetchpriority" => "high",
                "loading" => "eager",
                "alt" => "Valon Asani",
                "sizes" => $portrait_sources["sizes"],
            ]);
        } else {
            ?>
            <picture>
                <source type="image/webp" srcset="<?php echo esc_attr(
                    $portrait_sources["srcset"],
                ); ?>" sizes="<?php echo esc_attr($portrait_sources["sizes"]); ?>">
                <img src="<?php echo esc_url(
                    $portrait_sources["base"] . ".jpeg",
                ); ?>" width="1586" height="1983" alt="Valon Asani" fetchpriority="high" loading="eager">
            </picture>
        <?php
        } ?>
        <figcaption>Prishtina & Zürich</figcaption>
    </figure>
    <div class="container home-hero-inner">
        <div class="home-hero-copy">
            <p class="hero-identity"><?php echo esc_html(
                valon_text(
                    "Valon Asani · Founder of dua.com & MIK Group",
                    "Valon Asani · Themelues i dua.com & MIK Group",
                ),
            ); ?></p>
            <?php
            $intro = get_post_field("post_content", get_queried_object_id());
            if (trim($intro)) {
                echo apply_filters("the_content", $intro);
            } else {
                 ?>
                <h1 id="hero-title"><?php echo valon_text(
                    "Build something.<br>Start with yourself.",
                    "Ndërto diçka.<br>Fillo me veten.",
                ); ?></h1>
                <p class="hero-intro"><?php echo esc_html(
                    valon_text(
                        "I’m Valon. I build companies and share what the journey teaches me about relationships, ambition and a life of your own.",
                        "Jam Valoni. Ndërtoj kompani dhe ndaj çka po më mëson kjo rrugë për marrëdhëniet, ambicien dhe jetën që e zgjedh vetë.",
                    ),
                ); ?></p>
            <?php
            }
            ?>
            <div class="hero-newsletter">
                <h2><?php echo esc_html(
                    valon_text("Letters from Valon", "Letra nga Valoni"),
                ); ?></h2>
                <p><?php echo esc_html(
                    valon_text(
                        "One honest story. One useful idea. One thing to try. In your inbox every two weeks.",
                        "Një histori e sinqertë. Një ide e dobishme. Diçka me provu. Në emailin tand çdo dy javë.",
                    ),
                ); ?></p>
                <?php valon_newsletter("hero"); ?>
                <a class="sample-link" href="<?php echo esc_url(
                    valon_url("sample-letter"),
                ); ?>"><?php echo esc_html(
    valon_text("Read a sample letter", "Lexoje një letër shembull"),
); ?> <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</section>

<div class="press-strip container" aria-label="<?php echo esc_attr(
    valon_text("Selected media coverage", "Media të zgjedhura"),
); ?>">
    <span><?php echo esc_html(valon_text("In the media", "Në media")); ?></span>
    <a href="https://www.nzz.ch/international/kosovo-diaspora-wichtig-fuer-wirtschaft-und-gesellschaft-ld.1699736" class="press-nzz">NZZ</a>
    <a href="https://www.srf.ch/news/schweiz/geld-schicken-kam-fuer-mich-nie-in-frage" class="press-srf">SRF</a>
    <a href="https://www.watson.ch/international/schweiz/571493168-was-die-schweiz-vom-kosovo-lernen-kann" class="press-watson">watson</a>
    <a href="https://balkaninsight.com/2020/08/20/new-app-aims-to-connect-albanians-around-the-world/" class="press-balkan">Balkan Insight</a>
    <a href="<?php echo esc_url(
        valon_url("media"),
    ); ?>" class="text-link"><?php echo esc_html(
    valon_text("All interviews", "Krejt intervistat"),
); ?> <span aria-hidden="true">↗</span></a>
</div>

<section class="section container featured-section">
    <div class="section-heading"><h2><span><?php echo esc_html(
        valon_text("Start here.", "Fillo këtu."),
    ); ?></span><br><?php echo esc_html(
    valon_text("Ideas worth your time.", "Ide që ia vlejnë kohën."),
); ?></h2>
        <a class="text-link" href="<?php echo esc_url(
            valon_url("start"),
        ); ?>"><?php echo esc_html(
    valon_text("Explore the essentials", "Zbulo shkrimet e zgjedhura"),
); ?> <span aria-hidden="true">↗</span></a>
    </div>
    <div class="card-grid featured-grid"><?php valon_posts(3, true); ?></div>
</section>

<section class="interview-section">
    <div class="container section">
        <div class="section-heading"><div><h2><?php echo esc_html(
            valon_text(
                "Conversations that go deeper.",
                "Biseda që shkojnë ma thellë.",
            ),
        ); ?></h2><p><?php echo esc_html(
    valon_text(
        "On building companies, connecting people and life between cultures.",
        "Për ndërtimin e kompanive, lidhjen e njerëzve dhe jetën mes kulturave.",
    ),
); ?></p></div>
            <a class="text-link" href="<?php echo esc_url(
                valon_url("media"),
            ); ?>"><?php echo esc_html(
    valon_text("More interviews", "Ma shumë intervista"),
); ?> <span aria-hidden="true">↗</span></a>
        </div>
        <div class="interview-grid">
        <?php foreach (
            [
                ["ln-JiohoLOw", "Startup Grind Tirana", "Shqip"],
                ["h6jTohTFxps", "Swissalbs", "Schweizerdeutsch"],
                ["yUDlopH-MWw", "RTK · Mysafiri i Mëngjesit", "Shqip"],
            ]
            as [$video, $title, $language]
        ) { ?>
            <a class="interview-card" href="<?php echo esc_url(
                "https://www.youtube.com/watch?v=" . $video,
            ); ?>">
                <div class="interview-cover"><img src="<?php echo esc_url(
                    "https://i.ytimg.com/vi/" . $video . "/hqdefault.jpg",
                ); ?>" alt="" width="480" height="360" loading="lazy"><span class="interview-play" aria-hidden="true">▶</span></div>
                <div class="interview-body"><p><?php echo esc_html(
                    $language,
                ); ?></p><h3><?php echo esc_html(
    $title,
); ?></h3><span><?php echo esc_html(
    valon_text("Watch on YouTube", "Shiko në YouTube"),
); ?> <span aria-hidden="true">↗</span></span></div>
            </a>
        <?php } ?>
        </div>
    </div>
</section>

<section class="section container latest-section">
    <div class="section-heading"><h2><?php echo esc_html(
        valon_text("Latest writing.", "Shkrimet e fundit."),
    ); ?></h2><a class="text-link" href="<?php echo esc_url(
    valon_url("writing"),
); ?>"><?php echo esc_html(
    valon_text("All writing", "Krejt shkrimet"),
); ?> <span aria-hidden="true">↗</span></a></div>
    <div class="card-grid"><?php valon_posts(3); ?></div>
</section>

<section class="home-topics container">
    <h2><?php echo esc_html(
        valon_text("What’s on your mind?", "Çka po të sillet në mendje?"),
    ); ?></h2>
    <div class="home-topic-links"><?php foreach (
        valon_topics()
        as $slug => $topic
    ) { ?>
        <a href="<?php echo esc_url(
            valon_topic_url($slug),
        ); ?>"><?php echo esc_html(
    valon_text($topic[0], $topic[1]),
); ?> <span aria-hidden="true">↗</span></a>
    <?php } ?></div>
</section>

<section class="section container social-section home-social">
    <div class="section-heading"><div><h2><?php echo esc_html(
        valon_text(
            "From the daily conversations.",
            "Prej bisedave të përditshme.",
        ),
    ); ?></h2><p><?php echo esc_html(
    valon_text(
        "The questions, replies and unfiltered moments I share on social.",
        "Pyetjet, përgjigjet dhe momentet pa filtra që i ndaj në rrjete sociale.",
    ),
); ?></p></div><a class="text-link" href="<?php echo esc_url(
    valon_url("watch"),
); ?>"><?php echo esc_html(
    valon_text("Watch & explore", "Shiko & zbulo"),
); ?> <span aria-hidden="true">↗</span></a></div>
    <?php if (function_exists("vp_render_feed")) {
        echo vp_render_feed("home", 6);
    } ?>
</section>

<section class="home-about"><div class="container home-about-grid">
    <figure class="about-portrait"><img src="<?php echo esc_url(
        get_template_directory_uri() . "/assets/valon-portrait.jpeg",
    ); ?>" width="879" height="894" alt="<?php echo esc_attr(
    valon_text("Valon Asani", "Valon Asani"),
); ?>" loading="lazy"></figure>
    <div><p class="about-label"><?php echo esc_html(
        valon_text("About Valon", "Rreth Valonit"),
    ); ?></p><h2><?php echo esc_html(
    valon_text(
        "Building companies. Learning about life.",
        "Tue ndërtu kompani. Tue mësu për jetën.",
    ),
); ?></h2><p><?php echo esc_html(
    valon_text(
        "I’m a Swiss-Albanian founder living and working between Zürich and Prishtina. I founded dua.com and MIK Group. Today, I’m also building bethe.one and working with Spotted.",
        "Jam themelues shqiptaro-zviceran dhe jetoj e punoj mes Zürichut dhe Prishtinës. Kam themelu dua.com dhe MIK Group. Sot po ndërtoj edhe bethe.one dhe po punoj me Spotted.",
    ),
); ?></p><a class="text-link" href="<?php echo esc_url(
    valon_url("about"),
); ?>"><?php echo esc_html(
    valon_text("Read my story", "Lexoje historinë time"),
); ?> <span aria-hidden="true">↗</span></a></div>
    <div class="venture-grid">
        <?php foreach (
            [
                [
                    "dua.com",
                    "https://www.dua.com/",
                    "Connecting people.",
                    "Tue i lidhë njerëzit.",
                ],
                [
                    "bethe.one",
                    "https://bethe.one/",
                    "Becoming yourself.",
                    "Me u ba vetvetja.",
                ],
                [
                    "MIK Group",
                    "https://www.mikgroup.ch/",
                    "Building digital growth.",
                    "Rritje në botën digjitale.",
                ],
                [
                    "Spotted",
                    "https://www.spotted.de/",
                    "Making connections.",
                    "Tue kriju lidhje.",
                ],
            ]
            as [$name, $url, $en, $sq]
        ) { ?>
            <a href="<?php echo esc_url(
                $url,
            ); ?>"><span class="venture-name"><?php echo esc_html(
    $name,
); ?></span><p><?php echo esc_html(
    valon_text($en, $sq),
); ?></p><span aria-hidden="true">↗</span></a>
        <?php } ?>
    </div>
</div></section>
<?php get_template_part("template-parts/newsletter"); ?>
</main>
<?php get_footer(); ?>
