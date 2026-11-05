<?php
/** Template Name: Kthehu initiative */
defined("ABSPATH") || exit();
wp_enqueue_style(
    "valon-kthehu",
    get_theme_file_uri("/assets/kthehu.css"),
    ["valon-editorial"],
    filemtime(__DIR__ . "/assets/kthehu.css"),
);
wp_enqueue_script(
    "valon-kthehu",
    get_theme_file_uri("/js/kthehu.js"),
    [],
    filemtime(__DIR__ . "/js/kthehu.js"),
    true,
);
wp_localize_script("valon-kthehu", "kthehuConfig", [
    "ticket" => rest_url("valon/v1/kthehu/ticket"),
    "apply" => rest_url("valon/v1/kthehu/apply"),
]);
add_filter("language_attributes", fn() => 'lang="sq"');
get_header();
function valon_kt_field($key)
{
    [$label, $max, $required, $type] = vp_kt_fields()[$key];
    $conditional = in_array(
        $key,
        ["amount", "funding_use", "funding_type"],
        true,
    );
    echo '<div class="kt-field" data-field="' .
        esc_attr($key) .
        '"><label for="kt-' .
        esc_attr($key) .
        '"><span' .
        ($key === "sustainability" ? ' id="kt-sustainability-label"' : "") .
        ">" .
        esc_html($label) .
        "</span>" .
        (!$required && !$conditional ? " <small>(opsionale)</small>" : "") .
        "</label>";
    $attrs =
        ' id="kt-' .
        esc_attr($key) .
        '" name="' .
        esc_attr($key) .
        '" aria-describedby="kt-' .
        $key .
        "-hint kt-" .
        $key .
        '-error"' .
        ($required ? " required" : "");
    if ($type === "select") {
        echo "<select" .
            $attrs .
            '><option value="">Zgjedh nji mundësi</option>';
        foreach (vp_kt_choices()[$key] as $value) {
            echo "<option>" . esc_html($value) . "</option>";
        }
        echo "</select>";
    } elseif ($type === "textarea") {
        echo "<textarea" .
            $attrs .
            ' rows="3" maxlength="' .
            $max .
            '"></textarea>';
    } else {
        $autocomplete = [
            "name" => "name",
            "email" => "email",
            "phone" => "tel",
            "country" => "country-name",
            "city" => "address-level2",
        ];
        echo "<input" .
            $attrs .
            ' type="' .
            $type .
            '" maxlength="' .
            $max .
            '"' .
            (isset($autocomplete[$key])
                ? ' autocomplete="' . $autocomplete[$key] . '"'
                : "") .
            ">";
    }
    $hint =
        $key === "plan"
            ? "Mjaftojnë disa fjali: çka don me ba, ku dhe çfarë ndihme të duhet."
            : "";
    if ($key === "goals") {
        $hint =
            "P.sh. klientët e parë, vende pune, aftësi të përcjelluna ose njerëz të ndihmuem. S’ka problem nëse ende s’e din saktë.";
    }
    echo '<small class="kt-hint" id="kt-' .
        $key .
        '-hint">' .
        esc_html($hint) .
        '</small><span class="kt-error" id="kt-' .
        $key .
        '-error"></span></div>';
}
?>
<main id="main" class="kt-page kt-essential" lang="sq">
<div class="container kt-essential-layout">
<section class="kt-intro" aria-labelledby="kt-title">
<p class="kt-eyebrow">Nji nismë nga Valon Asani</p>
<h1 id="kt-title">Kthehu.<br>Ndërto.<br><em>Kontribuo.</em></h1>
<p class="kt-lead">Po mendon me u kthy, apo je kthy e po ndërton diçka? Më trego idenë tande dhe si mundem me të ndihmu.</p>
<p class="kt-muted">Përvojën që e ke fitu jashtë mundesh me e sjellë n’vend — me nji biznes, nji zanat ose nji projekt për komunitetin. Unë dhe ekipi mundemi me të ndihmu me këshilla, lidhje dhe hapa konkretë.</p>
<figure class="kt-video" id="kt-video"><p class="kt-video-title">Ftesa ime për me u kthy</p><iframe src="https://www.tiktok.com/player/v1/7702456687784922389?autoplay=0&amp;description=0&amp;music_info=0" title="Valon Asani: ftesë për me u kthy dhe kontribuu" width="240" height="427" allow="fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe><figcaption class="kt-caption">Video nga TikTok · <a href="https://www.tiktok.com/@valon_asani/video/7702456687784922389" target="_blank" rel="noopener">Hape në TikTok ↗</a></figcaption></figure>
<figure class="kt-diaspora"><img src="<?php echo esc_url(
    get_theme_file_uri("/assets/kthehu-diaspora.svg"),
); ?>" alt="Hartë ilustruese me lidhje nga diaspora në Amerikë, Europë e Australi drejt trojeve shqiptare." width="720" height="350"><figcaption><strong>Nga bota, drejt vendit.</strong><a href="https://www.dua.com/albanians/" target="_blank" rel="noopener">Shqiptarët nëpër botë ↗</a></figcaption></figure>
</section>
<section id="apliko" class="kt-essential-form" aria-labelledby="kt-form-title">
<h2 id="kt-form-title">Le ta nisim me nji mesazh.</h2><p class="kt-caption">Pa llogari. Vetëm ti, Valoni dhe ekipi.</p>
<?php if (
    !function_exists("vp_kt_fields")
): ?><p>Aplikimet do të hapen së shpejti.</p><?php else: ?>
<form id="kt-form" enctype="multipart/form-data" method="post" novalidate>
<noscript><p>Aktivizo JavaScript për me e dërgu këtë formular.</p></noscript>
<div class="kt-honeypot" aria-hidden="true"><label>Lëre bosh<input name="company_url" tabindex="-1" autocomplete="off"></label></div>
<input type="hidden" name="token" id="kt-token">
<div class="kt-two"><?php
valon_kt_field("name");
valon_kt_field("email");
?></div>
<?php valon_kt_field("plan"); ?>
<div class="kt-field kt-single-upload" data-field="attachments"><label for="kt-attachments">Shto nji dokument <small>(opsionale)</small></label><input id="kt-attachments" name="attachments[]" type="file" accept=".pdf,.docx,.pptx,.xlsx,.jpg,.png" aria-describedby="kt-upload-hint kt-attachments-error"><small id="kt-upload-hint" class="kt-hint">Plan, CV ose foto · deri në 10 MB · PDF, DOCX, PPTX, XLSX, JPG, PNG. Ruhet privatisht.</small><span id="kt-attachments-error" class="kt-error"></span></div>
<div class="kt-field" data-field="consent"><label class="kt-consent"><input id="kt-consent" type="checkbox" name="consent" value="1" required aria-describedby="kt-consent-error"><span>Pranoj <a href="#kt-privacy">shqyrtimin privat</a> nga Valoni dhe ekipi. Asgja nuk publikohet pa lejen time.</span></label><span class="kt-error" id="kt-consent-error"></span></div>
<div id="kt-status" role="status" aria-live="polite" tabindex="-1"></div><button id="kt-submit" class="kt-button" type="submit" disabled>Dërgo mesazhin <span aria-hidden="true">↗</span></button><button id="kt-retry" type="button" hidden>Provo me e lidhë formularin përsëri</button>
</form><?php endif; ?>
<p class="kt-caption">Të kontaktojmë nëse mundemi me ndihmu. Mbështetja nuk garantohet.</p>
<details id="kt-privacy" class="kt-essential-privacy"><summary>Qysh i ruejmë të dhanat?</summary><p>Valon Asani dhe ekipi i autorizuem e lexojnë mesazhin për me shqyrtu ndihmën dhe me të kontaktu. Nuk përdorim AI dhe nuk publikojmë asgja pa leje.</p><p>Të dhanat, dokumentet dhe shënimet ruhen deri në 24 muaj në sistemin aktiv; kopjet rezervë ndjekin afatin e hostimit. Për qasje, korrigjim ose fshim: <a href="mailto:valon@dua.com">valon@dua.com</a>. Lexo <a href="<?php echo esc_url(
    get_privacy_policy_url() ?: home_url("/privacy-policy/"),
); ?>">politikën e privatësisë</a>.</p></details>
</section></div></main>
<?php get_footer(); ?>
