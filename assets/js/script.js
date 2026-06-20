document.addEventListener('DOMContentLoaded', function () {
  const videoInput = document.querySelector('input[name="video"]');
  const fileName = document.querySelector('[data-video-name]');
  if (videoInput && fileName) {
    videoInput.addEventListener('change', function () {
      fileName.textContent = this.files && this.files[0] ? this.files[0].name : 'Aucun fichier sélectionné';
    });
  }

  document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', async function () {
      const target = document.querySelector(button.dataset.copy);
      if (!target) return;
      try {
        await navigator.clipboard.writeText(target.innerText || target.value || '');
        const old = button.textContent;
        button.textContent = 'Copié !';
        setTimeout(() => button.textContent = old, 1200);
      } catch (e) {
        alert('Copie impossible. Sélectionne le texte manuellement.');
      }
    });
  });
});
