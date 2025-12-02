console.log("logout.js chargé");

document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById('logout-btn');
  if (!logoutBtn) return;

  const handleLogout = () => {
    fetch('/deconnexion.php', {
      method: 'POST',
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const actionsDiv = document.getElementById('nav-actions');

        // Supprime tous les boutons utilisateur existants
        actionsDiv.querySelectorAll('.user-btn').forEach(btn => btn.remove());

        // Reconstruit la nav pour utilisateur non connecté
        const buttons = [
          { text: 'Accueil', href: 'index.php' },
          { text: 'Connexion', href: 'connexion.php' },
          { text: 'Créer un compte', href: 'inscription.php' },
        ];

        buttons.forEach(b => {
          const btn = document.createElement('button');
          btn.className = 'user-btn';
          btn.textContent = b.text;
          btn.onclick = () => window.location.href = b.href;
          actionsDiv.appendChild(btn);
        });
      }
    })
    .catch(err => console.error('Erreur lors de la déconnexion:', err));
  };

  logoutBtn.addEventListener('click', handleLogout);
});