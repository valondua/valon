<?php
/**
 * Preview: wp --user=<administrator> eval-file translate-consent-banner.php
 * Apply:   wp --user=<administrator> eval-file translate-consent-banner.php apply
 *
 * Uses Complianz 7.5.5 CMPLZ_COOKIEBANNER::save() and Polylang 3.8 PLL_MO,
 * the same import/make_entry/export methods used by Polylang's settings table.
 * The default is a dry run. Existing target-source translations are preserved.
 * No policy-page content, consent decisions, analytics IDs or subscribers change.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    exit("Run this file with WP-CLI.\n");
}
foreach (['cmplz_get_cookiebanner', 'pll_register_string', 'icl_register_string', 'PLL'] as $function) {
    if (!function_exists($function)) {
        WP_CLI::error("Required integration is unavailable: $function");
    }
}
if (!class_exists('PLL_MO') || !method_exists('PLL_MO', 'translate_if_any')) {
    WP_CLI::error('Polylang PLL_MO with translate_if_any is required.');
}
$apply = in_array('apply', $args ?? [], true);
if ($apply && !cmplz_user_can_manage()) {
    WP_CLI::error('Pass --user=<existing administrator> with manage_privacy capability.');
}
$message = 'This site uses necessary cookies to work. With your permission, Google Analytics helps me understand which pages people read. Embedded videos may also use third-party cookies. You can accept, reject optional cookies, or choose your preferences, and change your choice at any time.';
$dictionary = [
    'message_optin' => [
        'en' => $message,
        'sq' => 'Kjo faqe përdor cookies të domosdoshme për të funksionuar. Me lejen tënde, Google Analytics më ndihmon të kuptoj cilat faqe lexojnë vizitorët. Edhe videot e integruara mund të përdorin cookies nga palë të treta. Mund t’i pranosh, t’i refuzosh cookies opsionale ose të zgjedhësh preferencat e tua, dhe ta ndryshosh zgjedhjen në çdo kohë.',
        'de' => 'Diese Website verwendet notwendige Cookies, damit sie funktioniert. Mit deiner Erlaubnis hilft mir Google Analytics zu verstehen, welche Seiten gelesen werden. Eingebettete Videos können ebenfalls Cookies von Drittanbietern verwenden. Du kannst alle Cookies akzeptieren, optionale Cookies ablehnen oder deine Einstellungen auswählen und deine Entscheidung jederzeit ändern.',
    ],
    'accept' => ['en' => 'Accept all', 'sq' => 'Prano të gjitha', 'de' => 'Alle akzeptieren'],
    'dismiss' => ['en' => 'Reject optional', 'sq' => 'Refuzo cookies opsionale', 'de' => 'Optionale ablehnen'],
    'header' => ['en' => 'Manage Consent', 'sq' => 'Menaxho pëlqimin', 'de' => 'Einwilligung verwalten'],
    'revoke' => ['en' => 'Manage consent', 'sq' => 'Menaxho pëlqimin', 'de' => 'Einwilligung verwalten'],
    'view_preferences' => ['en' => 'View preferences', 'sq' => 'Shiko preferencat', 'de' => 'Einstellungen anzeigen'],
    'save_preferences' => ['en' => 'Save preferences', 'sq' => 'Ruaj preferencat', 'de' => 'Auswahl speichern'],
    'category_functional' => ['en' => 'Functional', 'sq' => 'Funksionale', 'de' => 'Funktional'],
    'category_prefs' => ['en' => 'Preferences', 'sq' => 'Preferencat', 'de' => 'Einstellungen'],
    'category_stats' => ['en' => 'Statistics', 'sq' => 'Statistika', 'de' => 'Statistik'],
    'category_all' => ['en' => 'Marketing', 'sq' => 'Marketing', 'de' => 'Marketing'],
    'functional_text' => [
        'en' => 'The technical storage or access is strictly necessary for the legitimate purpose of enabling the use of a specific service explicitly requested by the subscriber or user, or for the sole purpose of carrying out the transmission of a communication over an electronic communications network.',
        'sq' => 'Ruajtja ose qasja teknike është rreptësisht e nevojshme për qëllimin e ligjshëm të mundësimit të një shërbimi të caktuar, të kërkuar shprehimisht nga abonenti ose përdoruesi, ose vetëm për transmetimin e një komunikimi përmes një rrjeti të komunikimeve elektronike.',
        'de' => 'Die technische Speicherung oder der Zugriff ist unbedingt erforderlich, um einen ausdrücklich vom Teilnehmer oder Nutzer angeforderten Dienst bereitzustellen, oder ausschliesslich, um eine Nachricht über ein elektronisches Kommunikationsnetz zu übertragen.',
    ],
    'preferences_text' => [
        'en' => 'The technical storage or access is necessary for the legitimate purpose of storing preferences that are not requested by the subscriber or user.',
        'sq' => 'Ruajtja ose qasja teknike është e nevojshme për qëllimin e ligjshëm të ruajtjes së preferencave që nuk janë kërkuar nga abonenti ose përdoruesi.',
        'de' => 'Die technische Speicherung oder der Zugriff ist für den rechtmässigen Zweck erforderlich, Einstellungen zu speichern, die nicht vom Teilnehmer oder Nutzer angefordert wurden.',
    ],
    'statistics_text' => [
        'en' => 'The technical storage or access that is used exclusively for statistical purposes.',
        'sq' => 'Ruajtja ose qasja teknike që përdoret vetëm për qëllime statistikore.',
        'de' => 'Die technische Speicherung oder der Zugriff erfolgt ausschliesslich zu statistischen Zwecken.',
    ],
    'statistics_text_anonymous' => [
        'en' => 'The technical storage or access that is used exclusively for anonymous statistical purposes. Without a subpoena, voluntary compliance on the part of your Internet Service Provider, or additional records from a third party, information stored or retrieved for this purpose alone cannot usually be used to identify you.',
        'sq' => 'Ruajtja ose qasja teknike që përdoret vetëm për qëllime statistikore anonime. Pa një urdhër gjyqësor, bashkëpunimin vullnetar të ofruesit të shërbimit tënd të internetit ose të dhëna shtesë nga një palë e tretë, informacioni i ruajtur ose i marrë vetëm për këtë qëllim zakonisht nuk mund të përdoret për të të identifikuar.',
        'de' => 'Die technische Speicherung oder der Zugriff erfolgt ausschliesslich zu anonymen statistischen Zwecken. Ohne eine gerichtliche Anordnung, die freiwillige Mitwirkung deines Internetanbieters oder zusätzliche Aufzeichnungen Dritter können die allein zu diesem Zweck gespeicherten oder abgerufenen Informationen in der Regel nicht dazu verwendet werden, dich zu identifizieren.',
    ],
    'marketing_text' => [
        'en' => 'The technical storage or access is required to create user profiles to send advertising, or to track the user on a website or across several websites for similar marketing purposes.',
        'sq' => 'Ruajtja ose qasja teknike nevojitet për të krijuar profile përdoruesish për dërgimin e reklamave, ose për të ndjekur përdoruesin në një faqe apo në disa faqe për qëllime të ngjashme marketingu.',
        'de' => 'Die technische Speicherung oder der Zugriff ist erforderlich, um Nutzerprofile für Werbung zu erstellen oder den Nutzer auf einer Website oder über mehrere Websites hinweg zu ähnlichen Marketingzwecken zu verfolgen.',
    ],
];

// With no ID, the Complianz factory returns an unsaved banner rather than
// resolving the site's stored default. Select an existing row explicitly.
global $wpdb;
$banner_rows = $wpdb->get_results(
    "SELECT ID, `default` AS is_default FROM {$wpdb->prefix}cmplz_cookiebanners ORDER BY ID",
    ARRAY_A
);
$defaults = array_values(array_filter($banner_rows ?: [], static fn($row) => (int) $row['is_default'] === 1));
if (count($defaults) === 1) {
    $banner_id = (int) $defaults[0]['ID'];
} elseif (count($banner_rows ?: []) === 1) {
    $banner_id = (int) $banner_rows[0]['ID'];
} else {
    WP_CLI::error('Cannot select one existing default Complianz banner unambiguously.');
}
$banner = cmplz_get_cookiebanner($banner_id);
if ((int) $banner->ID !== $banner_id) {
    WP_CLI::error('Complianz did not load the selected existing banner.');
}
$text = static function ($value) {
    return is_array($value) ? ($value['text'] ?? '') : $value;
};
$overrides = ['message_optin', 'accept', 'dismiss'];
$source_changes = [];
foreach ($dictionary as $field => $translations) {
    $existing = $text($banner->$field);
    if (in_array($field, $overrides, true)) {
        if ($existing !== $translations['en']) {
            $source_changes[] = $field;
        }
    } elseif ($existing !== $translations['en']) {
        // Preserve unexpected custom copy rather than applying a mismatched translation.
        WP_CLI::error("Unexpected existing source for $field. Review its translation before applying.");
    }
}
if ($banner->manage_consent_options !== 'show-everywhere') {
    $source_changes[] = 'manage_consent_options';
}
if (!is_array($banner->dismiss) || (int) ($banner->dismiss['show'] ?? 0) !== 1) {
    $source_changes[] = 'dismiss_visibility';
}

// Snapshot target-source translations before Complianz registration. Polylang's
// WPML compatibility layer can automatically copy an old source's translation
// onto newly changed wording; that generated copy is not an existing translation.
$before = [];
$languages = [];
foreach (['en', 'sq', 'de'] as $slug) {
    $language = PLL()->model->get_language($slug);
    if (!$language) {
        WP_CLI::error("Required existing Polylang language missing: $slug");
    }
    $languages[$slug] = $language;
    $catalogue = new PLL_MO();
    $catalogue->import_from_db($language);
    foreach ($dictionary as $field => $translations) {
        $before[$slug][$field] = $catalogue->translate_if_any($translations['en']);
    }
}
WP_CLI::log('Banner ID: ' . $banner->ID);
WP_CLI::log('Source/settings changes: ' . (implode(', ', $source_changes) ?: 'none'));
foreach ($before as $slug => $translations) {
    $preserved = count(array_filter($translations, static fn($value) => $value !== ''));
    WP_CLI::log("$slug: " . (count($dictionary) - $preserved) . " missing translations to add; $preserved existing translations to preserve.");
}
if (!$apply) {
    WP_CLI::success('Dry run only. Add the positional argument apply to save.');
    return;
}

if ($source_changes) {
    $banner->message_optin = $message;
    $banner->accept = 'Accept all';
    $banner->dismiss = ['text' => 'Reject optional', 'show' => 1];
    $banner->manage_consent_options = 'show-everywhere';
    $banner->save();
    $saved = new CMPLZ_COOKIEBANNER($banner->ID);
    foreach ($overrides as $field) {
        if ($text($saved->$field) !== $dictionary[$field]['en']) {
            WP_CLI::error("Complianz did not persist $field.");
        }
    }
    if ($saved->manage_consent_options !== 'show-everywhere') {
        WP_CLI::error('Complianz did not persist the visible manage-consent control.');
    }
    if (!is_array($saved->dismiss) || (int) ($saved->dismiss['show'] ?? 0) !== 1) {
        WP_CLI::error('Complianz did not persist the visible Reject optional control.');
    }
}

// Complianz uses both APIs. The WPML compatibility API persists registrations
// under Polylang, whereas pll_register_string alone is request-local/admin-only.
foreach ($dictionary as $field => $translations) {
    $name = $field . $banner->translation_id;
    pll_register_string($name, $translations['en'], 'complianz', strlen($translations['en']) > 80);
    icl_register_string('complianz', $name, $translations['en']);
}
foreach ($languages as $slug => $language) {
    $catalogue = new PLL_MO();
    $catalogue->import_from_db($language);
    $changed = false;
    foreach ($dictionary as $field => $translations) {
        $target = $before[$slug][$field] !== '' ? $before[$slug][$field] : $translations[$slug];
        if ($catalogue->translate_if_any($translations['en']) !== $target) {
            $catalogue->add_entry($catalogue->make_entry($translations['en'], $target));
            $changed = true;
        }
    }
    if ($changed) {
        $catalogue->export_to_db($language);
    }
    $verify = new PLL_MO();
    $verify->import_from_db($language);
    foreach ($dictionary as $field => $translations) {
        $expected = $before[$slug][$field] !== '' ? $before[$slug][$field] : $translations[$slug];
        if ($verify->translate_if_any($translations['en']) !== $expected) {
            WP_CLI::error("Translation verification failed: $slug/$field");
        }
    }
}
do_action('pll_save_strings_translations');
wp_cache_delete('cmplz_cookiebanner_' . $banner->ID, 'cmplz');
WP_CLI::success('Banner source and EN/SQ/DE translations saved and verified. Existing translations preserved. Purge page caches before checking all three public languages.');
