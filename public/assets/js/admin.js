console.log("✅ admin.js bien chargé");

// ========== INITIALISATION NAV & FLASH ==========
document.addEventListener("DOMContentLoaded", () => {
  const burger = document.querySelector(".burger");
  const actions = document.querySelector(".site-nav .actions");
  if (burger && actions) {
    actions.classList.remove("open");
    burger.addEventListener("click", () => actions.classList.toggle("open"));
  }

  document.querySelectorAll(".flash-message[data-auto-dismiss]").forEach((el) => {
    const delay = parseInt(el.dataset.autoDismiss, 10);
    scheduleFlashDismiss(el, Number.isFinite(delay) ? delay : FLASH_DISMISS_DELAY);
  });

  document
    .querySelectorAll("form button[type='submit'][name='action']")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        if (btn.form) btn.form.dataset.lastAction = btn.value;
      });
    });
  try {
    const storedFlash = sessionStorage.getItem("adminFlash");
    if (storedFlash) {
      sessionStorage.removeItem("adminFlash");
      const data = JSON.parse(storedFlash);
      if (data && data.message) {
        pushFlash(data.message, data.type || "success");
      }
    }
  } catch (error) {
    console.error("Unable to restore admin flash message:", error);
  }
});

// ========== UTILITAIRES ==========
const DEFAULT_ERROR_MESSAGE = "Une erreur est survenue. Veuillez reessayer.";
const FLASH_DISMISS_DELAY = 5000;

function scheduleFlashDismiss(el, timeout = FLASH_DISMISS_DELAY) {
  const delay = Number.isFinite(timeout) ? timeout : FLASH_DISMISS_DELAY;
  setTimeout(() => {
    if (el.classList.contains("hide")) return;
    el.classList.add("hide");
    setTimeout(() => el.remove(), 800);
  }, delay);
}

function pushFlash(message, type = "success", timeout = FLASH_DISMISS_DELAY) {
  const container = document.getElementById("flash-container");
  if (!container) {
    if (type === "error") console.error(message);
    alert(message);
    return;
  }

  const flash = document.createElement("div");
  flash.className = "flash-message";
  if (type === "error") flash.classList.add("error");
  flash.dataset.autoDismiss = String(timeout);
  flash.textContent = message;
  container.appendChild(flash);
  scheduleFlashDismiss(flash, timeout);
}

function showError(message) {
  pushFlash(message || DEFAULT_ERROR_MESSAGE, "error");
}

function toJson(response) {
  if (!response.ok) throw new Error("Reponse reseau invalide");
  return response.json();
}

function handleRequestError(error) {
  console.error(error);
  showError("La requete a echoue. Veuillez reessayer.");
}
function getSectionMeta(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) return null;
  let page = parseInt(section.dataset.currentPage || "1", 10);
  if (!page || page < 1) page = 1;
  const param = section.dataset.pageParam || "";
  return { section, page, param };
}

function refreshSection(sectionId, options = {}) {
  const meta = getSectionMeta(sectionId);
  const url = new URL(window.location.href);
  if (meta && meta.param) {
    if (meta.page > 1) url.searchParams.set(meta.param, meta.page);
    else url.searchParams.delete(meta.param);
  }

  if (options.message) {
    try {
      sessionStorage.setItem(
        "adminFlash",
        JSON.stringify({
          message: options.message,
          type: options.type || "success",
        })
      );
    } catch (storageError) {
      console.error("Unable to persist admin flash message:", storageError);
    }
  }

  url.hash = sectionId;
  window.location.href = url.toString();
}

// ========== RÉSERVATIONS ==========
function terminer(button, reservationId, livreId) {
  const params = new URLSearchParams();
  params.append("action", "terminer");
  params.append("id", reservationId);
  params.append("livre_id", livreId);

  fetch("admin.php", { method: "POST", body: params })
    .then(toJson)
    .then((d) => {
      if (d.success) {
        const row = button.closest("tr");
        const statusLabel = d.statut_label || d.statut || "terminer";
        if (row) {
          const statutCell = row.querySelector('td[data-cell="statut"]');
          if (statutCell) statutCell.textContent = statusLabel;
          const actionCell = row.querySelector('td[data-cell="actions"]');
          if (actionCell) actionCell.textContent = "";
        }
        pushFlash(`Reservation ${statusLabel} mise a jour.`);
      } else showError(d.message);
    })
    .catch(handleRequestError);
}

// Gestion sous-onglets
document.addEventListener("DOMContentLoaded", () => {
  const subTabButtons = document.querySelectorAll(".subTabBtn");
  const subTabContents = document.querySelectorAll(".subTabContent");

  // Clic sur un sous-onglet
  subTabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      subTabButtons.forEach((b) => b.classList.remove("active"));
      subTabContents.forEach((c) => c.classList.remove("active"));

      btn.classList.add("active");
      const target = document.getElementById(btn.dataset.subtab);
      if (target) target.classList.add("active");
    });
  });

  // Afficher uniquement le sous-onglet "En attente" par défaut
  const defaultBtn = document.querySelector(".subTabBtnenattente");
  const defaultContent = document.getElementById("attente");

  if (defaultBtn && defaultContent) {
    subTabButtons.forEach((b) => b.classList.remove("active"));
    subTabContents.forEach((c) => c.classList.remove("active"));
    defaultBtn.classList.add("active");
    defaultContent.classList.add("active");
  }
});

// ========== LIVRES ==========
function filterBooks() {
  const input = document.getElementById("searchBook").value.toLowerCase();
  document.querySelectorAll("#booksTable tr").forEach((row, i) => {
    if (i === 0) return;
    const title = row.cells[0]?.innerText.toLowerCase() || "";
    const author = row.cells[1]?.innerText.toLowerCase() || "";
    const genre = row.cells[2]?.innerText.toLowerCase() || "";
    const searchableText = `${title} ${author} ${genre}`.toLowerCase();
    row.style.display = searchableText.includes(input) ? "" : "none";
  });
}

function openAddModal() {
  const modal = document.getElementById("modalAdd");
  const box = modal.firstElementChild;
  modal.style.display = "flex";
  setTimeout(() => {
    box.style.transform = "scale(1)";
    box.style.opacity = "1";
  }, 10);
}

function closeAddModal() {
  const modal = document.getElementById("modalAdd");
  const box = modal.firstElementChild;
  box.style.transform = "scale(0.9)";
  box.style.opacity = "0";
  setTimeout(() => (modal.style.display = "none"), 300);
}

document.addEventListener("DOMContentLoaded", () => {
  const formAdd = document.getElementById("formAddBook");
  if (formAdd) {
    formAdd.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = new URLSearchParams(new FormData(e.target));
      data.append("action", "ajouterLivre");
      fetch("admin.php", { method: "POST", body: data })
        .then(toJson)
        .then((d) => {
          if (d.success) {
            e.target.reset();
            closeAddModal();
            refreshSection("gererLivres", { message: "Livre ajoute avec succes." });
          } else showError(d.message);
        })
        .catch(handleRequestError);
    });
  }
});

function openEditModal(button) {
  const livre = JSON.parse(button.dataset.livre);
  const modal = document.getElementById("modalEdit");
  document.getElementById("modal_edit_livre_id").value = livre.livre_id;
  document.getElementById("modal_edit_titre").value = livre.titre;
  document.getElementById("modal_edit_auteur").value = livre.auteur;
  document.getElementById("modal_edit_genre").value = livre.genre;
  document.getElementById("modal_edit_description").value = livre.description;
  document.getElementById("modal_edit_image_url").value = livre.image_url;
  modal.style.display = "flex";
  setTimeout(() => {
    modal.firstElementChild.style.transform = "scale(1)";
    modal.firstElementChild.style.opacity = "1";
  }, 10);
}

function closeEditModal() {
  const modal = document.getElementById("modalEdit");
  const box = modal.firstElementChild;
  box.style.transform = "scale(0.9)";
  box.style.opacity = "0";
  setTimeout(() => (modal.style.display = "none"), 300);
}

document.addEventListener("DOMContentLoaded", () => {
  const formEdit = document.getElementById("formEditBookModal");
  if (formEdit) {
    formEdit.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = new URLSearchParams(new FormData(e.target));
      data.append("action", "modifierLivre");
      fetch("admin.php", { method: "POST", body: data })
        .then(toJson)
        .then((d) => {
          if (d.success && d.book) {
            const row = document.querySelector(
              `#booksTable tr[data-id="${d.book.livre_id}"]`
            );
            if (row) {
              if (row.cells[0]) row.cells[0].textContent = d.book.titre || "";
              if (row.cells[1]) row.cells[1].textContent = d.book.auteur || "";
              if (row.cells[2]) row.cells[2].textContent = d.book.genre || "";
              const editBtn = row.querySelector("button.modifier");
              if (editBtn) editBtn.dataset.livre = JSON.stringify(d.book);
            }
            closeEditModal();
            pushFlash("Livre mis a jour.");
          } else showError(d.message);
        })
        .catch(handleRequestError);
    });
  }
});

function deleteBook(button, livreId) {
  if (!confirm("Supprimer ce livre ?")) return;
  const params = new URLSearchParams();
  params.append("action", "supprimerLivre");
  params.append("livre_id", livreId);
  fetch("admin.php", { method: "POST", body: params })
    .then(toJson)
    .then((d) => {
      if (d.success) refreshSection("gererLivres", { message: "Livre supprime." });
      else showError(d.message);
    })
    .catch(handleRequestError);
}

function deleteUser(button, utilisateurId, pseudo) {
  if (!confirm("Supprimer l'utilisateur " + pseudo + " ?")) return;
  const params = new URLSearchParams();
  params.append("action", "supprimerUtilisateur");
  params.append("utilisateur_id", utilisateurId);
  fetch("admin.php", { method: "POST", body: params })
    .then(toJson)
    .then((d) => {
      if (d.success) refreshSection("utilisateurs", { message: "Utilisateur supprime." });
      else showError(d.message);
    })
    .catch(handleRequestError);
}

// ========== GRAPHIQUES ==========
let chartsCreated = false;

function openTab(tabName, evt, skipHashUpdate) {
  document.querySelectorAll(".tabContent").forEach((c) => c.classList.remove("active"));
  const target = document.getElementById(tabName);
  if (target) target.classList.add("active");

  document.querySelectorAll(".tabBtn").forEach((b) => b.classList.remove("active"));
  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.add("active");
  } else {
    const button = document.querySelector(`.tabBtn[data-tab="${tabName}"]`);
    if (button) button.classList.add("active");
  }

  if (tabName === "statistiques" && !chartsCreated) {
    createCharts();
    chartsCreated = true;
  }

  if (!skipHashUpdate) {
    try {
      const url = new URL(window.location.href);
      url.hash = tabName;
      history.replaceState(null, "", url);
    } catch (error) {
      window.location.hash = tabName;
    }
  }
}

function createCharts() {
  const { livres, users } = window.chartData;
  const noDataMsg = document.getElementById("noDataMessage");

  if (livres.data.length === 0 && users.data.length === 0) {
    noDataMsg.style.display = "block";
    return;
  } else {
    noDataMsg.style.display = "none";
  }

  const chartOptions = {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { enabled: true } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 } },
      x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
    },
  };

  if (livres.data.length > 0) {
    const canvasLivres = document.getElementById("graphLivres");
    canvasLivres.style.display = "block";
    new Chart(canvasLivres, {
  type: "bar",
  data: {
    labels: livres.labels,
    datasets: [
      {
        label: "Réservations",
        data: livres.data,
        backgroundColor: "#52a058cc",
        borderColor: "#52a058",
        borderWidth: 1,
        borderRadius: 6,
        barPercentage: 0.6,
      },
    ],
  },
  options: {
    ...chartOptions,
    indexAxis: "y",
    scales: {
      x: {
        beginAtZero: true,
        ticks: {
          stepSize: 1,
          precision: 0,
        },
      },
      y: {
        ticks: {
          autoSkip: false,
        },
      },
    },
  },
});

  }

  if (users.data.length > 0) {
    const canvasUsers = document.getElementById("graphUsers");
    canvasUsers.style.display = "block";
    new Chart(canvasUsers, {
      type: "bar",
      data: {
        labels: users.labels,
        datasets: [
          {
            label: "Réservations",
            data: users.data,
            backgroundColor: "#f5a623cc",
            borderColor: "#f57c00",
            borderWidth: 1,
            borderRadius: 6,
            barPercentage: 0.6,
          },
        ],
      },
      options: chartOptions,
    });
  }
}

// ========== INITIALISATION ==========
document.addEventListener("DOMContentLoaded", () => {
  // --- Onglet actif par defaut ---
  const initialHash = window.location.hash ? window.location.hash.substring(1) : "reservations";
  const targetTab = document.getElementById(initialHash) ? initialHash : "reservations";
  const initialButton = document.querySelector(`.tabBtn[data-tab="${targetTab}"]`);
  openTab(targetTab, { currentTarget: initialButton }, !window.location.hash);

  // --- Clics sur les onglets principaux ---
  document.querySelectorAll(".tabBtn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const tabName = btn.dataset.tab;
      if (tabName) {
        openTab(tabName, { currentTarget: btn });
      }
    });
  });

  // --- GESTION FORMULAIRES ADMIN (Valider / Refuser / Terminer) ---
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", async (e) => {
      const submitter = e.submitter;
      const actionValue = form.dataset.lastAction || (submitter ? submitter.value : "");
      form.dataset.lastAction = "";

      if (!actionValue || !["valider", "refuser", "terminer"].includes(actionValue)) {
        return;
      }

      e.preventDefault();
      const formData = new FormData(form);
      formData.set("action", actionValue);

      try {
        const response = await fetch("admin.php", { method: "POST", body: formData });
        const result = await response.json();

        if (result.success) {
          const statusLabel = result.statut_label || result.statut || actionValue;
          const row = form.closest("tr");
          if (row) {
            const statutCell = row.querySelector('td[data-cell="statut"]');
            if (statutCell) statutCell.textContent = statusLabel;

            const actionCell = row.querySelector('td[data-cell="actions"]');
            if (actionCell) actionCell.innerHTML = "";
          }
          pushFlash(`Reservation ${statusLabel} avec succes !`);
        } else {
          showError(result.message || "Une erreur est survenue.");
        }
      } catch (err) {
        console.error("Erreur AJAX :", err);
        showError("Erreur de communication avec le serveur.");
      }
    });
  });
});