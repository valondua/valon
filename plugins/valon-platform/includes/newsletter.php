<?php
defined("ABSPATH") || exit();
function vp_newsletter_form($placement = "inline", $lang = "en")
{
    $id = wp_unique_id("letter-");
    ob_start();
    ?>
 <form class="newsletter-form" data-placement="<?php echo esc_attr(
     $placement,
 ); ?>" data-lang="<?php echo esc_attr($lang); ?>">
 <div class="form-row"><label class="screen-reader-text" for="<?php echo esc_attr(
     $id,
 ); ?>"><?php echo esc_html(vp_text("Email address", "Adresa e emailit", $lang)); ?></label><input id="<?php echo esc_attr($id); ?>" name="email" type="email" autocomplete="email" placeholder="<?php echo esc_attr(vp_text("Your email address", "Adresa jote e emailit", $lang)); ?>" required maxlength="254"><button type="submit"><?php echo esc_html(vp_text("Get the free letters", "Merri letrat falas", $lang)); ?></button></div>
 <div class="form-options"><label><?php echo esc_html(
     vp_text("Read in", "Lexo në", $lang),
 ); ?> <select name="language"><option value="en" <?php selected($lang, "en"); ?>>English</option><option value="sq" <?php selected($lang, "sq"); ?>>Shqip</option><option value="de" <?php selected($lang, "de"); ?>>Deutsch</option></select></label></div>
 <label class="form-consent"><input type="checkbox" name="consent" required value="1"><span><?php echo esc_html(
     vp_text(
         "Send me Letters from Valon every two weeks.",
         "Dua me marrë Letra nga Valoni çdo dy javë.",
         $lang,
     ),
 ); ?></span></label>
 <label class="honey" aria-hidden="true">Leave empty<input name="website" tabindex="-1" autocomplete="off"></label>
 <p class="form-note"><?php echo esc_html(
     vp_text(
         "Free. Unsubscribe anytime. Confirm your email to join.",
         "Falas. Çregjistrohu kur të duash. Konfirmo emailin për me u bashku.",
         $lang,
     ),
 ); ?> <a href="<?php echo esc_url(function_exists("valon_url") ? valon_url("privacy-policy", $lang) : home_url("/privacy-policy/")); ?>"><?php echo esc_html(vp_text("Privacy", "Privatësia", $lang)); ?></a></p>
 <p class="form-status" role="status" aria-live="polite"></p><noscript><p><?php echo esc_html(
     vp_text(
         "Please enable JavaScript to use this signup form.",
         "Aktivizo JavaScript për me përdorë formularin.",
         $lang,
     ),
 ); ?></p></noscript></form>
 <?php return ob_get_clean();
}
function vp_subscribe($request)
{
    $requested_language = $request->get_param("language");
    $lang = in_array($requested_language, ["en", "sq", "de"], true)
        ? $requested_language
        : "en";
    $reply = fn($message, $status = 200) => new WP_REST_Response(
        ["message" => $message],
        $status,
    );
    $origin = $request->get_header("origin");
    $site = wp_parse_url(home_url());
    if ($origin) {
        $o = wp_parse_url($origin);
        if (
            !$o ||
            ($o["scheme"] ?? "") !== $site["scheme"] ||
            ($o["host"] ?? "") !== $site["host"] ||
            ($o["port"] ?? null) !== ($site["port"] ?? null)
        ) {
            return $reply("Invalid origin.", 403);
        }
    }
    if ($request->get_param("website")) {
        return $reply(
            vp_text(
                "Check your inbox to confirm.",
                "Kontrollo emailin për konfirmim.",
                $lang,
            ),
        );
    }
    $email = trim((string) $request->get_param("email"));
    if (
        strlen($email) > 254 ||
        !is_email($email) ||
        $request->get_param("consent") !== true
    ) {
        return $reply(
            vp_text(
                "Enter a valid email and agree to receive the letters.",
                "Shkruaje emailin e saktë dhe prano marrjen e letrave.",
                $lang,
            ),
            400,
        );
    }
    $ip = sanitize_text_field($_SERVER["REMOTE_ADDR"] ?? "");
    $bucket = "vp_signup_" . hash_hmac("sha256", $ip, wp_salt("nonce"));
    $attempts = (int) get_transient($bucket);
    if ($attempts >= 8) {
        return $reply(
            vp_text(
                "Please try again in an hour.",
                "Provo prapë pas një ore.",
                $lang,
            ),
            429,
        );
    }
    set_transient($bucket, $attempts + 1, HOUR_IN_SECONDS);
    $key = vp_secret("VP_MAILCHIMP_API_KEY");
    $list = vp_secret("VP_MAILCHIMP_LIST_ID");
    if (!$key || !$list || !preg_match('/-([a-z]+\d+)$/', $key, $match)) {
        return $reply(
            vp_text(
                "Signup is not available yet. Please check back soon.",
                "Regjistrimi ende s’është i hapur. Provo së shpejti.",
                $lang,
            ),
            503,
        );
    }
    $base =
        "https://" .
        $match[1] .
        ".api.mailchimp.com/3.0/lists/" .
        rawurlencode($list) .
        "/members";
    $source = sanitize_key((string) $request->get_param("source"));
    $allowed = [
        "tiktok",
        "instagram",
        "facebook",
        "linkedin",
        "x",
        "newsletter",
    ];
    if (!in_array($source, $allowed, true)) {
        $source = "website";
    }
    // Create only: an unauthenticated signup must never modify an existing subscriber's preferences.
    // VLANG is explicit: Albanian is not a built-in Mailchimp form translation.
    $body = [
        "email_address" => $email,
        "status" => "pending",
        "merge_fields" => ["VLANG" => $lang, "VSOURCE" => $source],
    ];
    $r = wp_remote_post($base, [
        "timeout" => 15,
        "redirection" => 0,
        "headers" => [
            "Authorization" => "Basic " . base64_encode("valon:" . $key),
            "Content-Type" => "application/json",
        ],
        "body" => wp_json_encode($body),
        "limit_response_size" => 100000,
    ]);
    $member = is_wp_error($r)
        ? []
        : json_decode(wp_remote_retrieve_body($r), true);
    $existing =
        !is_wp_error($r) &&
        wp_remote_retrieve_response_code($r) === 400 &&
        ($member["title"] ?? "") === "Member Exists";
    if (
        is_wp_error($r) ||
        (wp_remote_retrieve_response_code($r) >= 300 && !$existing)
    ) {
        return $reply(
            vp_text(
                "We couldn’t complete the request. Please try again later.",
                "S’mundëm me e përfundu kërkesën. Provo pak ma vonë.",
                $lang,
            ),
            502,
        );
    }
    // Existing subscribed, pending and unsubscribed contacts all receive the same public response.
    // Their details can only be changed through Mailchimp's secure preference or resubscription flow.
    return $reply(
        vp_text(
            "If your address is eligible, you’ll receive a confirmation email. Already subscribed? You’re all set.",
            "Nëse adresa jote mund të regjistrohet, do ta marrësh emailin e konfirmimit. Nëse je i regjistruar, je në rregull.",
            $lang,
        ),
    );
}
add_action("rest_api_init", function () {
    register_rest_route("valon/v1", "/subscribe", [
        "methods" => "POST",
        "permission_callback" => "__return_true",
        "callback" => "vp_subscribe",
    ]);
});
