(function () {
  "use strict";

  const menuButton = document.querySelector("[data-menu-button]");
  const mobileNav = document.querySelector("[data-mobile-nav]");

  if (menuButton && mobileNav) {
    const closeMenu = (restoreFocus) => {
      menuButton.setAttribute("aria-expanded", "false");
      menuButton.setAttribute("aria-label", "Ouvrir le menu");
      mobileNav.hidden = true;
      if (restoreFocus) menuButton.focus();
    };

    menuButton.addEventListener("click", () => {
      const willOpen = menuButton.getAttribute("aria-expanded") !== "true";
      menuButton.setAttribute("aria-expanded", String(willOpen));
      menuButton.setAttribute("aria-label", willOpen ? "Fermer le menu" : "Ouvrir le menu");
      mobileNav.hidden = !willOpen;
    });

    mobileNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => closeMenu(false));
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !mobileNav.hidden) closeMenu(true);
    });

    window.addEventListener("resize", () => {
      if (window.matchMedia("(min-width: 981px)").matches) closeMenu(false);
    });
  }

  const customAmount = document.querySelector("[data-custom-amount]");
  const presetAmounts = [...document.querySelectorAll("input[name='amount-preset']")];
  const frequencyInputs = [...document.querySelectorAll("input[name='frequency']")];
  const donationForm = document.querySelector("[data-donation-form]");
  const donationStatus = document.querySelector("[data-donation-status]");

  const formatAmount = (value) => {
    const amount = Number(value);
    if (!Number.isFinite(amount)) return "—";
    return new Intl.NumberFormat("fr-CH", { maximumFractionDigits: 2 }).format(amount);
  };

  const selectedAmount = () => {
    if (customAmount && customAmount.value.trim() !== "") return Number(customAmount.value);
    const selected = presetAmounts.find((input) => input.checked);
    return selected ? Number(selected.value) : NaN;
  };

  const updateSummary = () => {
    const amountOutput = document.querySelector("[data-summary-amount]");
    const frequencyOutput = document.querySelector("[data-summary-frequency]");
    const allocationOutput = document.querySelector("[data-summary-allocation]");
    const amount = selectedAmount();
    const frequency = frequencyInputs.find((input) => input.checked);
    const allocation = document.querySelector("[name='allocation']");

    if (amountOutput) amountOutput.textContent = Number.isFinite(amount) && amount > 0 ? `${formatAmount(amount)} CHF` : "À choisir";
    if (frequencyOutput) frequencyOutput.textContent = frequency ? frequency.dataset.label : "À choisir";
    if (allocationOutput && allocation) allocationOutput.textContent = allocation.options[allocation.selectedIndex].text;
  };

  const setDonationStatus = (message, state) => {
    if (!donationStatus) return;
    donationStatus.textContent = message;
    donationStatus.dataset.state = state;
    donationStatus.hidden = false;
    donationStatus.focus();
  };

  presetAmounts.forEach((input) => {
    input.addEventListener("change", () => {
      if (customAmount) customAmount.value = "";
      updateSummary();
    });
  });

  frequencyInputs.forEach((input) => input.addEventListener("change", updateSummary));

  if (customAmount) {
    customAmount.addEventListener("input", () => {
      if (customAmount.value !== "") presetAmounts.forEach((input) => { input.checked = false; });
      updateSummary();
    });
  }

  const allocation = document.querySelector("[name='allocation']");
  if (allocation) allocation.addEventListener("change", updateSummary);

  if (donationForm) {
    const queryAmount = new URLSearchParams(window.location.search).get("montant");
    if (queryAmount && /^\d+(?:[.,]\d{1,2})?$/.test(queryAmount)) {
      const normalized = queryAmount.replace(",", ".");
      const matchingPreset = presetAmounts.find((input) => input.value === normalized);
      if (matchingPreset) matchingPreset.checked = true;
      else if (customAmount) customAmount.value = normalized;
    }

    donationForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const amount = selectedAmount();
      if (!Number.isFinite(amount) || amount <= 0) {
        setDonationStatus("Choisissez un montant positif pour continuer.", "error");
        if (customAmount) customAmount.focus();
        return;
      }
      if (Math.round(amount * 100) !== amount * 100) {
        setDonationStatus("Saisissez au maximum deux décimales.", "error");
        if (customAmount) customAmount.focus();
        return;
      }
      setDonationStatus("Le formulaire frontend est valide. Aucun paiement n’a été lancé : la connexion sécurisée au prestataire relève du backend.", "info");
    });

    updateSummary();
  }

  document.querySelectorAll("[data-contact-form]").forEach((form) => {
    const status = form.querySelector("[data-form-status]");
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      if (status) {
        status.textContent = "Le formulaire est prêt côté interface. Aucun message n’a été envoyé tant que le backend et l’adresse officielle ne sont pas configurés.";
        status.hidden = false;
        status.focus();
      }
    });
  });
})();
