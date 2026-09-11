<footer class="site-footer"><div class="container"><div class="footer-top"><a class="wordmark" href="<?php echo esc_url(
    valon_url(),
); ?>">valon asani<span class="brand-dot">.</span></a><p><?php echo esc_html(
    valon_text(
        "A life of your own. A work in progress.",
        "Jeta jote. Gjithmonë tue u ndërtu.",
    ),
); ?></p></div>
<div class="footer-links"><div><?php foreach (
    valon_social_links()
    as $name => $url
) {
    echo '<a href="' . esc_url($url) . '" rel="me">' . esc_html($name) . "</a>";
} ?></div><div><?php foreach (
    [
        "media" => ["Media", "Media"],
        "now" => ["Now", "Tash"],
        "contact" => ["Contact", "Kontakti"],
        "privacy-policy" => ["Privacy", "Privatësia"],
    ]
    as $r => $label
) {
    echo '<a href="' .
        esc_url(valon_url($r)) .
        '">' .
        esc_html(valon_text(...$label)) .
        "</a>";
} ?></div></div>
<div class="footer-bottom"><span>© <?php echo esc_html(
    wp_date("Y"),
); ?> Valon Asani</span><span>Prishtina ↔ Zürich</span></div></div></footer><?php wp_footer(); ?></body></html>
