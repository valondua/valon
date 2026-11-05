<?php
/** Shared form schema: server validation and public field labels. */
defined("ABSPATH") || exit();
function vp_kt_choices()
{
    return [
        "destination" => [
            "Shqipni",
            "Kosovë",
            "Lugina e Preshevës",
            "Maqedonia e Veriut",
            "Mali i Zi",
            "Çamëria",
            "Vend tjetër shqiptar i origjinës",
        ],
        "timeline" => [
            "Po e mendoj",
            "Brenda 6 muajve",
            "Brenda 12 muajve",
            "Ma vonë",
            "Jam kthy tashma",
        ],
        "contribution" => [
            "Me nisë nji biznes",
            "Me rritë biznesin që kam",
            "Me gjetë punë ose me kontribuu me aftësitë e mia",
            "Projekt për komunitetin",
            "Tjetër",
        ],
        "stage" => [
            "Po e shqyrtoj idenë",
            "Kam nji plan të qartë",
            "Kam fillu",
            "Po funksionon ose po sjell të ardhuna",
        ],
        "support" => [
            "Mentorim dhe këshilla për biznes",
            "Financim ose njohje me investitorë",
            "Partnerë biznesi ose bashkëthemelues",
            "Klientë, shitje ose marketing",
            "Punësim ose lidhje profesionale",
            "Informata praktike për kthim dhe vendosje",
            "Njohje me profesionistë lokalë për ligj, administratë ose kontabilitet",
            "Teknologji, zhvillim produkti ose AI",
            "Dukshmëni dhe ndamje e historisë sime",
            "Tjetër",
        ],
        "funding_type" => [
            "Grant",
            "Investim",
            "Hua",
            "Njohje me financues",
            "S’e di ende",
        ],
        "podcast" => ["Po", "Ndoshta — më trego ma shumë", "Jo"],
    ];
}
function vp_kt_fields()
{
    // label, max length, required, input kind
    return [
        "name" => ["Emni dhe mbiemni", 160, true, "text"],
        "email" => ["Emaili", 254, true, "email"],
        "country" => ["Ku jeton sot? (shteti)", 120, false, "text"],
        "city" => ["Qyteti ku jeton sot", 120, false, "text"],
        "destination" => [
            "Ku don me u kthy ose me kontribuu?",
            100,
            false,
            "select",
        ],
        "town" => [
            "Qyteti ose fshati — shkruaje vendin saktë",
            200,
            false,
            "text",
        ],
        "timeline" => ["Ku je me kthimin?", 100, false, "select"],
        "story" => [
            "Na trego shkurt historinë tande dhe pse don me u kthy ose pse je kthy.",
            1000,
            false,
            "textarea",
        ],
        "phone" => ["Telefoni ose WhatsApp", 60, false, "tel"],
        "profile" => [
            "Website, LinkedIn ose profil social",
            1000,
            false,
            "url",
        ],
        "contribution" => ["Qysh don me kontribuu?", 150, false, "select"],
        "plan" => [
            "Çka don me ba dhe si mundemi me të ndihmu?",
            1500,
            true,
            "textarea",
        ],
        "stage" => ["Në cilën fazë je?", 100, false, "select"],
        "progress" => [
            "Çka ke ba deri tash dhe çfarë përvoje ose aftësish sjell?",
            1000,
            false,
            "textarea",
        ],
        "sustainability" => [
            "Kush përfiton, dhe qysh mundet me vazhdu kjo punë?",
            700,
            false,
            "textarea",
        ],
        "goals" => [
            "Çka synon me arritë gjatë 12 muajve të parë?",
            1000,
            false,
            "textarea",
        ],
        "obstacle" => [
            "Çka të duhet për me e ba hapin tjetër?",
            1000,
            false,
            "textarea",
        ],
        "amount" => [
            "Shuma e përafërt dhe valuta — ose “S’e di ende”",
            100,
            false,
            "text",
        ],
        "funding_use" => [
            "Për çka do të përdoreshin paret?",
            600,
            false,
            "textarea",
        ],
        "funding_type" => [
            "Çfarë mbështetjeje financiare po kërkon?",
            100,
            false,
            "select",
        ],
        "document_link" => ["Link dokumenti ose videoje", 1000, false, "url"],
        "podcast" => [
            "Don me e tregu historinë në podcastin KONCENTROHU?",
            100,
            false,
            "select",
        ],
    ];
}
function vp_kt_validate($raw)
{
    $data = [];
    $errors = [];
    $choices = vp_kt_choices();
    foreach (vp_kt_fields() as $key => [$label, $max, $required, $type]) {
        $value = $raw[$key] ?? "";
        if (!is_string($value)) {
            $errors[$key] = "Shkruaj nji përgjigje të vlefshme.";
            continue;
        }
        $value = trim(sanitize_textarea_field($value));
        $length = function_exists("mb_strlen")
            ? mb_strlen($value)
            : preg_match_all("/./us", $value);
        if (($required && $value === "") || $length > $max) {
            $errors[$key] = "Plotëso këtë fushë, deri në $max shkronja.";
        }
        if (
            $value &&
            isset($choices[$key]) &&
            !in_array($value, $choices[$key], true)
        ) {
            $errors[$key] = "Zgjedh nji prej mundësive.";
        }
        if ($value && $type === "email" && !is_email($value)) {
            $errors[$key] = "Kontrolloje adresën e emailit.";
        }
        if (
            $value &&
            $type === "url" &&
            (!filter_var($value, FILTER_VALIDATE_URL) ||
                !in_array(
                    strtolower(wp_parse_url($value, PHP_URL_SCHEME) ?? ""),
                    ["https", "http"],
                    true,
                ))
        ) {
            $errors[$key] =
                "Përdor nji link që fillon me https:// ose http://.";
        }
        $data[$key] = $value;
    }
    $support = $raw["support"] ?? [];
    if (
        !is_array($support) ||
        count($support) > 10 ||
        array_filter(
            $support,
            fn($v) => !is_string($v) ||
                !in_array($v, $choices["support"], true),
        )
    ) {
        $errors["support"] = "Zgjedh nji lloj ndihme të vlefshëm.";
        $support = [];
    }
    $data["support"] = array_values(array_unique($support));
    sort($data["support"]);
    if (($raw["consent"] ?? "") !== "1") {
        $errors["consent"] = "Lexoje dhe pranoje njoftimin e privatësisë.";
    }
    $data["email"] = strtolower($data["email"] ?? "");
    $data["privacy_version"] = "2026-11-05";
    return $errors
        ? new WP_Error("validation", "Kontrolloji fushat e shënueme.", [
            "status" => 422,
            "fields" => $errors,
        ])
        : $data;
}

/** Short public labels; stored values remain compatible with existing filters. */
function vp_kt_support_label($value)
{
    $labels = [
        "Mentorim",
        "Financim",
        "Partnerë",
        "Klientë & marketing",
        "Punësim",
        "Kthimi & vendosja",
        "Ligj & administratë",
        "Teknologji",
        "Dukshmëni",
        "Tjetër",
    ];
    $index = array_search($value, vp_kt_choices()["support"], true);
    return $index === false ? $value : $labels[$index];
}
