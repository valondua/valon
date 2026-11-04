/** Run against an isolated local WordPress with the Kthehu test fixtures (see docs/KTHEHU.md). */
const assert = require("node:assert/strict");
const { chromium } = require(
  process.env.KTHEHU_PLAYWRIGHT ||
    "../.local/kthehu-runtime/node_modules/@playwright/test",
);
const base = process.env.KTHEHU_TEST_URL || "http://127.0.0.1:9417";
if (!/^http:\/\/(127\.0\.0\.1|localhost):\d+$/.test(base))
  throw new Error("Local test sites only.");
const fixture =
  base + "/wp-content/themes/valon/.local/kthehu-runtime/fixture.php";
(async () => {
  const browser = await chromium.launch({ headless: true, channel: "chrome" });
  try {
    const admin = await browser.newPage();
    admin.setDefaultTimeout(15000);
    admin.setDefaultNavigationTimeout(15000);
    console.log("Logging into local WordPress");
    await admin.goto(base + "/wp-login.php");
    await admin.locator("#user_login").fill("admin");
    await admin.locator("#user_pass").fill("password");
    await Promise.all([
      admin.waitForURL(/wp-admin/),
      admin.locator("#wp-submit").click(),
    ]);
    const fixtureCall = async (op) => {
      const r = await admin.request.get(fixture + "?op=" + op);
      assert.equal(r.status(), 200);
      return r.json();
    };
    await fixtureCall("clean");
    await fixtureCall("reset");
    await fixtureCall("okmail");
    console.log("Logged in; fixtures ready");
    const page = await browser.newPage({
      viewport: { width: 390, height: 844 },
    });
    page.setDefaultTimeout(15000);
    page.setDefaultNavigationTimeout(15000);
    const errors = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await page.goto(base + "/kthehu/");
    await page.waitForFunction(() => document.querySelector("#kt-token").value);
    assert.equal(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
      true,
    );
    await page.locator("#kt-submit").click();
    assert.match(await page.locator("#kt-name-error").textContent(), /Plotëso/);
    for (const [key, value] of Object.entries({
      name: "Test Kthehu",
      email: "kthehu-test@example.invalid",
      country: "Zvicër",
      plan: "Kurse për të rinjtë.",
      obstacle: "Më duhet nji hapësinë.",
    }))
      await page.locator("#kt-" + key).fill(value);
    for (const key of ["destination", "timeline"])
      await page.locator("#kt-" + key).selectOption({ index: 1 });
    await page.locator("#kt-contribution").selectOption({ index: 1 });
    const support = page.locator('[name="support[]"]');
    await support.nth(1).check();
    assert.equal(await page.locator("#kt-funding").count(), 0);
    await support.first().check();
    await page.locator("#kt-consent").check();
    // Save the exact browser request so retry/duplicate behavior can be checked independently.
    await new Promise((resolve) => setTimeout(resolve, 2100));
    await page.evaluate(() => {
      window.ktTestRetry = new FormData(document.querySelector("#kt-form"));
    });
    const submitted = page.waitForRequest((r) =>
      r.url().endsWith("/kthehu/apply"),
    );
    await page.locator("#kt-submit").click();
    const firstRequest = await submitted;
    await page
      .locator(".kt-success")
      .waitFor()
      .catch(async (e) => {
        console.error(
          "Submission UI:",
          await page.locator("#kt-status").textContent(),
        );
        throw e;
      });
    const reference = (
      await page.locator(".kt-success strong").textContent()
    ).replace("Referenca: ", "");
    console.log(
      "PASS shorter mobile form, funding without extra required fields, validation and confirmation",
      reference,
    );
    let snapshot = await fixtureCall("inspect");
    const row = snapshot.rows.find((r) => r.reference === reference);
    assert.ok(row);
    assert.equal(row.mail_state, "sent");
    assert.equal(
      Array.isArray(snapshot.mail.to) ? snapshot.mail.to[0] : snapshot.mail.to,
      "kthehu-test@example.invalid",
    );
    assert.match(snapshot.mail.message, new RegExp(reference));
    const repeat = await page.evaluate(async (base) => {
      const response = await fetch(base + "/wp-json/valon/v1/kthehu/apply", {
        method: "POST",
        body: window.ktTestRetry,
      });
      return response.json();
    }, base);
    assert.equal(repeat.reference, reference);
    console.log("PASS saved confirmation email and idempotent retry");
    await fixtureCall("reset");
    const data = JSON.parse(row.payload);
    delete data.privacy_version;
    data.consent = "1";
    const send = async (changes = {}, files = []) => {
      const t = await (
        await page.request.get(base + "/wp-json/valon/v1/kthehu/ticket")
      ).json();
      await new Promise((r) => setTimeout(r, 2100));
      const fd = new FormData();
      for (const [key, value] of Object.entries({
        ...data,
        ...changes,
        token: t.token,
      })) {
        if (Array.isArray(value))
          value.forEach((v) => fd.append(key + "[]", v));
        else fd.append(key, value);
      }
      files.forEach((f) =>
        fd.append(
          "attachments[]",
          new Blob([f.bytes], { type: f.type }),
          f.name,
        ),
      );
      const response = await fetch(base + "/wp-json/valon/v1/kthehu/apply", {
        method: "POST",
        headers: { Origin: base },
        body: fd,
      });
      return { status: response.status, data: await response.json() };
    };
    assert.equal((await send()).data.reference, reference);
    assert.equal((await send({ email: "invalid" })).status, 422);
    assert.equal((await send({ story: "a".repeat(1001) })).status, 422);
    assert.equal(
      (await send({ support: ["Financim ose njohje me investitorë"] })).status,
      200,
    );
    assert.equal((await send({ consent: "0" })).status, 422);
    assert.equal((await send({ profile: "javascript:alert(1)" })).status, 422);
    const fake = {
      name: "fake.pdf",
      bytes: '<?php echo "evil";',
      type: "application/pdf",
    };
    assert.equal((await send({}, [fake])).status, 422);
    const pdf = {
      name: "plan.pdf",
      bytes: "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
      type: "application/pdf",
    };
    assert.equal((await send({}, [pdf, pdf, pdf, pdf])).status, 422);
    console.log(
      "PASS server-side validation, exact duplicate, forged file and excess upload rejection",
    );
    await fixtureCall("reset");
    await fixtureCall("failmail");
    const upload = await send(
      {
        name: '=HYPERLINK("evil")',
        email: "attachment-test@example.invalid",
        podcast: "Jo",
      },
      [pdf],
    );
    assert.equal(upload.status, 200, JSON.stringify(upload));
    assert.equal(upload.data.email_sent, false);
    snapshot = await fixtureCall("inspect");
    const attachmentRow = snapshot.rows.find(
      (r) => r.reference === upload.data.reference,
    );
    assert.ok(attachmentRow);
    assert.equal(attachmentRow.mail_state, "failed");
    assert.ok(Number(snapshot.file_count) > 0);
    await admin.goto(
      base +
        "/wp-admin/admin.php?page=vp-kthehu&application=" +
        attachmentRow.id,
    );
    const link = await admin
      .getByRole("link", { name: "plan.pdf", exact: true })
      .getAttribute("href");
    const file = await admin.request.get(link);
    assert.equal(file.status(), 200);
    assert.equal((await file.body()).toString(), pdf.bytes);
    assert.match(file.headers()["content-disposition"], /attachment/);
    const denied = await page.request.get(link);
    assert.notEqual(denied.status(), 200);
    const noNonce = await admin.request.get(
      base +
        "/wp-admin/admin-post.php?action=vp_kt_download&application=" +
        attachmentRow.id +
        "&file=0",
    );
    assert.equal(noNonce.status(), 403);
    console.log(
      "PASS private upload round-trip, anonymous denial and download nonce",
    );
    await fixtureCall("okmail");
    await admin
      .getByRole("button", { name: "Retry confirmation email" })
      .click();
    await admin.locator("#kt-notes").fill("Follow up with local partner.");
    await admin.locator("select[name=status]").selectOption("Reviewing");
    await admin
      .getByRole("button", { name: "Save review", exact: true })
      .click();
    assert.equal(
      await admin.locator("#kt-notes").inputValue(),
      "Follow up with local partner.",
    );
    snapshot = await fixtureCall("inspect");
    assert.equal(
      snapshot.rows.find((r) => r.id === attachmentRow.id).mail_state,
      "sent",
    );
    assert.equal(
      await admin
        .getByRole("button", { name: "Generate / retry AI review" })
        .count(),
      0,
    );
    console.log(
      "PASS email retry and human review notes/status; no AI controls",
    );
    await admin.goto(
      base + "/wp-admin/admin.php?page=vp-kthehu&status=Reviewing&podcast=Jo",
    );
    assert.ok(
      await admin
        .getByRole("link", { name: new RegExp(upload.data.reference) })
        .count(),
    );
    assert.equal(
      await admin.getByRole("link", { name: new RegExp(reference) }).count(),
      0,
    );
    const nonce = await admin.locator("[name=_wpnonce]").inputValue();
    const csv = await admin.request.get(
      base +
        "/wp-admin/admin-post.php?action=vp_kt_export&status=Reviewing&podcast=Jo&_wpnonce=" +
        nonce,
    );
    assert.equal(csv.status(), 200);
    assert.ok((await csv.text()).includes("'=HYPERLINK"));
    assert.ok(!(await csv.text()).includes(reference));
    const anonExport = await page.request.get(
      base + "/wp-admin/admin-post.php?action=vp_kt_export&_wpnonce=" + nonce,
    );
    assert.notEqual(anonExport.status(), 200);
    const reader = await browser.newPage();
    await reader.goto(base + "/wp-login.php");
    await reader.locator("#user_login").fill("kt_reader");
    await reader.locator("#user_pass").fill("test-reader-only");
    await reader.locator("#wp-submit").click();
    await reader.waitForURL(/wp-admin/);
    assert.equal(
      (await reader.goto(base + "/wp-admin/admin.php?page=vp-kthehu")).status(),
      403,
    );
    assert.equal((await reader.request.get(link)).status(), 403);
    console.log(
      "PASS dashboard filters, CSV formula escaping, anonymous export denial and unauthorized account access",
    );
    assert.deepEqual(errors, []);
    await page.goto(base + "/kthehu/");
    await page.waitForFunction(() => document.querySelector("#kt-token").value);
    await page.screenshot({
      path: ".local/kthehu-runtime/mobile.png",
      fullPage: true,
    });
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.screenshot({
      path: ".local/kthehu-runtime/desktop.png",
      fullPage: true,
    });
    await page.screenshot({ path: ".local/kthehu-runtime/hero-desktop.png" });
    for (const width of [320, 390, 768]) {
      await page.setViewportSize({ width, height: 844 });
      assert.ok(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
        `overflow at ${width}px`,
      );
      if (width === 390)
        await page.screenshot({
          path: ".local/kthehu-runtime/hero-mobile.png",
        });
    }
    await admin.screenshot({
      path: ".local/kthehu-runtime/dashboard.png",
      fullPage: true,
    });
    await fixtureCall("reset");
    const office = JSON.parse(
      require("node:child_process").execFileSync(
        "python3",
        [
          "-c",
          `import io,zipfile,base64,json
out={}
for ext,root in [('docx','word/document.xml'),('pptx','ppt/presentation.xml'),('xlsx','xl/workbook.xml')]:
 b=io.BytesIO()
 with zipfile.ZipFile(b,'w') as z:
  z.writestr('[Content_Types].xml','<?xml version="1.0"?><Types/>')
  z.writestr(root,'<?xml version="1.0"?><root/>')
 out[ext]=base64.b64encode(b.getvalue()).decode()
print(json.dumps(out))`,
        ],
        { encoding: "utf8" },
      ),
    );
    const officeResult = await send(
      {
        email: "office@example.invalid",
        support: ["Mentorim dhe këshilla për biznes"],
        plan: "Financim ose njohje me investitorë",
      },
      Object.entries(office).map(([ext, bytes]) => ({
        name: `plan.${ext}`,
        bytes: Buffer.from(bytes, "base64"),
        type: "application/zip",
      })),
    );
    assert.equal(officeResult.status, 200, JSON.stringify(officeResult));
    const fundingFilter = await admin.request.get(
      base +
        "/wp-admin/admin.php?page=vp-kthehu&support=" +
        encodeURIComponent("Financim ose njohje me investitorë"),
    );
    assert.ok(
      !(await fundingFilter.text()).includes(officeResult.data.reference),
    );
    const imageResult = await send({ email: "images@example.invalid" }, [
      {
        name: "photo.jpg",
        bytes: require("node:fs").readFileSync(
          require("node:path").resolve(__dirname, "../assets/portrait.jpg"),
        ),
        type: "image/jpeg",
      },
      {
        name: "photo.png",
        bytes: Buffer.from(
          "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6ZQAAAABJRU5ErkJggg==",
          "base64",
        ),
        type: "image/png",
      },
    ]);
    assert.equal(imageResult.status, 200, JSON.stringify(imageResult));
    assert.equal(
      (await send({}, [{ ...pdf, name: "forged.docx" }])).status,
      422,
    );
    console.log(
      "PASS DOCX, PPTX, XLSX, JPG, PNG uploads and forged Office rejection",
    );
    const largeBytes = Buffer.alloc(10 * 1024 * 1024, 32);
    Buffer.from("%PDF-1.4\n").copy(largeBytes);
    Buffer.from("\n%%EOF").copy(largeBytes, largeBytes.length - 6);
    const largeFile = {
      name: "large.pdf",
      bytes: largeBytes,
      type: "application/pdf",
    };
    snapshot = await fixtureCall("inspect");
    const before = snapshot.rows.length;
    const chunksBefore = Number(snapshot.file_count);
    await fixtureCall("failchunk");
    const rolledBack = await send({ email: "rollback@example.invalid" }, [
      largeFile,
    ]);
    assert.equal(rolledBack.status, 503, JSON.stringify(rolledBack));
    await fixtureCall("okchunk");
    snapshot = await fixtureCall("inspect");
    assert.equal(snapshot.rows.length, before);
    assert.equal(Number(snapshot.file_count), chunksBefore);
    const boundary = await send({ email: "boundary@example.invalid" }, [
      largeFile,
      largeFile,
      largeFile,
    ]);
    assert.equal(boundary.status, 200, JSON.stringify(boundary));
    const oversized = await send({ email: "oversized@example.invalid" }, [
      { ...largeFile, bytes: Buffer.concat([largeBytes, Buffer.from("x")]) },
    ]);
    assert.equal(oversized.status, 422, JSON.stringify(oversized));
    console.log(
      "PASS rollback after failed chunk write, 3 × 10 MB uploads, and oversized rejection",
    );
    const wrongOrigin = await fetch(base + "/wp-json/valon/v1/kthehu/apply", {
      method: "POST",
      headers: { Origin: "https://example.invalid" },
    });
    assert.equal(wrongOrigin.status, 403);
    await fixtureCall("reset");
    for (let n = 0; n < 10; n++)
      assert.equal((await send({ email: "invalid" })).status, 422);
    assert.equal((await send({ email: "invalid" })).status, 429);
    console.log("PASS cross-origin denial and rate limit");
    // Retention removes the application and its private chunks together.
    snapshot = await fixtureCall("expire&id=" + attachmentRow.id);
    assert.ok(!snapshot.rows.some((r) => r.id === attachmentRow.id));
    assert.equal((await admin.request.get(link)).status(), 404);
    console.log("PASS retention deletion of application and attachments");
    console.log("ALL KTHEHU BROWSER CHECKS PASSED");
  } finally {
    await browser.close();
  }
})().catch((e) => {
  console.error(e);
  process.exitCode = 1;
});
