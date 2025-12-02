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
        const accueilBtn = document.createElement('button');
        accueilBtn.textContent = 'Accueil';
        accueilBtn.onclick = () => window.location.href='index.php';
        actionsDiv.appendChild(accueilBtn);

        const loginBtn = document.createElement('button');
        loginBtn.textContent = 'Connexion';
        loginBtn.onclick = () => window.location.href='connexion.php';
        actionsDiv.appendChild(loginBtn);

        const signupBtn = document.createElement('button');
        signupBtn.textContent = 'Créer un compte';
        signupBtn.onclick = () => window.location.href='inscription.php';
        actionsDiv.appendChild(signupBtn);
      }
    })
    .catch(err => console.error('Erreur lors de la déconnexion:', err));
  };

  logoutBtn.addEventListener('click', handleLogout);
});