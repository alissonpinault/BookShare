// ==========================
// 📘 admin.js (BookShare)
// ==========================

document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ admin.js chargé");

    // --- Menu Burger ---
    const burger = document.querySelector(".burger");
    const actions = document.querySelector(".site-nav .actions");
    if (burger && actions) {
        burger.addEventListener("click", () => actions.classList.toggle("open"));
    }

    // --- Outils génériques ---
    const showError = msg => alert(msg || "Une erreur est survenue.");
    const toJson = r => r.ok ? r.json() : Promise.reject("Erreur réseau");
    const handleError = e => { console.error(e); showError(); };

    // --- Changement d’onglet principal ---
    function openTab(tabName) {
        document.querySelectorAll(".tabContent").forEach(c => c.style.display = "none");
        document.querySelectorAll(".tabBtn").forEach(b => b.classList.remove("active"));
        const tab = document.getElementById(tabName);
        const btn = document.querySelector(`.tabBtn[data-tab="${tabName}"]`);
        if (tab) tab.style.display = "block";
        if (btn) btn.classList.add("active");
        if (tabName === "statistiques") createCharts();
    }

    // --- Gestion des sous-onglets réservations ---
    const subTabs = document.querySelectorAll(".subTabBtnenattente, .subTabBtnencours, .subTabBtnarchive");
    const subContents = document.querySelectorAll(".subTabContentenattente, .subTabContentencours, .subTabContentarchive");

    subTabs.forEach(btn => {
        btn.addEventListener("click", () => {
            subTabs.forEach(b => b.classList.remove("active"));
            subContents.forEach(c => c.style.display = "none");
            btn.classList.add("active");
            const target = document.getElementById(btn.dataset.subtab);
            if (target) target.style.display = "block";
        });
    });

    // --- Actions Réservations (Valider / Refuser / Terminer) ---
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", e => {
            const actionBtn = form.querySelector("button[name='action']");
            if (!actionBtn) return;
            const action = actionBtn.value;

            if (["valider", "refuser", "terminer"].includes(action)) {
                e.preventDefault();
                const data = new FormData(form);

                fetch("admin.php", { method: "POST", body: data })
                    .then(toJson)
                    .then(d => {
                        if (d.success) {
                            alert(`✅ Action "${action}" effectuée avec succès !`);
                            location.reload();
                        } else showError(d.message);
                    })
                    .catch(handleError);
            }
        });
    });

    // --- Recherche Livre ---
    window.filterBooks = function() {
        const input = document.getElementById('searchBook').value.toLowerCase();
        document.querySelectorAll('#booksTable tr').forEach((row, i) => {
            if (i === 0) return;
            const text = Array.from(row.cells).map(c => c.innerText.toLowerCase()).join(" ");
            row.style.display = text.includes(input) ? "" : "none";
        });
    };

    // --- Modal Ajout Livre ---
    window.openAddModal = function() {
        const modal = document.getElementById('modalAdd');
        const box = modal.firstElementChild;
        modal.style.display = 'flex';
        setTimeout(() => { box.style.transform = 'scale(1)'; box.style.opacity = '1'; }, 10);
    };
    window.closeAddModal = function() {
        const modal = document.getElementById('modalAdd');
        const box = modal.firstElementChild;
        box.style.transform = 'scale(0.9)'; box.style.opacity = '0';
        setTimeout(() => modal.style.display = 'none', 300);
    };

    const formAdd = document.getElementById('formAddBook');
    if (formAdd) {
        formAdd.addEventListener('submit', e => {
            e.preventDefault();
            const data = new URLSearchParams(new FormData(e.target));
            data.append('action', 'ajouterLivre');
            fetch('admin.php', { method: 'POST', body: data })
                .then(toJson)
                .then(d => {
                    if (d.success) {
                        e.target.reset();
                        closeAddModal();
                        location.reload();
                    } else showError(d.message);
                })
                .catch(handleError);
        });
    }

    // --- Graphiques (avec window.chartData injecté par PHP) ---
    function createCharts() {
        if (!window.chartData) return;
        const livres = window.chartData.livres;
        const users = window.chartData.users;

        const options = {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            scales: { y: { beginAtZero: true } }
        };

        if (livres.data.length) {
            new Chart(document.getElementById('graphLivres'), {
                type: 'bar',
                data: { labels: livres.labels, datasets: [{ data: livres.data, backgroundColor: '#52a058cc' }] },
                options
            });
        }
        if (users.data.length) {
            new Chart(document.getElementById('graphUsers'), {
                type: 'bar',
                data: { labels: users.labels, datasets: [{ data: users.data, backgroundColor: '#f5a623cc' }] },
                options
            });
        }
    }
});