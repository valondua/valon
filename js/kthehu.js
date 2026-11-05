(() => {
  "use strict";
  const form = document.querySelector("#kt-form");
  if (!form) return;
  const button = document.querySelector("#kt-submit");
  const status = document.querySelector("#kt-status");
  const retry = document.querySelector("#kt-retry");
  let sending = false;
  async function ticket() {
    button.disabled = true;
    retry.hidden = true;
    try {
      const response = await fetch(kthehuConfig.ticket, {
        cache: "no-store",
        credentials: "same-origin",
      });
      const result = await response.json();
      if (!response.ok || !result.token) throw new Error();
      form.elements.token.value = result.token;
      status.textContent = "";
      button.disabled = false;
    } catch {
      status.textContent =
        "Formulari s’u lidh. Provo përsëri; përgjigjet mbesin këtu.";
      retry.hidden = false;
    }
  }
  retry.addEventListener("click", ticket);
  document
    .querySelector('a[href="#kt-privacy"]')
    ?.addEventListener("click", () => {
      document.querySelector("#kt-privacy").open = true;
    });
  ticket();
  function error(key, message) {
    const label = document.getElementById(`kt-${key}-error`);
    if (label) label.textContent = message;
    const input = document.getElementById(`kt-${key}`);
    if (input) input.setAttribute("aria-invalid", "true");
  }
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (sending) return;
    form.querySelectorAll(".kt-error").forEach((el) => {
      el.textContent = "";
    });
    form
      .querySelectorAll("[aria-invalid]")
      .forEach((el) => el.removeAttribute("aria-invalid"));
    let firstInvalid = null;
    [...form.elements].forEach((input) => {
      if (!input.disabled && input.willValidate && !input.checkValidity()) {
        error(
          input.name,
          input.validity.valueMissing
            ? "Plotëso këtë fushë."
            : "Kontrolloje këtë përgjigje.",
        );
        firstInvalid ||= input;
      }
    });
    const upload = document.getElementById("kt-attachments");
    const files = [...upload.files];
    if (
      files.length > 1 ||
      files.some(
        (file) =>
          file.size > 10 * 1024 * 1024 ||
          !/\.(pdf|docx|pptx|xlsx|jpg|png)$/i.test(file.name),
      )
    ) {
      error(
        "attachments",
        "Zgjedh nji dokument deri në 10 MB: PDF, DOCX, PPTX, XLSX, JPG ose PNG.",
      );
      firstInvalid ||= upload;
    }
    if (firstInvalid) {
      status.textContent = "Kontrolloji fushat e shënueme.";
      const details = firstInvalid.closest("details");
      if (details) details.open = true;
      firstInvalid.focus();
      return;
    }
    sending = true;
    button.disabled = true;
    form.setAttribute("aria-busy", "true");
    status.textContent = "Po e dërgojmë mesazhin…";
    try {
      const response = await fetch(kthehuConfig.apply, {
        method: "POST",
        body: new FormData(form),
        credentials: "same-origin",
      });
      const result = await response.json();
      if (!response.ok) {
        Object.entries(result.data?.fields || {}).forEach(([key, message]) =>
          error(key, message),
        );
        status.textContent =
          result.message || "Dërgimi dështoi. Provo përsëri.";
        const key = Object.keys(result.data?.fields || {})[0];
        (document.getElementById(`kt-${key}`) || status).focus();
        if (result.code === "token") {
          form.elements.token.value = "";
          retry.hidden = false;
        }
        return;
      }
      if (!result.reference) throw new Error();
      const confirmation = document.createElement("div");
      confirmation.id = "kt-status";
      confirmation.className = "kt-success";
      confirmation.setAttribute("role", "status");
      confirmation.tabIndex = -1;
      const heading = document.createElement("h2");
      heading.textContent = "Historia jote mbërriti.";
      const message = document.createElement("p");
      message.textContent = result.message;
      const reference = document.createElement("strong");
      reference.textContent = `Referenca: ${result.reference}`;
      const mail = document.createElement("p");
      mail.textContent = result.email_sent
        ? "Konfirmimin ta nisëm edhe me email. Kontrolloje edhe dosjen spam."
        : "Emaili i konfirmimit asht në pritje. Aplikimi yt asht ruejt — s’ka nevojë me e dërgu përsëri.";
      confirmation.append(heading, message, reference, mail);
      form.replaceWith(confirmation);
      confirmation.focus();
    } catch {
      status.textContent =
        "S’morëm konfirmim nga serveri. Përgjigjet tua mbesin këtu. Provo përsëri me këtë formular; nëse aplikimi asht ruejt, nuk krijohet dy herë.";
      status.focus();
    } finally {
      sending = false;
      button.disabled = !form.elements.token.value;
      form.removeAttribute("aria-busy");
    }
  });
})();
