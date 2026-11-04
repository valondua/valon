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
    $hint = $type === "textarea" ? "Deri në " . $max . " shkronja." : "";
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
<main id="main" class="kt-page" lang="sq">
<section class="kt-hero container" aria-labelledby="kt-title">
    <div class="kt-intro"><p class="kt-eyebrow"><span></span> Nji nismë nga Valon Asani</p><h1 id="kt-title">Kthehu.<br>Ndërto.<br><em>Kontribuo.</em></h1>
    <p class="kt-lead">Po mendon me u kthy? Apo je kthy e po ndërton diçka? Le ta bajmë hapin tjetër bashkë.</p>
    <a class="kt-button" href="#apliko">Trego idenë tande <span aria-hidden="true">↗</span></a><p class="kt-caption">Rreth 3 minuta · Pa llogari · Privatisht</p></div>
    <aside class="kt-letter"><p class="kt-eyebrow">Prej diasporës. Për vendin.</p><p class="kt-letter-title">Përvoja jote.<br>Ideja jote.<br><em>Hapi i radhës.</em></p><p>Biznes, punë ose nji projekt për komunitetin. S’të duhet nji plan i përsosun — vetëm dëshira me fillu.</p><div class="kt-signature">Valon Asani <span>Nji bisedë mundet me qenë fillimi.</span></div></aside>
</section>
<section class="container kt-support"><p class="kt-eyebrow">Çka mundemi me ba bashkë?</p><h2>Ti sjell idenë.<br>Gjejmë hapin tjetër.</h2><div class="kt-cards"><article><span>01 / Qartësi</span><h3>Këshilla që të çojnë përpara.</h3><p>Mentorim dhe përvojë praktike për planin tand.</p></article><article><span>02 / Njerëz</span><h3>Lidhje që hapin dyer.</h3><p>Partnerë, klientë dhe profesionistë të duhun.</p></article><article><span>03 / Mundësi</span><h3>Ma shumë hov për idenë.</h3><p>Dukshmëni dhe mundësi financimi, kur ka përputhje.</p></article></div><p class="kt-disclaimer">E lexojmë aplikimin dhe të kontaktojmë nëse mundemi me ndihmu. Financimi dhe mbështetja nuk garantohen.</p></section>
<section id="apliko" class="kt-application"><div class="container kt-form-layout"><div class="kt-form-intro"><p class="kt-eyebrow">Hapi yt i parë</p><h2>Nji ide.<br>Nji fillim.</h2><p>Na trego çka don me ba dhe ku të duhet ndihmë. Pjesën tjetër e flasim bashkë.</p><p class="kt-muted">Rreth 3 minuta.<br>Përgjigjet i lexon Valoni dhe ekipi.</p></div>
<?php if (
    !function_exists("vp_kt_fields")
): ?><p>Aplikimet do të hapen së shpejti.</p><?php else: ?>
<form id="kt-form" enctype="multipart/form-data" method="post" novalidate>
    <noscript><p>Aktivizo JavaScript për me e dërgu këtë formular.</p></noscript>
    <div class="kt-honeypot" aria-hidden="true"><label>Lëre bosh<input name="company_url" tabindex="-1" autocomplete="off"></label></div>
    <input type="hidden" name="token" id="kt-token">
    <fieldset><legend><span>01</span> Ti dhe kthimi yt</legend><div class="kt-two"><?php
    valon_kt_field("name");
    valon_kt_field("email");
    valon_kt_field("country");
    valon_kt_field("destination");
    ?></div><?php valon_kt_field("timeline"); ?></fieldset>
    <fieldset><legend><span>02</span> Çka don me ndërtu?</legend><?php foreach (
        ["contribution", "plan"]
        as $key
    ) {
        valon_kt_field($key);
    } ?></fieldset>
    <fieldset><legend><span>03</span> Çfarë ndihme të duhet?</legend><div class="kt-field" data-field="support"><p id="kt-support-label"><strong>Ku të duhet ndihmë? Zgjedh nji ose ma shumë.</strong></p><div class="kt-checks" role="group" aria-labelledby="kt-support-label" aria-describedby="kt-support-error"><?php foreach (
        vp_kt_choices()["support"]
        as $value
    ): ?><label><input type="checkbox" name="support[]" value="<?php echo esc_attr(
    $value,
); ?>"><span><?php echo esc_html(
    vp_kt_support_label($value),
); ?></span></label><?php endforeach; ?></div><span class="kt-error" id="kt-support-error"></span></div><?php valon_kt_field(
    "obstacle",
); ?>
    <details class="kt-extras"><summary>Shto dokumente ose interesim për podcast <small>opsionale</small></summary>
    <div class="kt-upload"><h3>Diçka me na tregu? <small>(opsionale)</small></h3><p>Plan, CV ose foto — nëse i ke.</p><label for="kt-attachments">Deri në 3 dokumente, deri në 10 MB secili</label><input id="kt-attachments" type="file" name="attachments[]" multiple accept=".pdf,.docx,.pptx,.xlsx,.jpg,.png" aria-describedby="kt-file-hint kt-attachments-error"><small id="kt-file-hint">PDF, DOCX, PPTX, XLSX, JPG ose PNG. Mos ngarko dokumente identiteti, të dhana bankare ose informata të ndjeshme që s’na duhen.</small><span class="kt-error" id="kt-attachments-error"></span><?php valon_kt_field(
        "document_link",
    ); ?></div>
    <div class="kt-podcast"><p class="kt-eyebrow">KONCENTROHU</p><?php valon_kt_field(
        "podcast",
    ); ?><p>Interesimi <strong>nuk ndikon</strong> në mbështetje. Incizimi dhe publikimi kërkojnë leje veçmas.</p></div>
    </details><div class="kt-field" data-field="consent"><label class="kt-consent"><input id="kt-consent" type="checkbox" name="consent" value="1" required aria-describedby="kt-consent-error"><span>E kam lexu <a href="#kt-privacy">njoftimin e privatësisë</a> dhe pranoj shqyrtimin privat nga Valoni dhe ekipi. Asgja nuk publikohet pa lejen time.</span></label><span class="kt-error" id="kt-consent-error"></span></div>

    <div id="kt-status" role="status" aria-live="polite" tabindex="-1"></div><button id="kt-submit" class="kt-button" type="submit" disabled>Dërgo aplikimin <span aria-hidden="true">↗</span></button><button id="kt-retry" type="button" hidden>Provo me e lidhë formularin përsëri</button>
    </fieldset>
</form><?php endif; ?></div></section>
<section id="kt-privacy" class="container kt-privacy"><p class="kt-eyebrow">Privatësia jote</p><h2>Historia jote trajtohet me kujdes.</h2><p>Aplikimin e lexojnë vetëm Valoni dhe ekipi i autorizuem për me shqyrtu ndihmën dhe me të kontaktu. Dokumentet ruhen privatisht. Nuk përdorim AI për shqyrtim.</p><p>Aplikimin, dokumentet dhe shënimet i mbajmë deri në 24 muaj, pastaj i fshijmë nga sistemi aktiv. Kopjet rezervë ndjekin afatin e hostimit. Për këtë nismë, përgjegjës asht Valon Asani. Për qasje, korrigjim ose fshim, shkruaj në <a href="mailto:valon@dua.com">valon@dua.com</a>, tue përmendë referencën e aplikimit. Lexo edhe <a href="<?php echo esc_url(
    get_privacy_policy_url() ?: home_url("/privacy-policy/"),
); ?>">politikën e privatësisë</a>. Nuk e publikojmë historinë ose dokumentet pa leje të veçantë.</p><p class="kt-caption">Njoftimi për këtë nismë · 4 nandor 2026</p></section>
</main>
<?php get_footer(); ?>
