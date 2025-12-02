console.log("logout.js bien chargé");

document.addEventListener("DOMContentLoaded", () => {
    const logoutBtn = document.getElementById("logout-btn");
    if (!logoutBtn) return; // Rien si pas connecté

    const handleLogout = () => {
        fetch('/deconnexion.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {

                // Recharge dynamiquement la partie .actions de la navbar
                fetch('/nav.php')
                    .then(res => res.text())
                    .then(html => {
                        const actions = document.querySelector(".actions");
                        if (actions) {
                            actions.innerHTML = html;
                        }

                        // Réattache le listener sur le nouveau bouton de déconnexion
                        const newLogoutBtn = document.getElementById("logout-btn");
                        if (newLogoutBtn) {
                            newLogoutBtn.addEventListener("click", handleLogout);
                        }
                    });

            } else {
                console.error("Déconnexion échouée :", data.error);
            }
        })
        .catch(err => console.error("Erreur lors de la déconnexion :", err));
    };

    logoutBtn.addEventListener("click", handleLogout);
});