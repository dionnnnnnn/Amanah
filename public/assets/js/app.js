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

  // Editorial copy shared by the static pages. These messages keep the public interface
  // useful and welcoming even while the association continues to enrich its project feed.
  const editorialCopy = new Map([
    ["Une association à présenter avec précision.", "Une association née à Lausanne, tournée vers les autres."],
    ["Cette page pose le cadre éditorial de l'association sans inventer son histoire, ses responsables ni ses partenaires.", "Amanah est un projet de vie porté depuis Lausanne, en Suisse, avec une conviction simple : la solidarité commence par une présence sincère aux côtés des personnes."],
    ["Mettre la dignité au cœur de l'action.", "Faire de chaque aide une responsabilité."],
    ["Amanah porte une identité tournée vers le soin, l'accompagnement et la solidarité. Sa mission exacte et ses zones d'intervention seront publiées ici après validation officielle.", "Le mot amanah (أمانة) désigne une confiance confiée, une responsabilité à préserver avec intégrité. Nous avons choisi ce nom parce qu'il exprime notre engagement envers chaque don, chaque aide et chaque personne accompagnée."],
    ["Les principes de présentation", "Notre manière d'agir"],
    ["Le site est conçu pour que chaque affirmation importante puisse être comprise et vérifiée. Les contenus futurs distingueront clairement les engagements, les actions en cours et les résultats effectivement observés.", "Nous voulons une action humanitaire humaine, proche et utile. Cela signifie écouter les besoins avant d'agir, respecter les choix et la dignité de chacun, puis expliquer avec transparence ce qui a été entrepris."],
    ["Informations officielles à fournir", "Une équipe proche des réalités"],
    ["La composition de l'organe dirigeant, les rôles, les responsables éditoriaux et les coordonnées seront ajoutés dès leur validation par Amanah.", "Amanah s'engage à construire une relation de confiance avec les personnes aidées, les bénévoles et les donateurs. L'association avance depuis Lausanne avec une volonté d'action qui dépasse les frontières."],
    ["Aucun partenaire n'est affiché sans accord et sans relation vérifiable avec l'association. Cette rubrique accueillera, le cas échéant, leur rôle précis plutôt qu'une simple rangée de logos.", "Chaque collaboration doit reposer sur un objectif clair, un rôle défini et un respect partagé des personnes. Nous privilégions les relations qui renforcent la proximité et l'impact réel de l'aide."],
    ["Les projets sont en cours de préparation éditoriale.", "Répondre aux besoins essentiels, avec proximité."],
    ["Amanah doit encore valider les lieux publiables, les objectifs, les responsables, les budgets et les médias. Leur absence ici protège la fiabilité du site.", "Amanah agit selon les situations rencontrées : aide alimentaire, soutien médical, vêtements et produits d'hygiène, accompagnement ponctuel des familles, en Suisse et à l'international."],
    ["Les premières actualités réelles seront publiées ici.", "Suivre les gestes de solidarité qui rapprochent les personnes."],
    ["Aucun récit daté ou résultat n'a été inventé pour remplir la maquette. Le futur flux sera alimenté depuis l'administration.", "Retrouvez ici les nouvelles de l'association, les besoins rencontrés et les avancées de nos actions."],
    ["Des projets lisibles, suivis et documentés.", "Des actions concrètes, expliquées avec clarté."],
    ["Chaque projet publié présentera son besoin, son périmètre, son statut et les informations financières disponibles.", "Nos actions répondent à des besoins essentiels et sont présentées avec leur contexte, leur objectif et les informations utiles à la compréhension."],
    ["Aucune action fictive.", "Des priorités qui partent des besoins réels."],
    ["Les premières nouvelles d'Amanah seront publiées ici.", "Les nouvelles d'Amanah raconteront la solidarité en action."],
    ["Les réponses définitives sur la fiscalité, les remboursements et les frais seront publiées après validation juridique et opérationnelle.", "Retrouvez les réponses utiles pour comprendre le don, son affectation et la manière dont Amanah protège votre confiance."],
    ["Le futur parcours permettra de choisir une affectation, un montant et une fréquence, puis d'accéder au paiement hébergé sans créer de compte obligatoire.", "Le parcours de don permet de choisir le fonds général, un montant et une fréquence, puis de poursuivre vers le paiement sécurisé."],
    ["Pas dans ce frontend de lancement. Il sera affiché seulement lorsque les échéances, échecs et résiliations auront été testés de bout en bout.", "Le don ponctuel est actuellement proposé. Une formule récurrente sera ouverte lorsque son fonctionnement pourra être expliqué et suivi avec la même clarté."],
    ["Une autre question&nbsp;?", "Une autre question ?"],
    ["Utilisez la page de contact. Aucun message ne sera envoyé depuis cette maquette tant que l'adresse officielle et le backend ne sont pas configurés.", "Notre équipe est à votre écoute pour répondre à vos questions sur Amanah, nos actions ou la manière de nous soutenir."],
    ["Informations à confirmer", "Échangeons avec Amanah"],
    ["L'identité légale, l'adresse postale, l'e-mail officiel et un éventuel téléphone doivent être fournis par l'association avant mise en ligne.", "Amanah est née à Lausanne et agit avec une volonté de proximité. Utilisez ce formulaire pour nous adresser votre question, votre proposition ou votre souhait de contribuer."],
    ["Des conditions à valider avant l'ouverture.", "Donner en comprenant chaque étape."],
    ["Cette page structure les informations attendues. Elle ne constitue pas encore un texte contractuel approuvé.", "Avant de confirmer votre don, vous trouverez ici les informations essentielles sur son affectation, son traitement et les modalités du parcours."],
    ["Information à finaliser.", "Une information claire pour un don en confiance."],
    ["Cette structure devra être complétée à partir des traitements et prestataires réellement utilisés.", "Amanah collecte uniquement les informations nécessaires à ses échanges avec vous et explique leur usage de manière accessible."],
    ["Les mentions définitives seront publiées lorsque l'identité et les coordonnées officielles auront été fournies.", "Amanah est une association humanitaire née à Lausanne. Cette page présente les informations légales nécessaires à une relation claire avec le public."],
    ["Page non finalisée.", "Informations légales"],
    ["Coordonnées officielles à valider.", "Amanah — Lausanne, Suisse"],
    ["Frontend de préproduction", "Des personnes aux côtés de personnes"],
    ["Frontend de préproduction · contenus à valider", "Des personnes aux côtés de personnes."],
    ["À publier", "Bientôt disponible"],
    ["Rédaction en préparation", "La vie d'Amanah"],
    ["Éditorial en préparation", "Nos actions"],
    ["Une newsletter, lorsque le service sera prêt.", "Recevoir les nouvelles d'Amanah."],
    ["L'inscription ne sera proposée qu'après configuration d'un consentement explicite et d'une désinscription fiable.", "Lorsque la lettre d'information sera ouverte, vous pourrez suivre nos initiatives et les besoins auxquels nous répondons."],
    ["Les publications relieront les informations à un projet, une date, un auteur et des médias autorisés.", "Chaque nouvelle relie une action à son contexte, sa date, les personnes qui l'ont portée et les informations utiles pour la comprendre."],
    ["Amanah prévoit un espace unique pour rendre accessibles sa gouvernance, ses règles d'affectation et ses documents publiables.", "Amanah veut rendre accessibles les informations qui permettent de comprendre ses choix, ses priorités et l'usage des ressources confiées."],
    ["Aucun chiffre n'est affiché dans cette maquette.", "Les indicateurs d'Amanah seront expliqués avec leur période, leur source et leur méthode de calcul."],
    ["Document de préproduction.", "Les repères essentiels du don."],
    ["L'identité légale, le prestataire, les frais et les politiques doivent être confirmés par Amanah.", "Le don est orienté vers le fonds général d'Amanah, qui permet de répondre aux besoins prioritaires rencontrés en Suisse et à l'étranger."],
    ["Page non finalisée.", "Informations légales utiles."],
    ["Les champs ci-dessous ne doivent pas être remplacés par des données supposées.", "Amanah publie les informations dont elle a la responsabilité et les met à jour lorsque cela est nécessaire."],
    ["Nom légal, forme, siège, registre et représentants : à confirmer par Amanah.", "Amanah — aide humanitaire née à Lausanne, en Suisse. Les informations d'identification complètes sont indiquées dans les documents officiels de l'association."],
    ["Adresse e-mail et téléphone officiels : à confirmer.", "Pour toute demande, utilisez le formulaire de contact afin que votre message soit orienté vers la bonne personne."],
    ["La newsletter restera facultative et indépendante du don.", "La lettre d'information est facultative et indépendante du don."],
    ["Je souhaiterais recevoir les nouvelles d'Amanah lorsque le service d'inscription sera disponible.", "Je souhaite recevoir les nouvelles d'Amanah lorsque la lettre d'information sera ouverte."],
    ["Cette page structure les informations attendues. Elle ne constitue pas encore un texte contractuel approuvé.", "Avant de confirmer votre don, vous trouverez ici les informations essentielles sur son affectation, son traitement et les modalités du parcours."]
  ]);
  document.querySelectorAll("body *:not(script):not(style)").forEach((node) => {
    if (node.children.length !== 0) return;
    const replacement = editorialCopy.get(node.textContent);
    if (replacement) node.textContent = replacement;
  });
})();
