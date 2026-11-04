/** Disposable WordPress preview; all email and AI calls are mocked. Never use with production data. */
const fs = require("node:fs");
const path = require("node:path");
const { spawn } = require("node:child_process");
const root = path.resolve(__dirname, "..");
const runtime = path.join(root, ".local/kthehu-runtime");
fs.mkdirSync(runtime, { recursive: true });
fs.copyFileSync(
  path.join(__dirname, "fixtures/kthehu-control.php"),
  path.join(runtime, "fixture.php"),
);
const blueprint = {
  steps: [
    { step: "activatePlugin", pluginPath: "valon-platform/valon-platform.php" },
    { step: "activateTheme", themeFolderName: "valon" },
    {
      step: "runPHP",
      code: `<?php require '/wordpress/wp-load.php'; update_option('permalink_structure','/%postname%/'); wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>'kthehu','post_title'=>'Kthehu. Ndërto. Kontribuo.']); wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>'privacy-policy','post_title'=>'Privacy','post_content'=>'Local test privacy contact: privacy@example.invalid']); wp_create_user('kt_reader','test-reader-only','reader@example.invalid'); flush_rewrite_rules();`,
    },
    {
      step: "writeFile",
      path: "/wordpress/wp-content/mu-plugins/kt-test-mail.php",
      data: `<?php add_filter('pre_wp_mail', function($return, $atts) { update_option('kt_test_last_mail', $atts); return get_option('kt_test_mail_fail') ? false : true; }, 10, 2); add_action('init', function() { $fail=get_option('kt_test_fail_chunk'); add_filter('query', function($sql) use ($fail) { static $chunks=0; if ($fail && str_starts_with($sql, 'INSERT INTO') && str_contains($sql, 'vp_kt_files') && ++$chunks === 2) return 'INSERT INTO missing_kt_test_table (x) VALUES (1)'; return $sql; }); }, 99);`,
    },
  ],
};
fs.writeFileSync(
  path.join(runtime, "blueprint.json"),
  JSON.stringify(blueprint),
);
const cli = path.join(runtime, "node_modules/.bin/wp-playground-cli");
if (!fs.existsSync(cli))
  throw new Error(
    "Install the preview dependencies using docs/KTHEHU.md first.",
  );
const child = spawn(
  cli,
  [
    "server",
    "--port=9417",
    "--workers=6",
    `--mount=${root}:/wordpress/wp-content/themes/valon`,
    `--mount=${root}/plugins/valon-platform:/wordpress/wp-content/plugins/valon-platform`,
    `--blueprint=${runtime}/blueprint.json`,
    "--php-ini",
    "upload_max_filesize",
    "10M",
    "--php-ini",
    "post_max_size",
    "32M",
    "--php-ini",
    "max_file_uploads",
    "20",
    "--define",
    "WP_ENVIRONMENT_TYPE",
    "local",
    "--define-bool",
    "VP_KTHEHU_TEST",
    "true",
  ],
  { stdio: "inherit" },
);
for (const signal of ["SIGINT", "SIGTERM"])
  process.on(signal, () => child.kill(signal));
child.on("exit", (code) => {
  process.exitCode = code || 0;
});
