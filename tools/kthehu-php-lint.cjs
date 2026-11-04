/** Parse feature PHP with the oldest supported PHP and the deployment PHP version. */
const fs = require("node:fs");
const path = require("node:path");
const {
  PHP,
} = require("../.local/kthehu-runtime/node_modules/@php-wasm/universal");
const {
  loadNodeRuntime,
} = require("../.local/kthehu-runtime/node_modules/@php-wasm/node");
const files = [
  "page-kthehu.php",
  "functions.php",
  "plugins/valon-platform/valon-platform.php",
  "plugins/valon-platform/includes/kthehu.php",
  "plugins/valon-platform/includes/kthehu/schema.php",
  "plugins/valon-platform/includes/kthehu/admin.php",
  "plugins/valon-platform/includes/kthehu/routing.php",
];
(async () => {
  const inputs = Object.fromEntries(
    files.map((file) => [
      file,
      fs.readFileSync(path.resolve(__dirname, "..", file), "utf8"),
    ]),
  );
  for (const version of ["8.1", "8.3"]) {
    const php = new PHP(
      await loadNodeRuntime(version, {
        emscriptenOptions: { processId: parseInt(version.replace(".", "")) },
      }),
    );
    const response = await php.runStream({
      code: `<?php $files=json_decode(base64_decode('${Buffer.from(JSON.stringify(inputs)).toString("base64")}'),true); foreach($files as $file=>$source) { try {token_get_all($source,TOKEN_PARSE);} catch(Throwable $e) {echo $file.': '.$e->getMessage(); exit(1);} } echo 'PASS PHP '.PHP_VERSION.' — '.count($files).' files parsed';`,
    });
    console.log(await response.stdoutText);
    if (await response.exitCode) throw new Error("PHP syntax check failed.");
    php.exit();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
