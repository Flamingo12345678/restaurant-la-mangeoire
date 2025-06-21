/**
 * Ce script permet d'harmoniser les classes CSS dans les pages d'authentification
 * afin de maintenir une cohérence visuelle dans tout le site.
 */

document.addEventListener('DOMContentLoaded', function() {
  // Harmoniser les paragraphes d'information
  document.querySelectorAll('p[style*="margin: 5px 0 0 0; font-size: 0.9em"]').forEach(el => {
    el.classList.add('info-paragraph');
    el.removeAttribute('style');
  });

  // Harmoniser les liens d'authentification
  document.querySelectorAll('a[style*="color: #ce1212; text-decoration: none"]').forEach(el => {
    el.classList.add('auth-link');
    el.removeAttribute('style');
  });

  // Harmoniser les conteneurs de liens
  document.querySelectorAll('div[style*="margin-top: 20px; text-align: center"]').forEach(el => {
    el.classList.add('auth-links-container');
    el.removeAttribute('style');
  });

  // Harmoniser les conteneurs d'erreurs
  document.querySelectorAll('div[style*="background-color: #fee; padding: 10px; margin: 10px; border: 1px solid #f00"]').forEach(el => {
    el.classList.add('error-container');
    el.removeAttribute('style');
  });

  // Harmoniser les lignes de formulaires en flex
  document.querySelectorAll('div[style*="display: flex; gap: 15px"]').forEach(el => {
    el.classList.add('form-row-flex');
    el.removeAttribute('style');
  });

  // Harmoniser les groupes de formulaires en flex
  document.querySelectorAll('div[style*="flex: 1"]').forEach(el => {
    el.classList.add('form-group-flex');
    el.removeAttribute('style');
  });

  // Harmoniser les titres de section
  document.querySelectorAll('h3[style*="margin-top: 30px"]').forEach(el => {
    el.classList.add('section-title');
    el.removeAttribute('style');
  });

  // Harmoniser les conteneurs de filtres
  document.querySelectorAll('div[style*="margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px"]').forEach(el => {
    el.classList.add('filters-container');
    el.removeAttribute('style');
  });

  // Harmoniser les titres de filtres
  document.querySelectorAll('h3[style*="margin-top: 0; font-size: 16px; margin-bottom: 10px"]').forEach(el => {
    el.classList.add('filter-title');
    el.removeAttribute('style');
  });

  // Harmoniser les formulaires de filtres
  document.querySelectorAll('form[style*="display: flex; flex-wrap: wrap; gap: 10px"]').forEach(el => {
    el.classList.add('filter-form');
    el.removeAttribute('style');
  });
  
  // Harmoniser les sélecteurs de formulaires
  document.querySelectorAll('select[style*="padding: 5px; border-radius: 3px; border: 1px solid #ddd"]').forEach(el => {
    el.classList.add('form-select');
    el.removeAttribute('style');
  });
  
  // Harmoniser les boutons de filtres
  document.querySelectorAll('button[style*="padding: 5px 10px; margin-top: 0"]').forEach(el => {
    el.classList.add('filter-button');
    el.removeAttribute('style');
  });
  
  // Harmoniser les boutons désactivés
  document.querySelectorAll('span[style*="opacity: 0.5; cursor: not-allowed"]').forEach(el => {
    el.classList.add('btn-disabled');
    el.removeAttribute('style');
  });
});
