/**
 * Ce script permet d'harmoniser les classes CSS dans les pages d'administrateurs et d'employés
 * afin de maintenir une cohérence visuelle avec le reste du site.
 */

document.addEventListener('DOMContentLoaded', function() {
  // Harmoniser les boutons
  document.querySelectorAll('.btn-primary, .btn-success').forEach(btn => {
    btn.classList.add('add-button');
  });

  document.querySelectorAll('.btn-secondary, .btn-cancel').forEach(btn => {
    btn.classList.add('cancel-button');
  });

  // Harmoniser les tableaux
  document.querySelectorAll('table').forEach(table => {
    if (!table.classList.contains('admin-table') && !table.classList.contains('data-table')) {
      table.classList.add('admin-table');
    }
  });

  // Harmoniser les conteneurs de formulaires
  document.querySelectorAll('.modal-content form, .form-container form').forEach(form => {
    form.classList.add('admin-form');
  });

  // Harmoniser les groupes de formulaires
  document.querySelectorAll('.form-row').forEach(row => {
    row.classList.add('form-grid');
  });

  // Appliquer un style cohérent aux titres
  document.querySelectorAll('h2.section-title').forEach(title => {
    title.classList.add('admin-section-title');
  });

  // Harmoniser les boutons de suppression et d'édition
  document.querySelectorAll('.btn-delete, button[title="Supprimer"]:not(.admin-action-btn)').forEach(btn => {
    btn.classList.add('delete-btn');
  });

  document.querySelectorAll('.btn-edit, a[title="Modifier"]:not(.admin-action-btn)').forEach(link => {
    link.classList.add('edit-btn');
  });

  // Améliorer la pagination
  document.querySelectorAll('.pagination a').forEach(link => {
    link.classList.add('page-link');
  });

  document.querySelectorAll('.pagination strong').forEach(current => {
    let wrapper = document.createElement('span');
    wrapper.className = 'current-page';
    wrapper.innerText = current.innerText;
    current.parentNode.replaceChild(wrapper, current);
  });
  
  // ***** NOUVELLES FONCTIONNALITÉS *****
  
  // Remplacer les styles inline pour les grilles de formulaires
  document.querySelectorAll('div[style*="display: grid; grid-template-columns"]').forEach(el => {
    el.classList.add('form-grid');
    el.removeAttribute('style');
  });
  
  // Remplacer les astérisques rouges pour les champs obligatoires
  document.querySelectorAll('span[style*="color: red"]').forEach(el => {
    el.classList.add('required-field');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les groupes de cases à cocher
  document.querySelectorAll('div[style*="display: flex; align-items: center"]').forEach(el => {
    el.classList.add('checkbox-group');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les étiquettes de cases à cocher
  document.querySelectorAll('label[style*="margin-left: 10px"]').forEach(el => {
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les conteneurs d'actions de formulaire
  document.querySelectorAll('div[style*="text-align: right; margin-top"]').forEach(el => {
    el.classList.add('form-actions');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les boutons d'annulation
  document.querySelectorAll('button[style*="background-color: #6c757d"]').forEach(el => {
    el.classList.add('cancel-button');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les boutons de soumission
  document.querySelectorAll('button[style*="background-color: #28a745"]').forEach(el => {
    el.classList.add('add-button');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les cellules d'action dans les tableaux
  document.querySelectorAll('th[style*="text-align: right; padding-right"]').forEach(el => {
    el.classList.add('actions-cell');
    el.removeAttribute('style');
  });
  
  // Remplacer les styles inline pour les formulaires de suppression en ligne
  document.querySelectorAll('form[style*="display: inline"]').forEach(el => {
    el.classList.add('inline-delete-form');
    el.removeAttribute('style');
  });
});
