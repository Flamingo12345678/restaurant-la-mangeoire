// Remplacer les styles inline par des classes CSS
document.addEventListener('DOMContentLoaded', function() {
  // Remplacer les div avec style="max-width: 100px;" par class="input-group-limited"
  const inputGroups = document.querySelectorAll('.input-group[style*="max-width: 100px"]');
  inputGroups.forEach(el => {
    el.classList.add('input-group-limited');
    el.removeAttribute('style');
  });

  // Remplacer les divs avec style="display:none;" par class="hidden"
  const hiddenElements = document.querySelectorAll('[style*="display:none"]');
  hiddenElements.forEach(el => {
    el.classList.add('hidden');
    el.removeAttribute('style');
  });
});
