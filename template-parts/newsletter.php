<section class="newsletter-band"><div class="container newsletter-grid"><div><p class="eyebrow"><?php echo esc_html(
    valon_text("Letters from Valon", "Letra nga Valoni"),
); ?></p><h2><?php echo valon_lang() === "sq"
    ? "Le ta vazhdojmë<br><em>bisedën.</em>"
    : "Let’s keep the<br><em>conversation going.</em>"; ?></h2><p><?php echo esc_html(
    valon_text(
        "One honest letter every two weeks. Ideas on building yourself, better relationships and a life you choose.",
        "Një letër e sinqertë çdo dy javë. Ide për veten, marrëdhënie ma të mira dhe jetën që e zgjedh vetë.",
    ),
); ?></p></div><div><?php valon_newsletter("footer"); ?></div></div></section>
