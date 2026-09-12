<?php
// Integration tests exercise real WordPress storage with isolated fake HTTP responses.
// They never send email or call a social platform.
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Tests are local-only.");
}
$GLOBALS["vp_qa_passed"] = 0;
$created = [];
$requests = [];
$scenario = "ok";
function check($condition, $label)
{
    $passed = &$GLOBALS["vp_qa_passed"];
    if (!$condition) {
        throw new RuntimeException("FAIL: " . $label);
    }
    $passed++;
    WP_CLI::line("PASS " . $label);
}
function response($body, $code = 200, $headers = [])
{
    return [
        "response" => ["code" => $code, "message" => "fixture"],
        "body" => wp_json_encode($body),
        "headers" => $headers,
        "cookies" => [],
    ];
}
putenv("VP_TIKTOK_ACCESS_TOKEN=fixture-access");
putenv("VP_TIKTOK_REFRESH_TOKEN=fixture-refresh");
putenv("VP_TIKTOK_ACCOUNT_ID=fixture-owner");
putenv("VP_TIKTOK_CLIENT_ID=fixture-client");
putenv("VP_TIKTOK_CLIENT_SECRET=fixture-secret");
putenv("VP_MAILCHIMP_API_KEY=fixture-key-us1");
putenv("VP_MAILCHIMP_LIST_ID=fixture-list");
$mock = function ($pre, $args, $url) use (&$requests, &$scenario) {
    $requests[] = [$url, $args];
    if (str_contains($url, "mailchimp.com")) {
        if ($scenario === "mail_error") {
            return response(["detail" => "secret subscriber data"], 500);
        }
        if (str_ends_with($url, "/tags")) {
            return response([], 204);
        }
        if ($scenario === "existing") {
            return response(["title" => "Member Exists"], 400);
        }
        return response([
            "status" => "pending",
            "email_address" => "fixture@example.invalid",
        ]);
    }

    if (str_contains($url, "graph.instagram.com")) {
        if (str_contains($url, "refresh_access_token")) {
            return response([
                "access_token" => "ig-renewed",
                "expires_in" => 5184000,
            ]);
        }
        if (str_contains($url, "/media?")) {
            return response([
                "data" => [
                    [
                        "id" => "ig-fixture-1",
                        "caption" => "Instagram fixture",
                        "media_type" => "CAROUSEL_ALBUM",
                        "permalink" =>
                            "https://www.instagram.com/p/VALON_FIXTURE1/",
                        "timestamp" => gmdate("c", time() - 200),
                    ],
                ],
            ]);
        }
        if (
            str_contains($url, "/ig-fixture-1?") &&
            $scenario === "meta_deleted"
        ) {
            return response(["error" => ["code" => 100]], 404);
        }
        if (
            str_contains($url, "/ig-fixture-1?") &&
            $scenario === "meta_permission"
        ) {
            return response(["error" => ["code" => 190]], 403);
        }
        return response(["id" => "fixture-ig-owner"]);
    }
    if (str_contains($url, "graph.facebook.com")) {
        if (str_contains($url, "/posts?")) {
            return response([
                "data" => [
                    [
                        "id" => "fb-fixture-1",
                        "from" => ["id" => "fixture-fb-owner"],
                        "message" => "Owned text fixture",
                        "is_published" => true,
                        "is_hidden" => false,
                        "privacy" => ["value" => "EVERYONE"],
                        "status_type" => "mobile_status_update",
                        "permalink_url" =>
                            "https://www.facebook.com/valonasanidua/posts/999900002",
                        "created_time" => gmdate("c", time() - 200),
                    ],
                    [
                        "id" => "fb-other",
                        "from" => ["id" => "other-page"],
                        "message" => "Other page",
                        "permalink_url" =>
                            "https://www.facebook.com/other/posts/999",
                    ],
                    [
                        "id" => "fb-repost",
                        "from" => ["id" => "fixture-fb-owner"],
                        "message" => "Repost",
                        "status_type" => "shared_story",
                        "permalink_url" =>
                            "https://www.facebook.com/valonasanidua/posts/999900003",
                    ],
                ],
            ]);
        }
        if (
            $scenario === "fb_private" &&
            str_contains($url, "/fb-fixture-1?")
        ) {
            return response([
                "id" => "fb-fixture-1",
                "is_published" => false,
                "privacy" => ["value" => "SELF"],
            ]);
        }
        return response(["id" => "fixture-fb-owner"]);
    }

    if ($scenario === "rate") {
        return response([], 429, ["retry-after" => 3600]);
    }
    if ($scenario === "network") {
        return new WP_Error("http_request_failed", "fixture network");
    }
    if (str_contains($url, "oauth/token")) {
        return response([
            "access_token" => "renewed-access",
            "refresh_token" => "rotated-refresh",
            "expires_in" => 86400,
        ]);
    }
    if (str_contains($url, "user/info")) {
        if (
            $scenario === "expired" &&
            ($args["headers"]["Authorization"] ?? "") ===
                "Bearer fixture-access"
        ) {
            return response(
                ["error" => ["code" => "access_token_invalid"]],
                401,
            );
        }
        return response([
            "data" => [
                "user" => [
                    "open_id" =>
                        $scenario === "wrong_owner"
                            ? "someone-else"
                            : "fixture-owner",
                ],
            ],
            "error" => ["code" => "ok"],
        ]);
    }
    if (str_contains($url, "video/list")) {
        return response([
            "data" => [
                "videos" => [
                    [
                        "id" => "999900001",
                        "share_url" =>
                            "https://www.tiktok.com/@valon_asani/video/999900001",
                        "video_description" => "Test fixture only",
                        "create_time" => time() - 100,
                        "cover_image_url" => "",
                    ],
                ],
                "has_more" => false,
            ],
            "error" => ["code" => "ok"],
        ]);
    }
    if (str_contains($url, "video/query")) {
        return response([
            "data" => [
                "videos" =>
                    $scenario === "deleted" ? [] : [["id" => "999900001"]],
            ],
            "error" => ["code" => "ok"],
        ]);
    }
    return response(["id" => "fixture-owner"]);
};
add_filter("pre_http_request", $mock, 10, 3);
$extra_options = [];
foreach (["instagram", "facebook"] as $p) {
    foreach (["status", "token", "lock"] as $kind) {
        $key = "vp_" . $kind . "_" . $p;
        $extra_options[$key] = get_option($key, null);
        delete_option($key);
    }
}
$old_status = get_option("vp_status_tiktok", null);
$old_token = get_option("vp_token_tiktok", null);
$old_lock = get_option("vp_lock_tiktok", null);
try {
    check(
        VP_Social::unseal(VP_Social::seal(["token" => "private"])) === [
            "token" => "private",
        ],
        "encrypted tokens round-trip",
    );
    check(VP_Social::unseal("corrupt") === [], "corrupt token rejected");
    check(
        !VP_Social::valid_url(
            "tiktok",
            "https://evil.example/@valon_asani/video/1",
        ),
        "reject foreign embed host",
    );
    check(
        !VP_Social::valid_url(
            "tiktok",
            "https://www.tiktok.com/@someone_else/video/1",
        ),
        "reject another TikTok creator",
    );
    check(
        !VP_Social::valid_url("x", "https://x.com/Other/status/1"),
        "reject another X creator",
    );
    delete_option("vp_token_tiktok");
    delete_option("vp_status_tiktok");
    delete_option("vp_lock_tiktok");
    $scenario = "wrong_owner";
    $r = VP_Social::sync("tiktok", true);
    check(
        is_wp_error($r) && $r->get_error_code() === "identity",
        "connected identity must match owner",
    );
    $scenario = "ok";
    $r = VP_Social::sync("tiktok", true);
    check($r === 1, "owned public video imports");
    $item = get_posts([
        "post_type" => "valon_social",
        "meta_key" => "_vp_key",
        "meta_value" => "tiktok:999900001",
        "numberposts" => 1,
        "suppress_filters" => true,
    ])[0];
    $created[] = $item->ID;
    VP_Social::sync("tiktok", true);
    check(
        count(
            get_posts([
                "post_type" => "valon_social",
                "meta_key" => "_vp_key",
                "meta_value" => "tiktok:999900001",
                "numberposts" => 10,
                "suppress_filters" => true,
            ]),
        ) === 1,
        "repeat import produces one record",
    );
    update_post_meta($item->ID, "_vp_hidden", "1");
    update_post_meta($item->ID, "_vp_featured", "1");
    VP_Social::sync("tiktok", true);
    check(
        get_post_meta($item->ID, "_vp_hidden", true) === "1" &&
            get_post_meta($item->ID, "_vp_featured", true) === "1",
        "editorial settings survive refresh",
    );
    $req = new WP_REST_Request("GET", "/valon/v1/embed/" . $item->ID);
    $r = rest_do_request($req);
    check(
        $r->get_status() === 404,
        "hidden post is unavailable in public embed API",
    );
    update_post_meta($item->ID, "_vp_hidden", "0");
    $draft = vp_make_draft($item->ID);
    $created[] = $draft;
    check(get_post_status($draft) === "draft", "social idea creates draft");
    check(
        vp_make_draft($item->ID) === $draft,
        "create-draft action is idempotent",
    );
    check(
        !str_contains(
            get_post_field("post_content", $draft),
            "Test fixture only",
        ),
        "oEmbed and feed captions never become article copy",
    );
    wp_update_post(["ID" => $draft, "post_status" => "publish"]);
    check(
        get_post_status($draft) === "draft",
        "unapproved article cannot publish",
    );
    update_post_meta(
        $draft,
        "_vp_approved_hash",
        vp_review_hash(
            get_the_title($draft),
            get_post_field("post_content", $draft),
        ),
    );
    wp_update_post(["ID" => $draft, "post_status" => "publish"]);
    check(
        get_post_status($draft) === "publish",
        "approved exact content can publish",
    );
    wp_update_post([
        "ID" => $draft,
        "post_content" => "Changed after approval",
    ]);
    check(
        get_post_status($draft) === "draft",
        "changed content invalidates approval",
    );
    $new = wp_insert_post([
        "post_type" => "post",
        "post_title" => "Unmarked fixture",
        "post_status" => "publish",
    ]);
    $created[] = $new;
    check(
        get_post_status($new) === "draft",
        "new articles require review even without review metadata",
    );
    $scenario = "rate";
    $r = VP_Social::sync("tiktok", true);
    check(
        is_wp_error($r) && $r->get_error_code() === "rate_limit",
        "rate limits are reported",
    );
    check(
        get_option("vp_status_tiktok")["retry_at"] >= time() + 3590,
        "Retry-After enforced",
    );
    check(
        get_post_meta($item->ID, "_vp_availability", true) === "public",
        "rate limits retain usable cached content",
    );
    $r = VP_Social::sync("tiktok");
    check(
        is_wp_error($r) && $r->get_error_code() === "backoff",
        "scheduled retries respect backoff",
    );
    $scenario = "network";
    VP_Social::sync("tiktok", true);
    check(
        get_post_meta($item->ID, "_vp_availability", true) === "public",
        "network outages preserve cache",
    );
    $scenario = "expired";
    delete_option("vp_token_tiktok");
    $r = VP_Social::sync("tiktok", true);
    check(
        $r === 1 &&
            VP_Social::token("tiktok")["access_token"] === "renewed-access",
        "expired token rotates and retries once",
    );
    check(
        !str_contains(get_option("vp_token_tiktok"), "renewed-access"),
        "rotated credentials are encrypted at rest",
    );
    $scenario = "deleted";
    VP_Social::sync("tiktok", true);
    check(
        get_post_meta($item->ID, "_vp_availability", true) === "unavailable",
        "confirmed removed video hidden",
    );
    $r = rest_do_request(
        new WP_REST_Request("GET", "/valon/v1/embed/" . $item->ID),
    );
    check(
        $r->get_status() === 404,
        "deleted embed has useful unavailable response",
    );
    $scenario = "ok";
    VP_Social::sync("tiktok", true);
    $r = rest_do_request(
        new WP_REST_Request("GET", "/valon/v1/embed/" . $item->ID),
    );
    check(
        $r->get_status() === 200 &&
            str_contains(
                $r->get_data()["iframe"],
                "https://www.tiktok.com/player/v1/",
            ),
        "public embed uses official TikTok player",
    );
    check(
        !str_contains(wp_json_encode($r->get_data()), "fixture-access"),
        "public embed response contains no credentials",
    );
    $count = count($requests);
    VP_Social::lock("tiktok");
    $r = VP_Social::sync("tiktok", true);
    check(
        is_wp_error($r) &&
            $r->get_error_code() === "locked" &&
            count($requests) === $count,
        "concurrent import is locked",
    );
    delete_option("vp_lock_tiktok");

    putenv("VP_INSTAGRAM_ACCESS_TOKEN=ig-fixture");
    putenv("VP_INSTAGRAM_ACCOUNT_ID=fixture-ig-owner");
    putenv("VP_INSTAGRAM_EXPIRES_AT=" . (time() - 1));
    putenv("VP_FACEBOOK_ACCESS_TOKEN=fb-fixture");
    putenv("VP_FACEBOOK_ACCOUNT_ID=fixture-fb-owner");
    $scenario = "ok";
    $r = VP_Social::sync("instagram", true);
    check($r === 1, "Instagram own-account carousel imports");
    $ig = get_posts([
        "post_type" => "valon_social",
        "meta_key" => "_vp_key",
        "meta_value" => "instagram:ig-fixture-1",
        "numberposts" => 1,
        "suppress_filters" => true,
    ])[0];
    $created[] = $ig->ID;
    check(
        VP_Social::token("instagram")["access_token"] === "ig-renewed",
        "Instagram long-lived token refreshes before expiry",
    );
    $r = VP_Social::sync("facebook", true);
    check(
        $r === 1,
        "Facebook imports own Page post and excludes reposts and other authors",
    );
    $fb = get_posts([
        "post_type" => "valon_social",
        "meta_key" => "_vp_key",
        "meta_value" => "facebook:fb-fixture-1",
        "numberposts" => 1,
        "suppress_filters" => true,
    ])[0];
    $created[] = $fb->ID;
    check(
        get_post_meta($fb->ID, "_vp_media_type", true) === "text",
        "Facebook text post remains supported",
    );
    $scenario = "fb_private";
    VP_Social::revalidate("facebook", "fb-fixture");
    check(
        get_post_meta($fb->ID, "_vp_availability", true) === "unavailable",
        "Confirmed private or unpublished Facebook post is hidden",
    );
    $scenario = "meta_permission";
    VP_Social::revalidate(
        "instagram",
        VP_Social::token("instagram")["access_token"],
    );
    check(
        get_post_meta($ig->ID, "_vp_availability", true) === "public",
        "Meta permission errors preserve cached content",
    );
    $scenario = "meta_deleted";
    VP_Social::revalidate(
        "instagram",
        VP_Social::token("instagram")["access_token"],
    );
    check(
        get_post_meta($ig->ID, "_vp_availability", true) === "unavailable",
        "Explicit Meta object deletion hides the cached item",
    );
    $scenario = "ok";
    $li = VP_Social::upsert("linkedin", [
        "id" => "activity-9999",
        "url" =>
            "https://www.linkedin.com/posts/valon-asani_building-activity-9999-abcd",
        "date" => gmdate("Y-m-d H:i:s", time() - 500),
        "caption" => "LinkedIn fixture",
        "type" => "link",
    ]);
    $created[] = $li;
    $r = rest_do_request(new WP_REST_Request("GET", "/valon/v1/embed/" . $li));
    check(
        str_contains($r->get_data()["iframe"], "urn:li:activity:9999"),
        "Curated LinkedIn URL resolves official embed",
    );
    wp_update_post(["ID" => $li, "post_status" => "draft"]);
    check(
        rest_do_request(
            new WP_REST_Request("GET", "/valon/v1/embed/" . $li),
        )->get_status() === 404,
        "Unpublished feed records cannot be fetched publicly",
    );

    $_SERVER["REMOTE_ADDR"] = "127.0.0.98";
    $bucket =
        "vp_signup_" .
        hash_hmac("sha256", $_SERVER["REMOTE_ADDR"], wp_salt("nonce"));
    delete_transient($bucket);
    $signup = new WP_REST_Request("POST", "/valon/v1/subscribe");
    $signup->set_body_params([
        "email" => "fixture@example.invalid",
        "language" => "sq",
        "consent" => true,
        "source" => "tiktok",
    ]);
    $requests = [];
    $r = vp_subscribe($signup);
    check($r->get_status() === 200, "signup submits to mocked Mailchimp");
    $body = json_decode($requests[0][1]["body"], true);
    check(
        $body["status"] === "pending" &&
            str_ends_with($requests[0][0], "/members"),
        "new subscription requires double opt-in without resubscribing existing contacts",
    );
    check(
        $body["merge_fields"]["VLANG"] === "sq",
        "subscriber language reaches Mailchimp",
    );
    check(
        !str_contains(
            wp_json_encode($r->get_data()),
            "fixture@example.invalid",
        ),
        "signup response never echoes email",
    );
    $signup->set_param("language", "de");
    $requests = [];
    $r = vp_subscribe($signup);
    $body = json_decode($requests[0][1]["body"], true);
    check(
        $body["merge_fields"]["VLANG"] === "de" &&
            $body["status"] === "pending",
        "German subscribers retain their language and double opt-in",
    );
    check(
        str_contains($r->get_data()["message"], "Bestätigungs-E-Mail"),
        "German subscription response is localized",
    );
    check(
        pll_get_post_language(
            (int) get_option("valon_pages")["de"]["home"],
            "locale",
        ) === "de_CH",
        "German edition uses Swiss Standard German locale",
    );
    check(
        preg_match(
            '/value="de"\s+selected=/',
            vp_newsletter_form("qa", "de"),
        ) && !str_contains(vp_newsletter_form("qa", "de"), "ß"),
        "German form selects German and uses Swiss spelling",
    );
    $signup->set_param("language", "xx");
    $requests = [];
    vp_subscribe($signup);
    check(
        json_decode($requests[0][1]["body"], true)["merge_fields"]["VLANG"] ===
            "en",
        "Unsupported signup language safely falls back to English",
    );
    $signup->set_param("language", "sq");
    $scenario = "existing";
    $requests = [];
    vp_subscribe($signup);
    check(
        count($requests) === 1,
        "existing subscribed contact is not silently retagged or resubscribed",
    );
    $scenario = "mail_error";
    $r = vp_subscribe($signup);
    check(
        $r->get_status() === 502 &&
            !str_contains(wp_json_encode($r->get_data()), "secret subscriber"),
        "provider details do not leak on errors",
    );
    $signup->set_header("Origin", "https://attacker.invalid");
    check(
        vp_subscribe($signup)->get_status() === 403,
        "cross-origin signup rejected",
    );
    $signup->set_header("Origin", "");
    $signup->set_param("consent", false);
    check(
        vp_subscribe($signup)->get_status() === 400,
        "signup requires explicit consent",
    );
    check(
        wp_next_scheduled("vp_hourly_sync") !== false,
        "hourly background task registered",
    );
    check(
        !get_post_type_object("valon_social")->publicly_queryable &&
            !get_post_type_object("valon_social")->show_in_rest,
        "social records have no indexable individual article endpoint",
    );
    $previous_user = get_current_user_id();
    wp_set_current_user(1);
    $publish_request = new WP_REST_Request("POST", "/wp/v2/posts");
    $publish_request->set_body_params([
        "title" => "REST blocked fixture",
        "content" => "Unapproved test content",
        "status" => "publish",
    ]);
    $blocked = rest_do_request($publish_request);
    check(
        $blocked->get_status() === 409,
        "Gutenberg receives a clear approval-required error",
    );
    wp_set_current_user($previous_user);
    WP_CLI::success(
        $GLOBALS["vp_qa_passed"] . " integration assertions passed.",
    );
} finally {
    remove_filter("pre_http_request", $mock, 10);
    foreach (array_unique($created) as $id) {
        wp_delete_post($id, true);
    }
    foreach (
        [
            "vp_status_tiktok" => $old_status,
            "vp_token_tiktok" => $old_token,
            "vp_lock_tiktok" => $old_lock,
        ]
        as $name => $value
    ) {
        if ($value === null) {
            delete_option($name);
        } else {
            update_option($name, $value, false);
        }
    }
    foreach ($extra_options as $name => $value) {
        if ($value === null) {
            delete_option($name);
        } else {
            update_option($name, $value, false);
        }
    }
    if (isset($bucket)) {
        delete_transient($bucket);
    }
}
