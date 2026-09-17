<?php
defined("ABSPATH") || exit();

// The banner asks every visitor to opt in. Site Kit remains the sole tag owner.
add_filter("googlesitekit_consent_defaults", function ($defaults) {
    if (is_array($defaults)) {
        unset($defaults["region"]);
    }
    return $defaults;
});

// Complianz stores editable banner text in Polylang. These few template labels
// use gettext instead; preserve any installed language-pack translation first.
add_filter("gettext_complianz-gdpr", function ($translated, $original) {
    if (is_admin() || $translated !== $original) {
        return $translated;
    }
    $language = function_exists("pll_current_language")
        ? pll_current_language("slug")
        : "";
    $language = $language ?: substr(get_locale(), 0, 2);
    $labels = [
        "sq" => [
            "Cookie Policy" => "Politika e cookies",
            "Privacy Statement" => "Deklarata e privatësisë",
            "Always active" => "Gjithmonë aktive",
            "Close dialog" => "Mbyll dritaren",
            "Manage options" => "Menaxho opsionet",
            "Manage services" => "Menaxho shërbimet",
            "Manage %s vendors" => "Menaxho %s ofrues",
            "Read more about TCF purposes on Cookie Database" => "Lexo më shumë për qëllimet e TCF në Cookie Database",
            "Read more about these purposes" => "Lexo më shumë për këto qëllime",
        ],
        "de" => [
            "Cookie Policy" => "Cookie-Richtlinie",
            "Privacy Statement" => "Datenschutzerklärung",
            "Always active" => "Immer aktiv",
            "Close dialog" => "Dialog schliessen",
            "Manage options" => "Optionen verwalten",
            "Manage services" => "Dienste verwalten",
            "Manage %s vendors" => "%s Anbieter verwalten",
            "Read more about TCF purposes on Cookie Database" => "Mehr über TCF-Zwecke auf Cookie Database erfahren",
            "Read more about these purposes" => "Mehr über diese Zwecke erfahren",
        ],
    ];
    return $labels[$language][$original] ?? $translated;
}, 10, 2);

// URL-mode documents do not get the page-ID translation that Complianz applies
// to custom pages. Resolve only existing published Polylang counterparts.
function vp_consent_document_url($url)
{
    if (
        is_admin() ||
        !is_string($url) ||
        !function_exists("pll_current_language") ||
        !function_exists("pll_get_post")
    ) {
        return $url;
    }
    $language = pll_current_language("slug") ?: substr(get_locale(), 0, 2);
    $page = $language ? url_to_postid($url) : 0;
    $translated = $page ? pll_get_post($page, $language) : 0;
    if (!$translated || get_post_status($translated) !== "publish") {
        return $url;
    }
    return get_permalink($translated) ?: $url;
}
add_filter("option_cmplz_privacy-statement_custom_page_url", "vp_consent_document_url");
add_filter("option_cmplz_cookie-statement_custom_page_url", "vp_consent_document_url");

// Complianz caches page_links per banner/locale for ten minutes. Resolve links
// again at its public settings hook so cached English URLs/labels cannot leak
// into a translated banner after deployment.
add_filter("cmplz_cookiebanner_settings_front_end", function ($settings) {
    if (is_admin() || !is_array($settings["page_links"] ?? null)) {
        return $settings;
    }
    foreach ($settings["page_links"] as &$links) {
        foreach (["cookie-statement" => "Cookie Policy", "privacy-statement" => "Privacy Statement"] as $type => $source) {
            if (!is_array($links[$type] ?? null)) {
                continue;
            }
            $links[$type]["url"] = vp_consent_document_url($links[$type]["url"] ?? "");
            if (($links[$type]["title"] ?? "") === $source) {
                $links[$type]["title"] = __($source, "complianz-gdpr");
            }
        }
    }
    unset($links);
    return $settings;
});
