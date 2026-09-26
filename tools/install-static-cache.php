<?php
/** Install the reviewed browser-cache block without changing WordPress rules. */
if (PHP_SAPI !== "cli") {
    exit(1);
}
$options = getopt("", ["root:", "backup-dir:", "apply"]);
$root = realpath($options["root"] ?? "");
$policy = file_get_contents(__DIR__ . "/../hosting/railway/static-cache.htaccess");
if (empty($options["root"]) || !$root || !is_file($root . "/wp-load.php") || !$policy) {
    fwrite(STDERR, "A WordPress --root and readable cache policy are required.\n");
    exit(1);
}
$path = $root . "/.htaccess";
if (is_link($path) || !is_file($path)) {
    fwrite(STDERR, "Expected an existing, regular WordPress .htaccess.\n");
    exit(1);
}
$before = file_get_contents($path);
$begin = "# BEGIN Valon Static Cache";
$end = "# END Valon Static Cache";
if (substr_count($before, $begin) !== substr_count($before, $end) || substr_count($before, $begin) > 1) {
    fwrite(STDERR, "Unexpected cache markers; refusing to rewrite .htaccess.\n");
    exit(1);
}
$replacements = 0;
$after = str_contains($before, $begin)
    ? preg_replace("~^# BEGIN Valon Static Cache\R.*?^# END Valon Static Cache(?:\R|$)~ms", $policy, $before, -1, $replacements)
    : $policy . "\n" . $before;
if (str_contains($before, $begin) && $replacements !== 1) {
    fwrite(STDERR, "Malformed cache markers; refusing to rewrite .htaccess.\n");
    exit(1);
}
if ($after === null || substr_count($after, $begin) !== 1 || substr_count($after, $end) !== 1) {
    fwrite(STDERR, "Could not produce one complete cache block.\n");
    exit(1);
}
$changed = $before !== $after;
if (!array_key_exists("apply", $options) || !$changed) {
    echo json_encode(["mode" => "dry-run", "changed" => $changed, "before_sha256" => hash("sha256", $before), "after_sha256" => hash("sha256", $after)]) . "\n";
    exit(0);
}
$backup = realpath($options["backup-dir"] ?? "");
if (empty($options["backup-dir"]) || !$backup || $backup === $root || str_starts_with($backup . "/", $root . "/")) {
    fwrite(STDERR, "--backup-dir must exist outside the public WordPress root.\n");
    exit(1);
}
$backupFile = $backup . "/htaccess-" . gmdate("Ymd-His") . "-" . substr(hash("sha256", $before), 0, 12);
umask(0077);
$handle = fopen($backupFile, "x");
if (!$handle || !chmod($backupFile, 0600) || fwrite($handle, $before) !== strlen($before)) {
    fwrite(STDERR, "Could not save the private recovery copy.\n");
    exit(1);
}
fclose($handle);
$temp = tempnam($root, ".htaccess.valon-cache-");
if (!$temp || file_put_contents($temp, $after) !== strlen($after) || !chmod($temp, fileperms($path) & 0777)) {
    if ($temp) {
        unlink($temp);
    }
    fwrite(STDERR, "Could not stage .htaccess.\n");
    exit(1);
}
// Run as the existing .htaccess owner so WordPress can continue updating it.
if (fileowner($temp) !== fileowner($path) || hash_file("sha256", $path) !== hash("sha256", $before)) {
    unlink($temp);
    fwrite(STDERR, "Owner or source changed; refusing to replace .htaccess.\n");
    exit(1);
}
if (!rename($temp, $path)) {
    unlink($temp);
    fwrite(STDERR, "Atomic .htaccess replacement failed.\n");
    exit(1);
}
echo json_encode(["mode" => "applied", "changed" => true, "after_sha256" => hash_file("sha256", $path), "backup" => $backupFile]) . "\n";
