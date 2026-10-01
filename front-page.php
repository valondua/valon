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
                <source type="image/avif" srcset="<?php echo esc_attr(
                    $portrait_sources["avif_srcset"],
                ); ?>" sizes="<?php echo esc_attr($portrait_sources["sizes"]); ?>">
                <source type="image/webp" srcset="<?php echo esc_attr(
                    $portrait_sources["srcset"],
                ); ?>" sizes="<?php echo esc_attr($portrait_sources["sizes"]); ?>">
                <img src="<?php echo esc_url(
                    $portrait_sources["src"],
                ); ?>" width="1448" height="2172" alt="Valon Asani" fetchpriority="high" loading="eager">
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
                    "Be the one.<br>Know your roots.",
                    "Bëhu ma i miri i vetes.<br>Njihe rrënjët.",
                ); ?></h1>
                <p class="hero-intro"><?php echo esc_html(
                    valon_text(
                        "I’m Valon. I believe we become stronger when we keep our word, love with respect and carry our Albanian roots forward.",
                        "Jam Valoni. Besoj që bahemi ma të fortë kur e mbajmë fjalën, duam me respekt dhe i çojmë rrënjët tona shqiptare përpara.",
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
                        "One honest story about love, lived values or our roots—and one thing to try. Every two weeks.",
                        "Një histori për dashninë, vlerat që i jetojmë ose rrënjët tona—dhe diçka me provu. Çdo dy javë.",
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
    <div class="card-grid featured-grid"><?php valon_brand_starters(); ?></div>
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
    <h2><?php echo esc_html(valon_text("Three ways in.", "Tri rrugë për me fillu.")); ?></h2>
    <p class="home-topics-intro"><?php echo esc_html(valon_text(
        "Love well. Live your values. Carry our roots forward. My stories begin with Albanian life and speak to anyone asking similar questions.",
        "Duaj me respekt. Jetoji vlerat. Çoji rrënjët tona përpara. Historitë e mia nisin prej jetës shqiptare, po pyetjet mund t’i vlejnë kujtdo.",
    )); ?></p>
    <div class="home-topic-links">
        <a href="<?php echo esc_url(valon_topic_url("relationships")); ?>"><?php echo esc_html(valon_text("Love well", "Duaj me respekt")); ?> <span aria-hidden="true">↗</span></a>
        <a href="<?php echo esc_url(valon_topic_url("personal-growth")); ?>"><?php echo esc_html(valon_text("Live your values", "Jetoji vlerat")); ?> <span aria-hidden="true">↗</span></a>
        <a href="<?php echo esc_url(valon_topic_url("life-between-cultures")); ?>"><?php echo esc_html(valon_text("Carry our roots forward", "Çoji rrënjët përpara")); ?> <span aria-hidden="true">↗</span></a>
    </div>
    <p class="home-topics-note"><?php echo esc_html(valon_text(
        "What I challenge: disrespect, broken promises and pride that never becomes care or action.",
        "Çka kundërshtoj: mosrespektin, fjalën e thyeme dhe krenarinë që nuk bahet kujdes a veprim.",
    )); ?></p>
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
    <figure class="about-portrait"><?php echo valon_static_image(
        "valon-portrait",
        ["alt" => "Valon Asani", "loading" => "lazy", "decoding" => "async"],
        "(max-width: 780px) 100vw, 560px",
    ); ?></figure>
    <div><p class="about-label"><?php echo esc_html(
        valon_text("About Valon", "Rreth Valonit"),
    ); ?></p><h2><?php echo esc_html(
    valon_text(
        "Love well. Keep your word. Know your roots.",
        "Duaj me respekt. Mbaje fjalën. Njihe prejardhjen.",
    ),
); ?></h2><p><?php echo esc_html(
    valon_text(
        "I’m a Swiss-Albanian founder between Zürich and Prishtina. I build ways for people to connect and grow. I want our values to show in how we love, build and care for the next generation.",
        "Jam themelues shqiptaro-zviceran mes Zürichut e Prishtinës. Ndërtoj mënyra që njerëzit me u lidhë e me u rritë. Dua që vlerat tona me u pa te dashnia, puna dhe kujdesi për brezin tjetër.",
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
