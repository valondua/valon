<?php
defined("ABSPATH") || exit();
final class VP_Social
{
    const PLATFORMS = ["tiktok", "instagram", "facebook", "linkedin", "x"];
    static function register()
    {
        register_post_type("valon_social", [
            "label" => "Social queue",
            "public" => false,
            "publicly_queryable" => false,
            "show_ui" => true,
            "show_in_rest" => false,
            "exclude_from_search" => true,
            "supports" => ["title"],
            "menu_icon" => "dashicons-format-video",
            "capability_type" => "post",
            "map_meta_cap" => true,
        ]);
    }
    static function profiles()
    {
        return [
            "tiktok" => "https://www.tiktok.com/@valon_asani",
            "instagram" => "https://www.instagram.com/valonasanidua/",
            "facebook" => "https://www.facebook.com/valonasanidua",
            "linkedin" => "https://www.linkedin.com/in/valon-asani/",
            "x" => "https://x.com/ValonAsaniDua",
        ];
    }
    static function config($p)
    {
        $prefix = "VP_" . strtoupper($p) . "_";
        return [
            "token" => vp_secret($prefix . "ACCESS_TOKEN"),
            "id" => vp_secret($prefix . "ACCOUNT_ID"),
            "refresh" => vp_secret($prefix . "REFRESH_TOKEN"),
            "client" => vp_secret($prefix . "CLIENT_ID"),
            "secret" => vp_secret($prefix . "CLIENT_SECRET"),
            "expires_at" => (int) vp_secret($prefix . "EXPIRES_AT"),
        ];
    }
    static function seal($data)
    {
        $iv = random_bytes(12);
        $tag = "";
        $cipher = openssl_encrypt(
            wp_json_encode($data),
            "aes-256-gcm",
            hash("sha256", wp_salt("auth"), true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );
        return base64_encode($iv . $tag . $cipher);
    }
    static function unseal($value)
    {
        $raw = base64_decode($value, true);
        if (!$raw || strlen($raw) < 29) {
            return [];
        }
        $plain = openssl_decrypt(
            substr($raw, 28),
            "aes-256-gcm",
            hash("sha256", wp_salt("auth"), true),
            OPENSSL_RAW_DATA,
            substr($raw, 0, 12),
            substr($raw, 12, 16),
        );
        return $plain ? (json_decode($plain, true) ?: []) : [];
    }
    static function token($p)
    {
        $config = self::config($p);
        $stored = self::unseal(get_option("vp_token_" . $p, ""));
        if (
            ($stored["fingerprint"] ?? "") !==
            hash("sha256", $config["token"])
        ) {
            $stored = [];
        }
        return $stored + [
            "access_token" => $config["token"],
            "refresh_token" => $config["refresh"],
            "expires_at" => $config["expires_at"],
        ];
    }
    static function request($url, $args = [])
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (
            !in_array(
                $host,
                [
                    "open.tiktokapis.com",
                    "graph.instagram.com",
                    "graph.facebook.com",
                    "www.tiktok.com",
                    "www.linkedin.com",
                    "publish.twitter.com",
                ],
                true,
            )
        ) {
            return new WP_Error("host", "Unsupported API host.");
        }
        $response = wp_safe_remote_request(
            $url,
            $args + [
                "timeout" => 15,
                "redirection" => 0,
                "limit_response_size" => 2000000,
            ],
        );
        if (is_wp_error($response)) {
            return new WP_Error("network", "Platform could not be reached.");
        }
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code === 429) {
            return new WP_Error(
                "rate_limit",
                "Platform rate limit; retry later.",
                [
                    "retry_after" => min(
                        21600,
                        max(
                            300,
                            (int) wp_remote_retrieve_header(
                                $response,
                                "retry-after",
                            ),
                        ),
                    ),
                ],
            );
        }
        if (
            $code === 401 ||
            $code === 403 ||
            in_array($data["error"]["code"] ?? 0, [190, 102], true) ||
            in_array(
                $data["error"]["code"] ?? "",
                ["access_token_invalid", "scope_not_authorized"],
                true,
            )
        ) {
            return new WP_Error(
                "auth",
                "Reconnect this account; authorization expired or is insufficient.",
            );
        }
        if (
            $code < 200 ||
            $code >= 300 ||
            !is_array($data) ||
            (!empty($data["error"]) && ($data["error"]["code"] ?? "") !== "ok")
        ) {
            return new WP_Error(
                "platform",
                "Platform returned an unsuccessful response.",
                ["status" => $code],
            );
        }
        return $data;
    }
    static function refresh($p, $force = false)
    {
        $t = self::token($p);
        $c = self::config($p);
        if (
            !$force &&
            (!$t["expires_at"] || $t["expires_at"] > time() + 21600)
        ) {
            return $t;
        }
        if (
            $p === "tiktok" &&
            $t["refresh_token"] &&
            $c["client"] &&
            $c["secret"]
        ) {
            $data = self::request(
                "https://open.tiktokapis.com/v2/oauth/token/",
                [
                    "method" => "POST",
                    "body" => [
                        "client_key" => $c["client"],
                        "client_secret" => $c["secret"],
                        "grant_type" => "refresh_token",
                        "refresh_token" => $t["refresh_token"],
                    ],
                ],
            );
        } elseif ($p === "instagram") {
            $data = self::request(
                add_query_arg(
                    [
                        "grant_type" => "ig_refresh_token",
                        "access_token" => $t["access_token"],
                    ],
                    "https://graph.instagram.com/refresh_access_token",
                ),
            );
        } else {
            return new WP_Error("auth", "Account reconnection is required.");
        }
        if (is_wp_error($data)) {
            return $data;
        }
        if (empty($data["access_token"])) {
            return new WP_Error("auth", "Token renewal failed.");
        }
        $next = [
            "access_token" => $data["access_token"],
            "refresh_token" => $data["refresh_token"] ?? $t["refresh_token"],
            "expires_at" => time() + (int) ($data["expires_in"] ?? 3600),
            "fingerprint" => hash("sha256", $c["token"]),
        ];
        update_option("vp_token_" . $p, self::seal($next), false);
        return $next;
    }
    static function lock($p)
    {
        $key = "vp_lock_" . $p;
        if (add_option($key, time(), "", "no")) {
            return true;
        }
        if ((int) get_option($key) < time() - 600) {
            delete_option($key);
            return add_option($key, time(), "", "no");
        }
        return false;
    }
    static function sync_all()
    {
        foreach (["tiktok", "instagram", "facebook"] as $p) {
            self::sync($p);
        }
    }
    static function sync($p, $force = false)
    {
        if (!in_array($p, ["tiktok", "instagram", "facebook"], true)) {
            return new WP_Error("platform", "Not an automatic source.");
        }
        $c = self::config($p);
        $state = get_option("vp_status_" . $p, []);
        if (!$c["token"] || !$c["id"]) {
            return new WP_Error(
                "unconfigured",
                "Server credentials and expected account ID are required.",
            );
        }
        if (!$force && ($state["retry_at"] ?? 0) > time()) {
            return new WP_Error("backoff", "Waiting before retry.");
        }
        if (!self::lock($p)) {
            return new WP_Error("locked", "A sync is already running.");
        }
        try {
            $t = self::refresh($p);
            if (is_wp_error($t)) {
                self::status($p, $t);
                return $t;
            }
            $result = self::fetch($p, $t["access_token"], $c["id"]);
            if (
                is_wp_error($result) &&
                $result->get_error_code() === "auth" &&
                $p !== "facebook"
            ) {
                $t = self::refresh($p, true);
                $result = is_wp_error($t)
                    ? $t
                    : self::fetch($p, $t["access_token"], $c["id"]);
            }
            if (is_wp_error($result)) {
                self::status($p, $result);
                return $result;
            }
            $count = 0;
            foreach ($result["items"] as $item) {
                $saved = self::upsert($p, $item);
                if (!is_wp_error($saved)) {
                    $count++;
                }
            }
            self::revalidate($p, $t["access_token"]);
            update_option(
                "vp_status_" . $p,
                [
                    "state" => "healthy",
                    "last_success" => time(),
                    "count" => $count,
                    "failures" => 0,
                    "message" => "Connected. Last sync completed.",
                    "cursor" => $result["cursor"] ?? null,
                ],
                false,
            );
            return $count;
        } finally {
            delete_option("vp_lock_" . $p);
        }
    }
    static function status($p, $error)
    {
        $old = get_option("vp_status_" . $p, []);
        $n = min(8, ($old["failures"] ?? 0) + 1);
        $detail = $error->get_error_data();
        $delay =
            $error->get_error_code() === "auth"
                ? 21600
                : min(21600, 300 * 2 ** ($n - 1));
        if (is_array($detail)) {
            $delay = max($delay, $detail["retry_after"] ?? 0);
        }
        update_option(
            "vp_status_" . $p,
            [
                "state" => $error->get_error_code(),
                "message" => $error->get_error_message(),
                "last_success" => $old["last_success"] ?? 0,
                "failures" => $n,
                "retry_at" => time() + $delay,
                "cursor" => $old["cursor"] ?? null,
            ],
            false,
        );
    }
    static function fetch($p, $token, $id)
    {
        $headers = ["Authorization" => "Bearer " . $token];
        $items = [];
        $cursor = null;
        $round = 0;
        if ($p === "tiktok") {
            $who = self::request(
                "https://open.tiktokapis.com/v2/user/info/?fields=open_id",
                ["headers" => $headers],
            );
            if (is_wp_error($who)) {
                return $who;
            }
            if (($who["data"]["user"]["open_id"] ?? "") !== $id) {
                return new WP_Error(
                    "identity",
                    "Connected TikTok account does not match the configured account.",
                );
            }
            do {
                $body = ["max_count" => 20];
                if ($cursor) {
                    $body["cursor"] = $cursor;
                }
                $data = self::request(
                    "https://open.tiktokapis.com/v2/video/list/?fields=id,title,video_description,create_time,cover_image_url,share_url",
                    [
                        "method" => "POST",
                        "headers" => $headers + [
                            "Content-Type" => "application/json",
                        ],
                        "body" => wp_json_encode($body),
                    ],
                );
                if (is_wp_error($data)) {
                    return $data;
                }
                foreach ($data["data"]["videos"] ?? [] as $v) {
                    $items[] = [
                        "id" => $v["id"],
                        "url" => $v["share_url"],
                        "caption" =>
                            $v["video_description"] ?? ($v["title"] ?? ""),
                        "date" => gmdate(
                            "Y-m-d H:i:s",
                            (int) $v["create_time"],
                        ),
                        "type" => "video",
                        "cover" => $v["cover_image_url"] ?? "",
                    ];
                }
                $cursor = !empty($data["data"]["has_more"])
                    ? $data["data"]["cursor"] ?? null
                    : null;
            } while ($cursor && ++$round < 5);
        } else {
            $base =
                $p === "instagram"
                    ? "https://graph.instagram.com/v26.0/"
                    : "https://graph.facebook.com/v26.0/";
            $who = self::request($base . rawurlencode($id) . "?fields=id", [
                "headers" => $headers,
            ]);
            if (is_wp_error($who)) {
                return $who;
            }
            if ((string) ($who["id"] ?? "") !== $id) {
                return new WP_Error(
                    "identity",
                    "Connected account does not match the configured account.",
                );
            }
            do {
                $params =
                    $p === "instagram"
                        ? [
                            "fields" =>
                                "id,caption,media_type,media_url,thumbnail_url,permalink,timestamp",
                            "limit" => 50,
                        ]
                        : [
                            "fields" =>
                                "id,message,created_time,permalink_url,from,attachments{media_type,media,url},status_type,is_published,is_hidden,privacy",
                            "limit" => 50,
                        ];
                if ($cursor) {
                    $params["after"] = $cursor;
                }
                $data = self::request(
                    add_query_arg(
                        $params,
                        $base .
                            rawurlencode($id) .
                            ($p === "instagram" ? "/media" : "/posts"),
                    ),
                    ["headers" => $headers],
                );
                if (is_wp_error($data)) {
                    return $data;
                }
                foreach ($data["data"] ?? [] as $v) {
                    if (
                        $p === "facebook" &&
                        ((string) ($v["from"]["id"] ?? "") !== $id ||
                            ($v["status_type"] ?? "") === "shared_story" ||
                            ($v["is_published"] ?? false) !== true ||
                            !empty($v["is_hidden"]) ||
                            (!empty($v["privacy"]["value"]) &&
                                $v["privacy"]["value"] !== "EVERYONE"))
                    ) {
                        continue;
                    }
                    $a = $v["attachments"]["data"][0] ?? [];
                    $items[] = [
                        "id" => $v["id"],
                        "url" => $v["permalink"] ?? ($v["permalink_url"] ?? ""),
                        "caption" => $v["caption"] ?? ($v["message"] ?? ""),
                        "date" => gmdate(
                            "Y-m-d H:i:s",
                            strtotime(
                                $v["timestamp"] ??
                                    ($v["created_time"] ?? "now"),
                            ),
                        ),
                        "type" => strtolower(
                            $v["media_type"] ?? ($a["media_type"] ?? "text"),
                        ),
                        "cover" =>
                            $v["thumbnail_url"] ??
                            (($v["media_type"] ?? "") === "IMAGE"
                                ? $v["media_url"] ?? ""
                                : $a["media"]["image"]["src"] ?? ""),
                    ];
                }
                $cursor = !empty($data["paging"]["next"])
                    ? $data["paging"]["cursors"]["after"] ?? null
                    : null;
            } while ($cursor && ++$round < 4);
        }
        // Never interpret absence from a paginated window as deletion.
        return ["items" => $items, "cursor" => $cursor];
    }
    static function valid_url($p, $url)
    {
        $parts = wp_parse_url($url);
        if (
            !$parts ||
            ($parts["scheme"] ?? "") !== "https" ||
            isset($parts["user"]) ||
            isset($parts["port"])
        ) {
            return false;
        }
        $hosts = [
            "tiktok" => ["www.tiktok.com", "tiktok.com"],
            "instagram" => ["www.instagram.com", "instagram.com"],
            "facebook" => ["www.facebook.com", "facebook.com"],
            "linkedin" => ["www.linkedin.com", "linkedin.com"],
            "x" => ["x.com", "www.x.com", "twitter.com", "www.twitter.com"],
        ];
        if (!in_array($parts["host"] ?? "", $hosts[$p] ?? [], true)) {
            return false;
        }
        $path = $parts["path"] ?? "";
        return match ($p) {
            "tiktok" => (bool) preg_match(
                '~^/@valon_asani/(video|photo)/[0-9]+/?$~',
                $path,
            ),
            "instagram" => (bool) preg_match(
                '~^/(?:valonasanidua/)?(?:p|reel|tv)/[A-Za-z0-9_-]+/?$~',
                $path,
            ),
            "x" => (bool) preg_match(
                '~^/ValonAsaniDua/status/[0-9]+/?$~i',
                $path,
            ),
            "linkedin" => str_starts_with($path, "/posts/valon-asani_") ||
                str_starts_with($path, "/feed/update/urn:li:"),
            "facebook" => str_starts_with($path, "/valonasanidua/") ||
                str_starts_with($path, "/reel/") ||
                $path === "/permalink.php" ||
                $path === "/watch/",
            default => false,
        };
    }
    static function upsert($p, $item)
    {
        if (
            !in_array($p, self::PLATFORMS, true) ||
            empty($item["id"]) ||
            !self::valid_url($p, $item["url"] ?? "")
        ) {
            return new WP_Error("invalid", "Invalid source URL or ID.");
        }
        $stamp = strtotime($item["date"] ?? "");
        if (!$stamp || $stamp > time() + DAY_IN_SECONDS) {
            return new WP_Error(
                "date",
                "A valid original publication date is required.",
            );
        }
        $key = $p . ":" . sanitize_text_field((string) $item["id"]);
        $record_lock = "item_" . hash("sha256", $key);
        if (!self::lock($record_lock)) {
            return new WP_Error(
                "locked",
                "This item is already being updated.",
            );
        }
        try {
            $found = get_posts([
                "post_type" => "valon_social",
                "post_status" => [
                    "publish",
                    "draft",
                    "pending",
                    "private",
                    "trash",
                ],
                "numberposts" => 1,
                "meta_key" => "_vp_key",
                "meta_value" => $key,
                "fields" => "ids",
                "suppress_filters" => true,
            ]);
            $post = [
                "post_type" => "valon_social",
                "post_status" => "publish",
                "post_title" =>
                    wp_trim_words(
                        wp_strip_all_tags($item["caption"] ?? ""),
                        14,
                        "…",
                    ) ?:
                    ucfirst($p) . " post",
            ];
            if ($found) {
                $post["ID"] = $found[0];
                if (get_post_status($found[0]) === "trash") {
                    return $found[0];
                }
            }
            $id = wp_insert_post($post, true);
            if (is_wp_error($id)) {
                return $id;
            }
            $values = [
                "key" => $key,
                "platform" => $p,
                "external_id" => (string) $item["id"],
                "source_url" => esc_url_raw($item["url"]),
                "caption" => sanitize_textarea_field($item["caption"] ?? ""),
                "published_at" => gmdate("Y-m-d H:i:s", $stamp),
                "media_type" => sanitize_key($item["type"] ?? "link"),
                "availability" => "public",
                "synced_at" => time(),
            ];
            if (isset($item["language"])) {
                $values["language"] = in_array(
                    $item["language"],
                    ["sq", "en", "de"],
                    true,
                )
                    ? $item["language"]
                    : "und";
            } elseif (!$found) {
                $values["language"] = "und";
            }
            $cover = esc_url_raw($item["cover"] ?? "");
            if ($cover && wp_parse_url($cover, PHP_URL_SCHEME) === "https") {
                $values["cover"] = $cover;
            }
            foreach ($values as $k => $v) {
                update_post_meta($id, "_vp_" . $k, $v);
            }
            return $id;
        } finally {
            delete_option("vp_lock_" . $record_lock);
        }
    }
    static function revalidate($p, $token)
    {
        $posts = self::items($p, $p === "tiktok" ? 20 : 24, $p === "tiktok");
        if (!$posts) {
            return;
        }
        if ($p === "tiktok") {
            $ids = array_map(
                fn($x) => get_post_meta($x->ID, "_vp_external_id", true),
                $posts,
            );
            $data = self::request(
                "https://open.tiktokapis.com/v2/video/query/?fields=id",
                [
                    "method" => "POST",
                    "headers" => [
                        "Authorization" => "Bearer " . $token,
                        "Content-Type" => "application/json",
                    ],
                    "body" => wp_json_encode([
                        "filters" => ["video_ids" => $ids],
                    ]),
                ],
            );
            if (is_wp_error($data) || !isset($data["data"]["videos"])) {
                return;
            }
            $available = array_column($data["data"]["videos"], "id");
            foreach ($posts as $post) {
                if (
                    !in_array(
                        get_post_meta($post->ID, "_vp_external_id", true),
                        $available,
                        true,
                    )
                ) {
                    update_post_meta(
                        $post->ID,
                        "_vp_availability",
                        "unavailable",
                    );
                }
            }
        } else {
            // Explicit removal or successful private/unpublished state hides content; ambiguous permission/network failures retain the cache.
            $base =
                $p === "instagram"
                    ? "https://graph.instagram.com/v26.0/"
                    : "https://graph.facebook.com/v26.0/";
            foreach ($posts as $post) {
                $response = self::request(
                    $base .
                        rawurlencode(
                            get_post_meta($post->ID, "_vp_external_id", true),
                        ) .
                        "?fields=" .
                        ($p === "facebook"
                            ? "id,is_published,is_hidden,privacy"
                            : "id"),
                    ["headers" => ["Authorization" => "Bearer " . $token]],
                );
                if (
                    $p === "facebook" &&
                    !is_wp_error($response) &&
                    (($response["is_published"] ?? true) === false ||
                        !empty($response["is_hidden"]) ||
                        (!empty($response["privacy"]["value"]) &&
                            $response["privacy"]["value"] !== "EVERYONE"))
                ) {
                    update_post_meta(
                        $post->ID,
                        "_vp_availability",
                        "unavailable",
                    );
                }
                if (
                    is_wp_error($response) &&
                    in_array(
                        $response->get_error_data()["status"] ?? 0,
                        [404, 410],
                        true,
                    )
                ) {
                    update_post_meta(
                        $post->ID,
                        "_vp_availability",
                        "unavailable",
                    );
                }
            }
        }
    }
    static function items($p, $limit = 6, $video_only = false)
    {
        $query = [
            "post_type" => "valon_social",
            "post_status" => "publish",
            "numberposts" => $limit,
            "meta_key" => "_vp_published_at",
            "orderby" => "meta_value",
            "order" => "DESC",
            "suppress_filters" => true,
            "meta_query" => [
                ["key" => "_vp_platform", "value" => $p],
                ["key" => "_vp_availability", "value" => "public"],
                [
                    "relation" => "OR",
                    ["key" => "_vp_hidden", "compare" => "NOT EXISTS"],
                    ["key" => "_vp_hidden", "value" => "1", "compare" => "!="],
                ],
            ],
        ];
        if ($video_only) {
            $query["meta_query"][] = [
                "key" => "_vp_media_type",
                "value" => "video",
            ];
        }
        return get_posts($query);
    }
}
function vp_render_feed($platform = "home", $limit = 6)
{
    $p = $platform === "home" ? "tiktok" : $platform;
    $posts = VP_Social::items($p, $limit, $platform === "home");
    if (!$posts && $platform === "home") {
        $p = "instagram";
        $posts = VP_Social::items($p, $limit);
    }
    ob_start();
    if (!$posts) {
        echo '<div class="social-empty"><p>' .
            esc_html(
                vp_text(
                    "The latest conversations are on my social channels.",
                    "Bisedat e fundit janë në rrjetet e mia sociale.",
                ),
            ) .
            "</p>";
        foreach (VP_Social::profiles() as $key => $url) {
            echo '<a class="text-link" style="margin-right:20px" href="' .
                esc_url($url) .
                '">' .
                esc_html(ucfirst($key)) .
                " ↗</a>";
        }
        echo "</div>";
        return ob_get_clean();
    }
    echo '<div class="social-grid">';
    foreach ($posts as $post) {
        $id = $post->ID;
        $url = get_post_meta($id, "_vp_source_url", true);
        $cover = get_post_meta($id, "_vp_cover", true);
        $caption = get_post_meta($id, "_vp_caption", true);
        $language = get_post_meta($id, "_vp_language", true);
        $date = get_post_meta($id, "_vp_published_at", true);
        $featured = get_post_meta($id, "_vp_featured", true) === "1";
        $article = absint(get_post_meta($id, "_vp_article_id", true));
        echo '<article class="social-card"><div class="social-cover">';
        if ($cover) {
            echo '<img src="' .
                esc_url($cover) .
                '" alt="" loading="lazy" referrerpolicy="no-referrer">';
        } else {
            echo '<span class="social-placeholder">' .
                esc_html(ucfirst($p)) .
                "<br>↗</span>";
        }
        echo '<button class="social-play" data-social-id="' .
            esc_attr($id) .
            '" aria-label="' .
            esc_attr(
                vp_text("Open post from ", "Hap postimin nga ") . ucfirst($p),
            ) .
            '">▷</button></div><p class="eyebrow">' .
            esc_html(
                ucfirst($p) .
                    ($language === "und" ? "" : " · " . strtoupper($language)) .
                    ($featured ? vp_text(" · Selected", " · Zgjedhur") : ""),
            ) .
            '</p><p class="social-caption">' .
            esc_html($caption) .
            '</p><time datetime="' .
            esc_attr(gmdate("c", strtotime($date))) .
            '">' .
            esc_html(wp_date("M j, Y", strtotime($date))) .
            '</time><a data-social-source="' .
            esc_attr($p) .
            '" href="' .
            esc_url($url) .
            '" target="_blank" rel="noopener noreferrer">' .
            esc_html(vp_text("View original", "Shiko origjinalin")) .
            " ↗</a>";
        if ($article && get_post_status($article) === "publish") {
            echo '<a class="text-link" href="' .
                esc_url(get_permalink($article)) .
                '">' .
                esc_html(
                    vp_text("Read the full idea →", "Lexo idenë e plotë →"),
                ) .
                "</a>";
        }
        echo "</article>";
    }
    echo "</div>";
    return ob_get_clean();
}
add_action("rest_api_init", function () {
    register_rest_route("valon/v1", "/embed/(?P<id>\d+)", [
        "methods" => "GET",
        "permission_callback" => "__return_true",
        "callback" => function ($request) {
            $id = (int) $request["id"];
            if (
                get_post_type($id) !== "valon_social" ||
                get_post_status($id) !== "publish" ||
                get_post_meta($id, "_vp_hidden", true) === "1" ||
                get_post_meta($id, "_vp_availability", true) !== "public"
            ) {
                return new WP_Error("not_found", "Post unavailable.", [
                    "status" => 404,
                ]);
            }
            $p = get_post_meta($id, "_vp_platform", true);
            $url = get_post_meta($id, "_vp_source_url", true);
            $external = get_post_meta($id, "_vp_external_id", true);
            $iframe = "";
            if (
                $p === "tiktok" &&
                preg_match('/^\d+$/', $external) &&
                str_contains($url, "/video/")
            ) {
                $iframe = "https://www.tiktok.com/player/v1/" . $external;
            }
            if ($p === "facebook") {
                $iframe = add_query_arg(
                    ["href" => $url, "width" => 500, "show_text" => "true"],
                    "https://www.facebook.com/plugins/post.php",
                );
            }
            if (
                $p === "linkedin" &&
                preg_match(
                    "/urn:li:(?:share|ugcPost|activity):[0-9]+/",
                    $url,
                    $m,
                )
            ) {
                $iframe = "https://www.linkedin.com/embed/feed/update/" . $m[0];
            }
            if (
                $p === "linkedin" &&
                !$iframe &&
                preg_match("/-activity-([0-9]+)/", $url, $m)
            ) {
                $iframe =
                    "https://www.linkedin.com/embed/feed/update/urn:li:activity:" .
                    $m[1];
            }
            if (
                $p === "instagram" &&
                preg_match("~/(?:p|reel|tv)/([A-Za-z0-9_-]+)~", $url, $m)
            ) {
                $iframe =
                    "https://www.instagram.com/p/" .
                    $m[1] .
                    "/embed/captioned/";
            }
            return [
                "platform" => $p,
                "source" => $url,
                "iframe" => $iframe,
                "id" => $external,
            ];
        },
    ]);
});
