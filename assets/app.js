(function () {
  "use strict";

  const menuButton = document.querySelector("[data-menu-button]");
  const mobileNav = document.querySelector("[data-mobile-nav]");
  if (menuButton && mobileNav) {
    const closeMenu = (restoreFocus) => { menuButton.setAttribute("aria-expanded", "false"); menuButton.setAttribute("aria-label", "Ouvrir le menu"); mobileNav.hidden = true; if (restoreFocus) menuButton.focus(); };
    menuButton.addEventListener("click", () => { const willOpen = menuButton.getAttribute("aria-expanded") !== "true"; menuButton.setAttribute("aria-expanded", String(willOpen)); menuButton.setAttribute("aria-label", willOpen ? "Fermer le menu" : "Ouvrir le menu"); mobileNav.hidden = !willOpen; });
    mobileNav.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => closeMenu(false)));
    document.addEventListener("keydown", (event) => { if (event.key === "Escape" && !mobileNav.hidden) closeMenu(true); });
    window.addEventListener("resize", () => { if (window.matchMedia("(min-width: 981px)").matches) closeMenu(false); });
  }

  const customAmount = document.querySelector("[data-custom-amount]");
  const presetAmounts = [...document.querySelectorAll("input[name='amount-preset']")];
  const frequencyInputs = [...document.querySelectorAll("input[name='frequency']")];
  const donationForm = document.querySelector("[data-donation-form]");
  const donationStatus = document.querySelector("[data-donation-status]");
  const formatAmount = (value) => { const amount = Number(value); return Number.isFinite(amount) ? new Intl.NumberFormat("fr-CH", { maximumFractionDigits: 2 }).format(amount) : "—"; };
  const selectedAmountText = () => { if (customAmount && customAmount.value.trim() !== "") return customAmount.value.trim().replace(",", "."); const selected = presetAmounts.find((input) => input.checked); return selected ? selected.value : ""; };
  const updateSummary = () => {
    const amountOutput = document.querySelector("[data-summary-amount]"); const frequencyOutput = document.querySelector("[data-summary-frequency]"); const allocationOutput = document.querySelector("[data-summary-allocation]");
    const amount = Number(selectedAmountText()); const frequency = frequencyInputs.find((input) => input.checked); const allocation = document.querySelector("[name='allocation']");
    if (amountOutput) amountOutput.textContent = Number.isFinite(amount) && amount > 0 ? `${formatAmount(amount)} CHF` : "À choisir";
    if (frequencyOutput) frequencyOutput.textContent = frequency ? frequency.dataset.label : "À choisir";
    if (allocationOutput && allocation) allocationOutput.textContent = allocation.options[allocation.selectedIndex].text;
  };
  const setStatus = (node, message, state) => { if (!node) return; node.textContent = message; node.dataset.state = state; node.hidden = false; node.focus(); };
  presetAmounts.forEach((input) => input.addEventListener("change", () => { if (customAmount) customAmount.value = ""; updateSummary(); }));
  frequencyInputs.forEach((input) => input.addEventListener("change", updateSummary));
  if (customAmount) customAmount.addEventListener("input", () => { if (customAmount.value !== "") presetAmounts.forEach((input) => { input.checked = false; }); updateSummary(); });
  const allocation = document.querySelector("[name='allocation']"); if (allocation) allocation.addEventListener("change", updateSummary);
  const getCsrf = async () => { const response = await fetch("/session/csrf", { credentials: "same-origin", headers: { Accept: "application/json" } }); if (!response.ok) throw new Error("Le service de sécurité est indisponible."); return (await response.json()).token; };
  const idempotencyKey = () => (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`);

  if (donationForm) {
    const checkoutKey = idempotencyKey();
    const queryAmount = new URLSearchParams(window.location.search).get("montant");
    if (queryAmount && /^\d+(?:[.,]\d{1,2})?$/.test(queryAmount)) { const normalized = queryAmount.replace(",", "."); const matchingPreset = presetAmounts.find((input) => input.value === normalized); if (matchingPreset) matchingPreset.checked = true; else { presetAmounts.forEach((input) => { input.checked = false; }); if (customAmount) customAmount.value = normalized; } }
    donationForm.addEventListener("submit", async (event) => {
      event.preventDefault(); const amountText = selectedAmountText();
      if (!/^\d+(?:\.\d{1,2})?$/.test(amountText) || Number(amountText) < 1 || Number(amountText) > 1000000) { setStatus(donationStatus, "Saisissez un montant entre 1 et 1 000 000 CHF, avec deux décimales au maximum.", "error"); if (customAmount) customAmount.focus(); return; }
      if (!donationForm.reportValidity()) return;
      const payload = Object.fromEntries(new FormData(donationForm).entries()); payload.amount = amountText; payload.currency = "CHF"; payload.frequency = payload.frequency === "unique" ? "one_time" : payload.frequency; delete payload["amount-preset"]; delete payload.custom_amount; delete payload.newsletter;
      try { const csrf = await getCsrf(); const response = await fetch("/dons/checkout", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf, "Idempotency-Key": checkoutKey }, body: JSON.stringify(payload) }); const result = await response.json(); if (!response.ok) throw new Error(result.error || "Le don n’a pas pu être préparé."); setStatus(donationStatus, "Votre don est prêt. Redirection vers le paiement sécurisé…", "success"); window.location.assign(result.checkout_url); } catch (error) { setStatus(donationStatus, error.message || "Le service est momentanément indisponible.", "error"); }
    });
    updateSummary();
  }

  document.querySelectorAll("[data-contact-form]").forEach((form) => {
    const status = form.querySelector("[data-form-status]");
    form.addEventListener("submit", async (event) => { event.preventDefault(); if (!form.reportValidity()) return; try { const csrf = await getCsrf(); const response = await fetch("/contact", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf }, body: JSON.stringify(Object.fromEntries(new FormData(form).entries())) }); const result = await response.json(); if (!response.ok) throw new Error(result.error || "Le message n’a pas pu être envoyé."); setStatus(status, result.message || "Votre message a bien été reçu.", "success"); form.reset(); } catch (error) { setStatus(status, error.message || "Le service est momentanément indisponible.", "error"); } });
  });
})();
