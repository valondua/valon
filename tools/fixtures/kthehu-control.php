<?php
if (!defined("VP_KTHEHU_TEST") || !VP_KTHEHU_TEST) {
    http_response_code(404);
    exit();
}
require "/wordpress/wp-load.php";
if (
    !defined("VP_KTHEHU_TEST") ||
    !VP_KTHEHU_TEST ||
    wp_get_environment_type() !== "local" ||
    !current_user_can("manage_options") ||
    parse_url(home_url(), PHP_URL_HOST) !== "127.0.0.1"
) {
    http_response_code(403);
    exit();
}
header("Content-Type: application/json");
global $wpdb;
$op = $_GET["op"] ?? "inspect";
if ($op === "failchunk") {
    update_option("kt_test_fail_chunk", 1);
}
if ($op === "okchunk") {
    delete_option("kt_test_fail_chunk");
}
if ($op === "clean") {
    foreach ($wpdb->get_col("SELECT id FROM " . vp_kt_table()) as $id) {
        vp_kt_delete((int) $id);
    }
    delete_option("kt_test_last_mail");
}
if ($op === "expire") {
    $wpdb->update(
        vp_kt_table(),
        ["created_at" => "2020-01-01 00:00:00"],
        ["id" => absint($_GET["id"])],
    );
    do_action("vp_kt_maintenance");
}
if ($op === "reset") {
    $wpdb->query("DELETE FROM " . vp_kt_table("limits"));
}
if ($op === "failmail") {
    update_option("kt_test_mail_fail", 1);
}
if ($op === "okmail") {
    delete_option("kt_test_mail_fail");
}
if ($op === "review") {
    define("VP_KTHEHU_OPENAI_KEY", "test-key-never-sent");
    define("VP_KTHEHU_OPENAI_MODEL", "test-model");
    add_filter(
        "pre_http_request",
        function ($r, $args, $url) {
            if ($url !== "https://api.openai.com/v1/responses") {
                return $r;
            }
            update_option(
                "kt_test_ai_request",
                json_decode($args["body"], true),
            );
            return [
                "headers" => [],
                "response" => ["code" => 200],
                "body" => wp_json_encode([
                    "status" => "completed",
                    "output" => [
                        [
                            "content" => [
                                [
                                    "type" => "output_text",
                                    "text" =>
                                        "Applicant statements: Test plan. AI inference: potential local value is uncertain. Questions: 1? 2? 3? Human reviewer: contact applicant. Attachments not reviewed.",
                                ],
                            ],
                        ],
                    ],
                ]),
            ];
        },
        10,
        3,
    );
    $id = absint($_GET["id"]);
    $wpdb->update(
        vp_kt_table(),
        ["ai_state" => "pending", "review" => ""],
        ["id" => $id],
    );
    vp_kt_review($id);
}
echo wp_json_encode([
    "mail" => get_option("kt_test_last_mail"),
    "ai_request" => get_option("kt_test_ai_request"),
    "rows" => $wpdb->get_results("SELECT * FROM " . vp_kt_table()),
    "file_count" => $wpdb->get_var(
        "SELECT COUNT(*) FROM " . vp_kt_table("files"),
    ),
]);
