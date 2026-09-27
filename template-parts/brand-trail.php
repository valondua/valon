<?php
defined("ABSPATH") || exit();
$t = fn($en, $sq, $de) => valon_localized(compact("en", "sq", "de"));
$paths = [
    ["relationships", $t("Love well", "Duaj me respekt", "Respektvoll lieben")],
    ["personal-growth", $t("Live your values", "Jetoji vlerat", "Werte leben")],
    ["life-between-cultures", $t("Carry our roots forward", "Çoji rrënjët përpara", "Unsere Wurzeln weitertragen")],
];
?>
<nav class="article-brand-trail" aria-label="<?php echo esc_attr($t("Explore Valon's themes", "Zbulo temat e Valonit", "Valons Themen entdecken")); ?>">
    <p class="eyebrow"><?php echo esc_html($t("The ideas behind the stories", "Idetë prapa historive", "Die Ideen hinter den Geschichten")); ?></p>
    <p><?php echo esc_html($t(
        "Be the one. Know your roots. Build what comes next.",
        "Bëhu ma i miri i vetes. Njihe prejardhjen. Ndërto çka vjen.",
        "Werde, wer du sein willst. Kenne deine Wurzeln. Gestalte, was kommt.",
    )); ?></p>
    <div class="article-brand-paths">
        <?php foreach ($paths as [$slug, $label]): ?>
            <a href="<?php echo esc_url(valon_topic_url($slug)); ?>"><?php echo esc_html($label); ?> <span aria-hidden="true">↗</span></a>
        <?php endforeach; ?>
    </div>
</nav>
