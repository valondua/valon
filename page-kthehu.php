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
            ' rows="4" maxlength="' .
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
    <p class="kt-lead">A je shqiptar n’diasporë dhe po mendon me u kthy? Apo je kthy tashma dhe po ndërton diçka?</p><p>Ma trego historinë tande, idenë dhe çka të duhet me ecë përpara. Qëllimi jem asht me të ndihmu me kriju vlerë n’vendin tand.</p>
    <a class="kt-button" href="#apliko">Trego historinë tande <span aria-hidden="true">↗</span></a><p class="kt-caption">Rreth 5–7 minuta · Pa llogari · Historia jote mbetet private</p></div>
    <aside class="kt-letter"><p class="kt-eyebrow">Kthimi ka ma shumë se nji rrugë.</p><p class="kt-letter-title">Nji ide.<br>Nji zanat.<br>Nji fillim i ri.</p><p>Mundesh me kontribuu me biznes, punë, aftësi, investim ose nji projekt që i shërben komunitetit.</p><div class="kt-signature">Valon Asani <span>Për njerëzit që duen me ba diçka n’vend.</span></div></aside>
</section>
<section class="kt-band"><div class="container kt-section-grid"><div><p class="kt-eyebrow">Për kë asht kjo nismë?</p><h2>Për ty që po mendon<br>me lanë nji gjurmë.</h2></div><div><p>Për ty që po planifikon kthimin, je kthy tashma, po nisë ose po rritë nji biznes, don me sjellë aftësitë e tua profesionale, apo po ndërton diçka të dobishme për komunitetin.</p><p><strong>S’të duhet nji plan biznesi i përsosun.</strong> Na duhet me kuptu çka don me ba dhe ku të duhet ndihmë.</p><p class="kt-muted">Shqipni, Kosovë, Lugina e Preshevës, Maqedonia e Veriut, Mali i Zi, Çamëria dhe vende të tjera shqiptare të origjinës. Vendin tand mundesh me e përshkru saktë në formular.</p></div></div></section>
<section class="container kt-support"><p class="kt-eyebrow">Çka mundemi me ba bashkë?</p><h2>Ndihma fillon prej asaj<br>që të duhet ty.</h2><div class="kt-cards"><article><span>01 / Përvojë</span><h3>Me e qartësu hapin tjetër.</h3><p>Mentorim, këshilla praktike, përvojë në biznes, teknologji, produkt ose AI.</p></article><article><span>02 / Lidhje</span><h3>Me gjetë njerëzit e duhun.</h3><p>Njohje me partnerë, klientë, profesionistë lokalë, punëdhënës ose investitorë.</p></article><article><span>03 / Mundësi</span><h3>Me i dhanë hov idesë.</h3><p>Dukshmëni, ndamje e historisë dhe shqyrtim i mundësive të financimit, kur ka hapësinë dhe përputhje.</p></article></div><p class="kt-disclaimer">Mbështetja varet prej aplikimit dhe kapacitetit që kemi. Nuk premtojmë financim, punë ose mbulim të kthimit.</p></section>
<section class="container kt-process"><div><p class="kt-eyebrow">Qysh funksionon?</p><h2>Prej historisë,<br>te hapi tjetër.</h2></div><ol><li><h3>Na trego historinë dhe nevojën tande.</h3><p>Plotëso formularin me aq sa din sot.</p></li><li><h3>E shqyrtojmë aplikimin tand.</h3><p>Secilin veçmas, me kujdes dhe pa premtime të parakohshme.</p></li><li><h3>Flasim për nji hap konkret.</h3><p>Nëse ka nji mënyrë praktike me të ndihmu, të kontaktojmë.</p></li></ol></section>
<section id="apliko" class="kt-application"><div class="container kt-form-layout"><div class="kt-form-intro"><p class="kt-eyebrow">Hapi yt i parë</p><h2>Trego historinë tande.</h2><p>S’ka nevojë me i pasë krejt përgjigjet. Shkruaj thjesht, me fjalët e tua.</p><p class="kt-muted">Fushat opsionale janë të shënueme. Dokumentet dhe podcasti janë krejt në dorën tande.</p><ol class="kt-progress" aria-label="Pjesët e aplikimit"><li>Ti dhe kthimi yt</li><li>Çka don me ndërtu?</li><li>Çfarë ndihme të duhet?</li></ol></div>
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
    valon_kt_field("city");
    valon_kt_field("destination");
    valon_kt_field("town");
    ?></div><?php
valon_kt_field("timeline");
valon_kt_field("story");
?><div class="kt-two"><?php
valon_kt_field("phone");
valon_kt_field("profile");
?></div></fieldset>
    <fieldset><legend><span>02</span> Çka don me ndërtu?</legend><?php foreach (
        ["contribution", "plan", "stage", "progress", "sustainability", "goals"]
        as $key
    ) {
        valon_kt_field($key);
    } ?></fieldset>
    <fieldset><legend><span>03</span> Çfarë ndihme të duhet?</legend><div class="kt-field" data-field="support"><p id="kt-support-label"><strong>Zgjedh krejt ato që të ndihmojnë.</strong></p><div class="kt-checks" role="group" aria-labelledby="kt-support-label" aria-describedby="kt-support-error"><?php foreach (
        vp_kt_choices()["support"]
        as $value
    ): ?><label><input type="checkbox" name="support[]" value="<?php echo esc_attr(
    $value,
); ?>"><span><?php echo esc_html(
    $value,
); ?></span></label><?php endforeach; ?></div><span class="kt-error" id="kt-support-error"></span></div><?php valon_kt_field(
    "obstacle",
); ?>
    <div id="kt-funding" class="kt-conditional" hidden><h3>Pak ma shumë për financimin</h3><p>Nji vlerësim i përafërt mjafton. Mundesh me thanë “S’e di ende”.</p><?php foreach (
        ["amount", "funding_use", "funding_type"]
        as $key
    ) {
        valon_kt_field($key);
    } ?></div>
    <div class="kt-upload"><h3>Diçka me na tregu? <small>(opsionale)</small></h3><p>Plan biznesi, prezantim, buxhet, CV ose foto të projektit — nëse i ke.</p><label for="kt-attachments">Deri në 3 dokumente, deri në 10 MB secili</label><input id="kt-attachments" type="file" name="attachments[]" multiple accept=".pdf,.docx,.pptx,.xlsx,.jpg,.png" aria-describedby="kt-file-hint kt-attachments-error"><small id="kt-file-hint">PDF, DOCX, PPTX, XLSX, JPG ose PNG. Mos ngarko dokumente identiteti, të dhana bankare ose informata të ndjeshme që s’na duhen.</small><span class="kt-error" id="kt-attachments-error"></span><?php valon_kt_field(
        "document_link",
    ); ?></div>
    <div class="kt-podcast"><p class="kt-eyebrow">KONCENTROHU</p><?php valon_kt_field(
        "podcast",
    ); ?><p>Përgjigjja jote <strong>nuk ndikon</strong> në shqyrtimin për mbështetje. Interesimi nuk asht leje për publikim. Incizimin dhe publikimin i dakordojmë veçmas.</p></div>
    <div class="kt-field" data-field="consent"><label class="kt-consent"><input id="kt-consent" type="checkbox" name="consent" value="1" required aria-describedby="kt-consent-error"><span>E kam lexu <a href="#kt-privacy">njoftimin e privatësisë</a> dhe pranoj që aplikimi e dokumentet e mia me u përpunu privatisht nga Valoni dhe ekipi, me ndihmën e AI për shqyrtim. Historia dhe dokumentet e mia nuk publikohen pa lejen time të veçantë.</span></label><span class="kt-error" id="kt-consent-error"></span></div>
    <p class="kt-disclaimer">Aplikimi nuk garanton financim ose mbështetje. Mundësitë shqyrtohen rast pas rasti.</p>
    <div id="kt-status" role="status" aria-live="polite" tabindex="-1"></div><button id="kt-submit" class="kt-button" type="submit" disabled>Dërgo aplikimin <span aria-hidden="true">↗</span></button><button id="kt-retry" type="button" hidden>Provo me e lidhë formularin përsëri</button>
    </fieldset>
</form><?php endif; ?></div></section>
<section id="kt-privacy" class="container kt-privacy"><p class="kt-eyebrow">Privatësia jote</p><h2>Historia jote trajtohet me kujdes.</h2><p>Aplikimin e përdorim për me kuptu planin tand, me shqyrtu ndihmën e mundshme dhe me të kontaktu. Qasje kanë vetëm Valoni dhe anëtarët e autorizuem të ekipit. Dokumentet ruhen privatisht dhe nuk marrin link publik.</p><p>Kur përdorim AI, teksti i planit dhe përgjigjet për ndihmën përpunohen nga OpenAI për nji përmbledhje që e kontrollon ekipi. Emnin, emailin, telefonin, linket, përgjigjen për podcastin dhe dokumentet nuk ia dërgojmë AI. Mos përfshi të dhana të panevojshme personale në përgjigjet e lira. AI nuk merr vendime për aplikimin.</p><p>Aplikimin, dokumentet dhe shënimet i mbajmë deri në 24 muaj, pastaj i fshijmë nga sistemi aktiv. Kopjet rezervë ndjekin afatin e hostimit. Për këtë nismë, përgjegjës asht Valon Asani. Për qasje, korrigjim ose fshim, shkruaj në <a href="mailto:valon@dua.com">valon@dua.com</a>, tue përmendë referencën e aplikimit. Lexo edhe <a href="<?php echo esc_url(
    get_privacy_policy_url() ?: home_url("/privacy-policy/"),
); ?>">politikën e privatësisë</a>. Nuk e publikojmë historinë ose dokumentet pa leje të veçantë.</p><p class="kt-caption">Njoftimi për këtë nismë · 4 nandor 2026</p></section>
</main>
<?php get_footer(); ?>
