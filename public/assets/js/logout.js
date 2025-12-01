console.log("logout.js bien chargé");

document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById('logout-btn');
  if (!logoutBtn) return; // empêche l'erreur si le bouton n'existe pas

  const handleLogout = () => {
    fetch('/deconnexion.php', {
      method: 'POST',
      credentials: 'same-origin' // pour envoyer les cookies de session
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // On change le bouton en "Connexion"
        logoutBtn.textContent = 'Connexion';
        // Retire l'ancien listener et ajoute un nouveau pour rediriger
        logoutBtn.removeEventListener('click', handleLogout);
        logoutBtn.addEventListener('click', () => window.location.href='connexion.php');
      }
    })
    .catch(err => console.error('Erreur lors de la déconnexion:', err));
  };

  logoutBtn.addEventListener('click', handleLogout);
});