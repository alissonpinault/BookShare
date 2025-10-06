// ========== MENU BURGER ==========
document.addEventListener("DOMContentLoaded", () => {
  const burger = document.querySelector(".burger");
  const actions = document.querySelector(".site-nav .actions");
  if (!burger || !actions) return;
  actions.classList.remove("open");
  burger.addEventListener("click", () => actions.classList.toggle("open"));
});

// ========== UTILITAIRES ==========
const DEFAULT_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";

function showError(message) {
  alert(message || DEFAULT_ERROR_MESSAGE);
}

function toJson(response) {
  if (!response.ok) throw new Error("Réponse réseau invalide");
  return response.json();
}

function handleRequestError(error) {
  console.error(error);
  showError("La requête a échoué. Veuillez réessayer.");
}

function getSectionMeta(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) return null;
  let page = parseInt(section.dataset.currentPage || "1", 10);
  if (!page || page < 1) page = 1;
  const param = section.dataset.pageParam || "";
  return { section, page, param };
}

function refreshSection(sectionId) {
  const meta = getSectionMeta(sectionId);
  const url = new URL(window.location.href);
  if (meta && meta.param) {
    if (meta.page > 1) url.searchParams.set(meta.param, meta.page);
    else url.searchParams.delete(meta.param);
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
        if (row) {
          if (row.cells[3]) row.cells[3].textContent = d.statut || "terminer";
          if (row.cells[4]) row.cells[4].textContent = "";
        }
      } else showError(d.message);
    })
    .catch(handleRequestError);
}

// Gestion sous-onglets
document.addEventListener("DOMContentLoaded", () => {
  const subTabButtons = document.querySelectorAll(
    ".subTabBtnenattente, .subTabBtnencours, .subTabBtnarchive"
  );
  const subTabContents = document.querySelectorAll(
    ".subTabContentenattente, .subTabContentencours, .subTabContentarchive"
  );

  // Clic sur un sous-onglet
  subTabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      subTabButtons.forEach((b) => b.classList.remove("active"));
      subTabContents.forEach((c) => (c.style.display = "none"));

      btn.classList.add("active");
      const target = document.getElementById(btn.dataset.subtab);
      if (target) target.style.display = "block";
    });
  });

  // Afficher uniquement le sous-onglet "En attente" par défaut
  const defaultBtn = document.querySelector(".subTabBtnenattente");
  const defaultContent = document.getElementById("attente");

  if (defaultBtn && defaultContent) {
    subTabButtons.forEach((b) => b.classList.remove("active"));
    subTabContents.forEach((c) => (c.style.display = "none"));
    defaultBtn.classList.add("active");
    defaultContent.style.display = "block";
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
            refreshSection("gererLivres");
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
      if (d.success) refreshSection("gererLivres");
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
      if (d.success) refreshSection("utilisateurs");
      else showError(d.message);
    })
    .catch(handleRequestError);
}

// ========== GRAPHIQUES ==========
let chartsCreated = false;

function openTab(tabName, evt, skipHashUpdate) {
  document.querySelectorAll(".tabContent").forEach((c) => (c.style.display = "none"));
  const target = document.getElementById(tabName);
  if (target) target.style.display = "block";

  document.querySelectorAll(".tabBtn").forEach((b) => b.classList.remove("active"));
  if (evt && evt.currentTarget) evt.currentTarget.classList.add("active");
  else {
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
    } catch {
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
      options: chartOptions,
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
  const initialHash = window.location.hash ? window.location.hash.substring(1) : "reservations";
  const targetTab = document.getElementById(initialHash) ? initialHash : "reservations";
  const button = document.querySelector(`.tabBtn[data-tab="${targetTab}"]`);
  openTab(targetTab, { currentTarget: button }, !window.location.hash);
});