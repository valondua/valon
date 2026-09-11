<?php get_header(); ?><main id="main">
<section class="hero container" aria-labelledby="hero-title"><div class="hero-copy"><p class="eyebrow"><span class="status-dot"></span> <?php echo esc_html(
    valon_text("Entrepreneur. Curious human.", "Sipërmarrës. Njeri kureshtar."),
); ?></p>
<?php
$intro = get_post_field("post_content", get_queried_object_id());
if (trim($intro)) {
    echo apply_filters("the_content", $intro);
} else {
     ?><h1 id="hero-title"><?php echo valon_lang() === "sq"
    ? "Ndërto diçka.<br>Fillo me <em>veten.</em>"
    : "Build something.<br>Start with <em>yourself.</em>"; ?></h1>
<p class="hero-intro"><?php echo esc_html(
    valon_text(
        "I’m Valon. I build companies, question things, and share what I’m learning about relationships, growth and a life of your own.",
        "Jam Valoni. Ndërtoj kompani, baj pyetje dhe ndaj çka po mësoj për marrëdhëniet, rritjen personale dhe jetën që e zgjedh vetë.",
    ),
); ?></p>
<?php
}
?>
<div class="hero-letter"><p class="eyebrow"><?php echo esc_html(
    valon_text("Letters from Valon", "Letra nga Valoni"),
); ?> ↗</p><p><?php echo esc_html(
     valon_text(
         "Honest lessons. Useful ideas. One letter every two weeks.",
         "Mësime të sinqerta. Ide të dobishme. Një letër çdo dy javë.",
     ),
 ); ?></p><?php valon_newsletter("hero"); ?></div>
<a class="text-link" href="<?php echo esc_url(
    valon_url("start"),
); ?>"><?php echo esc_html(
    valon_text("New here? Start with these →", "Je i ri këtu? Fillo me këto →"),
); ?></a></div>
<figure class="hero-portrait"><?php
$portrait = get_theme_mod("valon_portrait");
if ($portrait) {
    echo wp_get_attachment_image($portrait, "full", false, [
        "fetchpriority" => "high",
        "loading" => "eager",
        "alt" => "Valon Asani",
    ]);
} else {
    echo '<img src="' .
        esc_url(get_template_directory_uri() . "/assets/portrait.jpg") .
        '" width="724" height="1086" alt="Valon Asani" fetchpriority="high">';
}
?><figcaption><span>VALON ASANI</span><span>Prishtina ↔ Zürich</span></figcaption></figure></section>
<div class="venture-strip container"><span class="eyebrow"><?php echo esc_html(
    valon_text("A few things I’m building", "Disa gjana që po ndërtoj"),
); ?></span><a href="https://www.dua.com/">dua<span class="brand-dot">.</span>com</a><a href="https://bethe.one/">bethe.one</a><a href="https://www.mikgroup.ch/">MIK GROUP</a><a href="https://www.spotted.de/">spotted</a></div>
<section class="section container"><div class="section-heading"><div><p class="eyebrow">01 / <?php echo esc_html(
    valon_text("A place to begin", "Një vend me fillu"),
); ?></p><h2><?php echo esc_html(
    valon_text("A few ideas to start with.", "Disa ide për me fillu."),
); ?></h2></div><a class="text-link" href="<?php echo esc_url(
    valon_url("start"),
); ?>"><?php echo esc_html(
    valon_text("Start here ↗", "Fillo këtu ↗"),
); ?></a></div><div class="card-grid"><?php valon_posts(
    3,
    true,
); ?></div></section>
<section class="topic-section"><div class="container"><p class="eyebrow">02 / <?php echo esc_html(
    valon_text("Follow your curiosity", "Ndiqe kureshtjen"),
); ?></p><h2><?php echo esc_html(
    valon_text("Life doesn’t fit in one box.", "Jeta s’hyn në një kuti."),
); ?></h2><div class="topic-grid"><?php
$i = 0;
foreach (valon_topics() as $slug => $t):
    $i++; ?><a class="topic-card" href="<?php echo esc_url(
    valon_topic_url($slug),
); ?>"><span class="topic-number">0<?php echo $i; ?> <span>↗</span></span><h3><?php echo esc_html(
     valon_text($t[0], $t[1]),
 ); ?></h3><p><?php echo esc_html(valon_text($t[2], $t[3])); ?></p></a><?php
endforeach;
?></div></div></section>
<section class="section container"><div class="section-heading"><div><p class="eyebrow">03 / <?php echo esc_html(
    valon_text("From the notebook", "Nga shënimet"),
); ?></p><h2><?php echo esc_html(
    valon_text("Writing & reflections.", "Shkrime & mendime."),
); ?></h2></div><a class="text-link" href="<?php echo esc_url(
    valon_url("writing"),
); ?>"><?php echo esc_html(
    valon_text("All writing ↗", "Krejt shkrimet ↗"),
); ?></a></div><div class="writing-list"><?php valon_posts(
    4,
); ?></div></section>
<section class="section container social-section"><div class="section-heading"><div><p class="eyebrow">04 / <?php echo esc_html(
    valon_text("The conversation continues", "Biseda vazhdon"),
); ?></p><h2><?php echo esc_html(
    valon_text(
        "Less polished. More personal.",
        "Pa shumë filtra. Ma personal.",
    ),
); ?></h2></div><a class="text-link" href="<?php echo esc_url(
    valon_url("watch"),
); ?>"><?php echo esc_html(
    valon_text("Watch & explore ↗", "Shiko & zbulo ↗"),
); ?></a></div><?php if (function_exists("vp_render_feed")) {
    echo vp_render_feed("home", 6);
} ?></section>
<section class="about-strip container"><p class="eyebrow"><?php echo esc_html(
    valon_text("A little about me", "Pak për mue"),
); ?></p><h2><?php echo esc_html(
    valon_text(
        "A founder. A curious human. Still figuring things out.",
        "Themelues. Njeri kureshtar. Ende tue mësu.",
    ),
); ?></h2><p><?php echo esc_html(
    valon_text(
        "From Switzerland to Kosovo, from building businesses to building a more intentional life. This is where I share the lessons along the way.",
        "Prej Zvicrës në Kosovë, prej ndërtimit të bizneseve te një jetë me ma shumë qëllim. Këtu i ndaj mësimet e kësaj rruge.",
    ),
); ?></p><a class="text-link" href="<?php echo esc_url(
    valon_url("about"),
); ?>"><?php echo esc_html(
    valon_text("My story ↗", "Historia ime ↗"),
); ?></a> <a class="text-link" href="<?php echo esc_url(
    valon_url("media"),
); ?>"><?php echo esc_html(
    valon_text("Interviews & media ↗", "Intervista & media ↗"),
); ?></a></section>
<?php get_template_part(
    "template-parts/newsletter",
); ?></main><?php get_footer(); ?>
