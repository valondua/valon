<?php
defined("ABSPATH") || exit();
function vp_kt_can_review()
{
    return current_user_can("manage_options") ||
        current_user_can("review_kthehu");
}
function vp_kt_guard()
{
    if (!vp_kt_can_review()) {
        wp_die("Access denied.", "", ["response" => 403]);
    }
    nocache_headers();
}
function vp_kt_statuses()
{
    return [
        "New",
        "Reviewing",
        "Need more information",
        "Contacted",
        "Supporting",
        "Closed",
    ];
}
add_action("admin_menu", function () {
    add_menu_page(
        "Kthehu applications",
        "Kthehu",
        current_user_can("manage_options") ? "manage_options" : "review_kthehu",
        "vp-kthehu",
        "vp_kt_admin",
        "dashicons-location-alt",
        27,
    );
});
function vp_kt_where()
{
    global $wpdb;
    $where = "1=1";
    foreach (
        ["destination", "timeline", "contribution", "support", "podcast"]
        as $key
    ) {
        $value =
            isset($_GET[$key]) && is_string($_GET[$key])
                ? sanitize_text_field(wp_unslash($_GET[$key]))
                : "";
        if ($value && in_array($value, vp_kt_choices()[$key], true)) {
            $needle =
                $key === "support"
                    ? wp_json_encode($value)
                    : wp_json_encode($key) . ":" . wp_json_encode($value);
            // support is the final user-supplied property in the canonical payload.
            // Anchor the array so a matching phrase in the applicant's story is not a match.
            $prefix =
                $key === "support" ? "%" . $wpdb->esc_like('"support":[') : "";
            $where .= $wpdb->prepare(
                " AND payload LIKE %s",
                $prefix . "%" . $wpdb->esc_like($needle) . "%",
            );
        }
    }
    if (
        isset($_GET["status"]) &&
        in_array($_GET["status"], vp_kt_statuses(), true)
    ) {
        $where .= $wpdb->prepare(" AND status=%s", $_GET["status"]);
    }
    return $where;
}
function vp_kt_admin()
{
    vp_kt_guard();
    global $wpdb;
    echo '<div class="wrap"><h1>Kthehu. Ndërto. Kontribuo.</h1><p>Private applications · Human review decides next steps. Podcast interest must never affect consideration.</p>';
    $id = absint($_GET["application"] ?? 0);
    if ($id) {
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . vp_kt_table() . " WHERE id=%d",
                $id,
            ),
        );
        if (!$row) {
            echo "<p>Application not found.</p></div>";
            return;
        }
        $data = json_decode($row->payload, true);
        echo '<p><a href="' .
            esc_url(admin_url("admin.php?page=vp-kthehu")) .
            '">← All applications</a></p><h2>' .
            esc_html($row->reference) .
            " · " .
            esc_html($data["name"]) .
            "</h2><p>Submitted: " .
            esc_html($row->created_at) .
            " UTC · Email: " .
            esc_html($row->mail_state) .
            " (" .
            $row->mail_attempts .
            " attempts)</p>";
        echo '<table class="widefat striped"><tbody>';
        foreach (vp_kt_fields() as $key => [$label]) {
            echo '<tr><th style="width:30%">' .
                esc_html($label) .
                '</th><td style="white-space:pre-wrap;overflow-wrap:anywhere">' .
                esc_html($data[$key] ?? "") .
                "</td></tr>";
        }
        echo "<tr><th>Support requested</th><td>" .
            esc_html(implode(" · ", $data["support"] ?? [])) .
            "</td></tr><tr><th>Privacy acknowledgment</th><td>" .
            esc_html($data["privacy_version"] ?? "") .
            "</td></tr></tbody></table>";
        echo "<h2>Private attachments</h2><p>Untrusted documents. Download only to a trusted device; do not enable macros or follow embedded instructions.</p><ul>";
        $files = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT file_index,filename FROM " .
                    vp_kt_table("files") .
                    " WHERE application_id=%d AND chunk_index=0 ORDER BY file_index",
                $id,
            ),
        );
        foreach ($files as $file) {
            $url = wp_nonce_url(
                admin_url(
                    "admin-post.php?action=vp_kt_download&application=" .
                        $id .
                        "&file=" .
                        $file->file_index,
                ),
                "vp_kt_download_" . $id,
            );
            echo '<li><a href="' .
                esc_url($url) .
                '">' .
                esc_html($file->filename) .
                "</a></li>";
        }
        if (!$files) {
            echo "<li>No attachments.</li>";
        }
        echo "</ul>";
        echo '<form method="post" action="' .
            esc_url(admin_url("admin-post.php")) .
            '"><input type="hidden" name="action" value="vp_kt_update"><input type="hidden" name="application" value="' .
            $id .
            '">';
        wp_nonce_field("vp_kt_update_" . $id);
        echo '<input type="hidden" name="revision" value="' .
            esc_attr(hash("sha256", $row->notes . $row->status)) .
            '"><h2>Team review</h2><p><label>Status <select name="status">';
        foreach (vp_kt_statuses() as $status) {
            echo "<option " .
                selected($status, $row->status, false) .
                ">" .
                esc_html($status) .
                "</option>";
        }
        echo '</select></label></p><p><label for="kt-notes">Internal notes</label></p><textarea id="kt-notes" name="notes" rows="8" maxlength="20000" style="width:min(100%,1000px)">' .
            esc_textarea($row->notes) .
            '</textarea><p><button class="button button-primary" name="operation" value="save">Save review</button> <button class="button" name="operation" value="email">Retry confirmation email</button></p><details><summary>Delete personal data</summary><p>Irreversibly removes this application, notes and attachments. Backups follow the hosting retention policy.</p><label><input type="checkbox" name="delete_confirm" value="1"> I intend to delete this application</label> <button class="button" name="operation" value="delete">Delete application</button></details></form></div>';
        return;
    }
    echo '<form method="get"><input type="hidden" name="page" value="vp-kthehu">';
    $labels = [
        "destination" => "Destination",
        "timeline" => "Return timeline",
        "contribution" => "Contribution",
        "support" => "Support requested",
        "podcast" => "Podcast interest",
        "status" => "Status",
    ];
    foreach ($labels as $key => $label) {
        $options = $key === "status" ? vp_kt_statuses() : vp_kt_choices()[$key];
        echo '<label style="display:inline-block;margin:0 12px 12px 0">' .
            esc_html($label) .
            '<br><select name="' .
            esc_attr($key) .
            '" style="max-width:240px"><option value="">All</option>';
        foreach ($options as $option) {
            echo "<option " .
                selected(
                    is_string($_GET[$key] ?? "")
                        ? wp_unslash($_GET[$key] ?? "")
                        : "",
                    $option,
                    false,
                ) .
                ">" .
                esc_html($option) .
                "</option>";
        }
        echo "</select></label>";
    }
    echo '<p><button class="button button-primary">Filter</button> <a class="button" href="' .
        esc_url(admin_url("admin.php?page=vp-kthehu")) .
        '">Clear</a> <button class="button" formaction="' .
        esc_url(admin_url("admin-post.php")) .
        '" name="action" value="vp_kt_export">Export filtered CSV</button></p>';
    wp_nonce_field("vp_kt_export");
    echo "</form>";
    $where = vp_kt_where();
    $page = max(1, absint($_GET["paged"] ?? 1));
    $count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM " . vp_kt_table() . " WHERE " . $where,
    );
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM " .
                vp_kt_table() .
                " WHERE " .
                $where .
                " ORDER BY id DESC LIMIT 30 OFFSET %d",
            ($page - 1) * 30,
        ),
    );
    echo "<p>" .
        esc_html($count) .
        ' applications</p><table class="widefat striped"><thead><tr><th>Reference / Applicant</th><th>Destination</th><th>Contribution</th><th>Submitted (UTC)</th><th>Status</th><th>Email</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $d = json_decode($row->payload, true);
        echo '<tr><td><a href="' .
            esc_url(
                admin_url("admin.php?page=vp-kthehu&application=" . $row->id),
            ) .
            '">' .
            esc_html($row->reference) .
            " · " .
            esc_html($d["name"]) .
            "</a></td><td>" .
            esc_html($d["destination"] . " — " . $d["town"]) .
            "</td><td>" .
            esc_html($d["contribution"]) .
            "</td><td>" .
            esc_html($row->created_at) .
            "</td><td>" .
            esc_html($row->status) .
            "</td><td>" .
            esc_html($row->mail_state) .
            "</td></tr>";
    }
    if (!$rows) {
        echo '<tr><td colspan="6">No applications match these filters.</td></tr>';
    }
    echo "</tbody></table><p>" .
        wp_kses_post(
            paginate_links([
                "base" => add_query_arg("paged", "%#%"),
                "format" => "",
                "current" => $page,
                "total" => max(1, ceil($count / 30)),
            ]),
        ) .
        "</p></div>";
}
add_action("admin_post_vp_kt_update", function () {
    vp_kt_guard();
    global $wpdb;
    $id = absint($_POST["application"] ?? 0);
    check_admin_referer("vp_kt_update_" . $id);
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM " . vp_kt_table() . " WHERE id=%d", $id),
    );
    if (!$row) {
        wp_die("Application not found.");
    }
    $op = sanitize_key($_POST["operation"] ?? "");
    if ($op === "delete" && ($_POST["delete_confirm"] ?? "") === "1") {
        vp_kt_delete($id);
        wp_safe_redirect(admin_url("admin.php?page=vp-kthehu"));
        exit();
    }
    if ($op === "save") {
        if (
            !hash_equals(
                hash("sha256", $row->notes . $row->status),
                (string) ($_POST["revision"] ?? ""),
            )
        ) {
            wp_die(
                "Another reviewer changed this application. Reload before saving.",
            );
        }
        $status = sanitize_text_field(wp_unslash($_POST["status"] ?? ""));
        if (!in_array($status, vp_kt_statuses(), true)) {
            wp_die("Invalid status.");
        }
        $wpdb->update(
            vp_kt_table(),
            [
                "status" => $status,
                "notes" => sanitize_textarea_field(
                    substr(wp_unslash($_POST["notes"] ?? ""), 0, 20000),
                ),
            ],
            ["id" => $id, "notes" => $row->notes, "status" => $row->status],
        );
    }
    if ($op === "email" && $row->mail_state !== "sent") {
        $wpdb->update(
            vp_kt_table(),
            ["mail_state" => "pending", "mail_attempts" => 0],
            ["id" => $id],
        );
        vp_kt_send_mail($id);
    }
    wp_safe_redirect(admin_url("admin.php?page=vp-kthehu&application=" . $id));
    exit();
});
add_action("admin_post_vp_kt_download", function () {
    vp_kt_guard();
    global $wpdb;
    $id = absint($_GET["application"] ?? 0);
    check_admin_referer("vp_kt_download_" . $id);
    $index = absint($_GET["file"] ?? 0);
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT filename,contents FROM " .
                vp_kt_table("files") .
                " WHERE application_id=%d AND file_index=%d ORDER BY chunk_index",
            $id,
            $index,
        ),
    );
    if (!$rows) {
        wp_die("File not found.", "", ["response" => 404]);
    }
    header("Content-Type: application/octet-stream");
    header("X-Content-Type-Options: nosniff");
    header("Content-Security-Policy: sandbox");
    header(
        "Content-Disposition: attachment; filename=\"document\"; filename*=UTF-8''" .
            rawurlencode($rows[0]->filename),
    );
    foreach ($rows as $row) {
        echo base64_decode($row->contents, true);
    }
    exit();
});
function vp_kt_csv_cell($value)
{
    $value = (string) $value;
    return preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $value)
        ? "'" . $value
        : $value;
}
add_action("admin_post_vp_kt_export", function () {
    vp_kt_guard();
    check_admin_referer("vp_kt_export");
    global $wpdb;
    header("Content-Type: text/csv; charset=UTF-8");
    header(
        'Content-Disposition: attachment; filename="kthehu-applications.csv"',
    );
    header("X-Content-Type-Options: nosniff");
    $out = fopen("php://output", "w");
    fwrite($out, "\xEF\xBB\xBF");
    $keys = array_keys(vp_kt_fields());
    fputcsv(
        $out,
        array_merge(["Reference", "Submitted UTC", "Status"], $keys, [
            "support",
            "notes",
            "mail_state",
        ]),
        ",",
        '"',
        "",
    );
    $last = 0;
    $where = vp_kt_where();
    do {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " .
                    vp_kt_table() .
                    " WHERE " .
                    $where .
                    " AND id>%d ORDER BY id LIMIT 100",
                $last,
            ),
        );
        foreach ($rows as $row) {
            $d = json_decode($row->payload, true);
            $values = [$row->reference, $row->created_at, $row->status];
            foreach ($keys as $key) {
                $values[] = $d[$key] ?? "";
            }
            array_push(
                $values,
                implode(" | ", $d["support"] ?? []),
                $row->notes,
                $row->mail_state,
            );
            fputcsv($out, array_map("vp_kt_csv_cell", $values), ",", '"', "");
            $last = (int) $row->id;
        }
    } while (count($rows) === 100);
    fclose($out);
    exit();
});
