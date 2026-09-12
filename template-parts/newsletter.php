<section class="newsletter-band"><div class="container newsletter-grid"><div><p class="eyebrow"><?php echo esc_html(
    valon_text("Letters from Valon", "Letra nga Valoni"),
); ?></p><h2><?php echo valon_lang() === "sq"
    ? "Ide që të mbesin.<br>Një letër çdo dy javë."
    : "Ideas to live with.<br>A letter every two weeks."; ?></h2><p><?php echo esc_html(
    valon_text(
        "One honest story. One useful idea. One thing to try. Join Letters from Valon, in English or Albanian.",
        "Një histori e sinqertë. Një ide e dobishme. Diçka me provu. Merr Letra nga Valoni, në shqip ose anglisht.",
    ),
); ?></p></div><div><?php valon_newsletter("footer"); ?></div></div></section>
