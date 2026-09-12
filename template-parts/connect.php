<?php
defined("ABSPATH") || exit();
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
$descriptions = [
    "TikTok" => $t(
        "Candid videos, questions and replies. Mostly in Albanian.",
        "Video pa filtra, pyetje e përgjigje. Kryesisht shqip.",
        "Offene Videos, Fragen und Antworten. Vor allem auf Albanisch.",
    ),
    "Instagram" => $t(
        "Everyday moments, stories and the people in my life.",
        "Momente të përditshme, histori dhe njerëzit në jetën time.",
        "Alltagsmomente, Geschichten und die Menschen in meinem Leben.",
    ),
    "Facebook" => $t(
        "Videos, reflections and conversations with the community.",
        "Video, mendime dhe biseda me komunitetin.",
        "Videos, Gedanken und Gespräche mit der Community.",
    ),
    "LinkedIn" => $t(
        "Building companies, technology and lessons from the work.",
        "Ndërtimi i kompanive, teknologjia dhe mësimet prej punës.",
        "Unternehmen, Technologie und Erfahrungen aus der Praxis.",
    ),
    "X" => $t(
        "Short observations and conversations as they happen.",
        "Mendime të shkurta dhe biseda të momentit.",
        "Kurze Gedanken und aktuelle Gespräche.",
    ),
];
?>
<section class="section connect-section"><div class="section-heading"><div><p class="eyebrow"><?php echo esc_html(
    $t("Keep in touch", "Mbajmë kontakt", "Bleiben wir in Kontakt"),
); ?></p><h2><?php echo esc_html(
    $t(
        "The conversation continues.",
        "Biseda vazhdon.",
        "Das Gespräch geht weiter.",
    ),
); ?></h2></div><a class="text-link" href="<?php echo esc_url(
    valon_url("newsletter"),
); ?>"><?php echo esc_html(
    $t("Get my letters", "Merri letrat e mia", "Meine Briefe erhalten"),
); ?> ↗</a></div><div class="connect-grid"><?php foreach (
     valon_social_links()
     as $name => $url
 ): ?><a href="<?php echo esc_url(
    $url,
); ?>" class="connect-card" data-connect-platform="<?php echo esc_attr(
    strtolower($name),
); ?>"><h3><?php echo esc_html(
    $name,
); ?> <span aria-hidden="true">↗</span></h3><p><?php echo esc_html(
     $descriptions[$name],
 ); ?></p><span class="text-link"><?php echo esc_html(
    $t("Find me on ", "Më gjej në ", "Zu meinem Profil auf ") . $name,
); ?></span></a><?php endforeach; ?></div></section>
