console.log("logout.js bien chargé");

document.getElementById('logout-btn').addEventListener('click', () => {
  fetch('deconnexion.php', {
    method: 'POST',
    credentials: 'same-origin' // pour envoyer les cookies de session
  })
  .then(response => {
    if (response.ok) {
      // On change le bouton en "Connexion"
      const btn = document.getElementById('logout-btn');
      btn.textContent = 'Connexion';
      btn.removeEventListener('click', arguments.callee);
      btn.addEventListener('click', () => window.location.href='connexion.php');
    }
  })
  .catch(err => console.error('Erreur lors de la déconnexion:', err));
});
