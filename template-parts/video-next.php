<?php
defined("ABSPATH") || exit();
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
?>
<section class="video-next prose" aria-labelledby="video-next-title">
<p class="eyebrow"><?php echo esc_html(
    $t("Letters from Valon", "Letra nga Valoni", "Briefe von Valon"),
); ?></p>
<h2 id="video-next-title"><?php echo esc_html(
    $t(
        "Keep the conversation going.",
        "Ta vazhdojmë bisedën.",
        "Lass uns im Gespräch bleiben.",
    ),
); ?></h2>
<p><?php echo esc_html(
    $t(
        "More room for the ideas behind the videos. Join my free newsletter for personal reflections and something to try in your own life.",
        "Ma shumë hapësirë për idetë prapa videove. Bashkohu me letrat e mia falas për reflektime personale dhe diçka me provu në jetën tande.",
        "Mehr Raum für die Gedanken hinter den Videos. Abonniere meine kostenlosen Briefe mit persönlichen Reflexionen und Ideen für deinen Alltag.",
    ),
); ?></p>
<a class="button" href="<?php echo esc_url(
    valon_url("newsletter"),
); ?>"><?php echo esc_html(
    $t("Discover the letters", "Zbulo letrat", "Die Briefe entdecken"),
); ?> ↗</a>
</section>
