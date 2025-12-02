console.log("logout.js chargé");

document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById('logout-btn');
  if (!logoutBtn) return; // pas de bouton => rien à faire

  const handleLogout = () => {
    fetch('/deconnexion.php', {
      method: 'POST',
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const actionsDiv = document.querySelector('.site-nav .actions');

        // Reconstruire la nav pour utilisateur non connecté
        actionsDiv.innerHTML = `
          <button onclick="window.location.href='index.php'">Accueil</button>
          <button onclick="window.location.href='connexion.php'">Connexion</button>
          <button onclick="window.location.href='inscription.php'">Créer un compte</button>
        `;
      }
    })
    .catch(err => console.error('Erreur lors de la déconnexion:', err));
  };

  logoutBtn.addEventListener('click', handleLogout);
});