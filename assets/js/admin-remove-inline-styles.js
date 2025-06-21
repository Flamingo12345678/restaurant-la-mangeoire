// Script pour remplacer les styles inline dans les pages d'administration
document.addEventListener('DOMContentLoaded', function() {
  // Ajouter la classe admin-body au body si elle n'existe pas déjà
  document.body.classList.add('admin-body');
  
  // Remplacer les div d'erreur avec style="display:none;"
  document.querySelectorAll('[id="form-error"][style*="display:none"]').forEach(el => {
    el.classList.add('admin-form-error');
    el.removeAttribute('style');
  });
  
  // Remplacer les boutons avec style="background:#b01e28;color:#fff;"
  document.querySelectorAll('button[style*="background:#b01e28"]').forEach(el => {
    el.classList.add('admin-delete-btn');
    el.removeAttribute('style');
  });
  
  // Remplacer les conteneurs de section avec style="background-color: #f9f9f9; border-radius: 5px;"
  document.querySelectorAll('div[style*="background-color: #f9f9f9"]').forEach(el => {
    el.classList.add('admin-section-container');
    el.removeAttribute('style');
  });
  
  // Remplacer les titres avec style="color: #222; font-size: 23px; margin-bottom: 30px; position: relative;"
  document.querySelectorAll('h2[style*="color: #222"]').forEach(el => {
    el.classList.add('admin-section-title');
    el.removeAttribute('style');
  });
  
  // Remplacer les groupes de formulaires avec style="grid-column: 1 / -1;"
  document.querySelectorAll('.form-group[style*="grid-column: 1 / -1"]').forEach(el => {
    el.classList.add('admin-form-group-full');
    el.removeAttribute('style');
  });
  
  // Remplacer les titres de sous-section avec style="margin-top: 30px;"
  document.querySelectorAll('.section-title[style*="margin-top: 30px"]').forEach(el => {
    el.classList.add('admin-subsection-title');
    el.removeAttribute('style');
  });
  
  // Remplacer les formulaires cachés avec style="display: none; margin-bottom: 30px;"
  document.querySelectorAll('#addEmployeForm[style*="display: none"]').forEach(el => {
    el.classList.add('admin-hidden-form');
    el.removeAttribute('style');
  });
  
  // Remplacer les conteneurs de formulaires avec style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);"
  document.querySelectorAll('div[style*="background-color: #fff; padding: 20px;"]').forEach(el => {
    el.classList.add('admin-form-container');
    el.removeAttribute('style');
  });
  
  // Remplacer les titres de formulaires avec style="margin-top: 0; margin-bottom: 20px; font-size: 18px;"
  document.querySelectorAll('h3[style*="margin-top: 0;"]').forEach(el => {
    el.classList.add('admin-form-title');
    el.removeAttribute('style');
  });
  
  // Remplacer les grilles de formulaires avec style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;"
  document.querySelectorAll('.form-grid[style*="display: grid;"]').forEach(el => {
    el.classList.add('admin-form-grid');
    el.removeAttribute('style');
  });
  
  // Remplacer les astérisques obligatoires avec style="color: red;"
  document.querySelectorAll('span[style*="color: red;"]').forEach(el => {
    el.classList.add('admin-required');
    el.removeAttribute('style');
  });
  
  // Remplacer les groupes de formulaires en ligne avec style="display: flex; align-items: center;"
  document.querySelectorAll('.form-group[style*="display: flex;"]').forEach(el => {
    el.classList.add('admin-form-group-inline');
    el.removeAttribute('style');
  });
});
