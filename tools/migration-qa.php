<?php
if (wp_get_environment_type() !== "local") {
    WP_CLI::error("Local migration rehearsal only.");
}
function migration_check($ok, $name)
{
    if (!$ok) {
        throw new RuntimeException($name);
    }
    WP_CLI::line("PASS " . $name);
}
$bundle = [
    "pages" => json_decode(
        file_get_contents(
            get_template_directory() . "/review/content/pages.json",
        ),
        true,
    ),
    "topics" => json_decode(
        file_get_contents(
            get_template_directory() . "/review/content/topics.json",
        ),
        true,
    ),
];
$bundle["pages"]["contact"]["de"]["content"] .= "<script>alert(1)</script>";
$clean = vp_launch_validate($bundle);
migration_check(
    !str_contains($clean["pages"]["contact"]["de"]["content"], "<script>"),
    "Uploaded HTML is sanitized",
);
unset($bundle["pages"]["contact"]["de"]);
try {
    vp_launch_validate($bundle);
    throw new LogicException("Invalid bundle accepted");
} catch (RuntimeException $e) {
    migration_check(
        $e->getMessage() === "Missing core language journey.",
        "Incomplete language journey rejected",
    );
}
$bundle = [
    "pages" => json_decode(
        file_get_contents(
            get_template_directory() . "/review/content/pages.json",
        ),
        true,
    ),
    "topics" => json_decode(
        file_get_contents(
            get_template_directory() . "/review/content/topics.json",
        ),
        true,
    ),
];
wp_set_current_user(0);
try {
    vp_launch_run("stage", $bundle);
    throw new LogicException("Anonymous accepted");
} catch (RuntimeException $e) {
    migration_check(
        $e->getMessage() === "Administrator access required.",
        "Anonymous migration is blocked",
    );
}
wp_set_current_user((int) get_option("vp_editorial_owner"));
$oldmap = get_option("valon_pages");
$before = [];
foreach ($oldmap as $routes) {
    foreach ($routes as $id) {
        $p = get_post($id);
        $before[$id] = [$p->post_name, $p->post_content, $p->post_status];
    }
}
$articles = wp_count_posts("post")->publish;
vp_launch_run("stage", vp_launch_validate($bundle));
foreach ($before as $id => $state) {
    $p = get_post($id);
    migration_check(
        [$p->post_name, $p->post_content, $p->post_status] === $state,
        "Published page " . $id . " unchanged during staging",
    );
}
$ids = get_option("vp_launch_staged_ids");
migration_check(count($ids) === 42, "All 42 translated core pages staged");
$first = get_post($ids[0]);
$content = $first->post_content;
wp_update_post(
    wp_slash([
        "ID" => $first->ID,
        "post_content" => $content . "<p>Changed after review</p>",
    ]),
);
try {
    vp_launch_run("publish");
    throw new LogicException("Changed copy accepted");
} catch (RuntimeException $e) {
    migration_check(
        str_contains($e->getMessage(), "changed"),
        "Changed staged copy cannot publish",
    );
}
wp_update_post(wp_slash(["ID" => $first->ID, "post_content" => $content]));
vp_launch_run("publish");
migration_check(
    get_option("valon_pages") === $oldmap,
    "Publication preserves existing page IDs and language relationships",
);
foreach ($before as $id => $state) {
    $p = get_post($id);
    migration_check(
        $p->post_name === $state[0] && $p->post_status === "publish",
        "Published URL " . $id . " retained",
    );
}
migration_check(
    wp_count_posts("post")->publish === $articles,
    "No article or article translation was published",
);
migration_check(
    !get_option("vp_launch_staged_ids"),
    "Completed bundle cannot be accidentally applied twice",
);
WP_CLI::success("Migration rehearsal passed.");
