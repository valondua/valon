<?php
defined("ABSPATH") || exit();
// The dedicated signup journey and utility pages should stay uninterrupted.
if (!function_exists("vp_newsletter_form") || (!is_front_page() && !is_singular("post"))) return;
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
?>
<dialog class="newsletter-popup" aria-labelledby="newsletter-popup-title" aria-describedby="newsletter-popup-intro">
    <button type="button" class="newsletter-popup-close" data-newsletter-close aria-label="<?php echo esc_attr($t("Close", "Mbyll", "Schliessen")); ?>" autofocus>×</button>
    <p class="eyebrow"><?php echo esc_html($t("Letters from Valon", "Letra nga Valoni", "Briefe von Valon")); ?></p>
    <h2 id="newsletter-popup-title"><?php echo esc_html($t("Let’s keep the conversation going.", "Ta vazhdojmë bisedën.", "Bleiben wir im Gespräch.")); ?></h2>
    <p id="newsletter-popup-intro"><?php echo esc_html($t("One honest story. One idea to try. A letter from me, every two weeks.", "Një histori e sinqertë. Një ide me e provu. Një letër prej meje, çdo dy javë.", "Eine ehrliche Geschichte. Eine Idee zum Ausprobieren. Alle zwei Wochen ein Brief von mir.")); ?></p>
    <?php valon_newsletter("popup"); ?>
    <a class="newsletter-popup-more" href="<?php echo esc_url(valon_url("newsletter")); ?>"><?php echo esc_html($t("See what’s inside the letters", "Shiko çka përmbajnë letrat", "Entdecke, was dich erwartet")); ?> →</a>
</dialog>
