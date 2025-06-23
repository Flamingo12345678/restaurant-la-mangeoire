// Gestion de la sidebar d'administration

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation admin-sidebar.js');
    
    const burgerBtn = document.getElementById('admin-burger-btn');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');
    
    // Debug des éléments trouvés
    console.log('🔍 Éléments détectés:', {
        burgerBtn: !!burgerBtn,
        sidebar: !!sidebar,
        overlay: !!overlay
    });
    
    if (!burgerBtn) {
        console.error('❌ Bouton burger non trouvé');
        return;
    }
    
    if (!sidebar) {
        console.error('❌ Sidebar non trouvée');
        return;
    }
    
    if (!overlay) {
        console.error('❌ Overlay non trouvé');
        return;
    }
    
    // Fonction pour ouvrir/fermer la sidebar
    function toggleSidebar(event) {
        event.preventDefault();
        console.log('🔄 Toggle sidebar');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        
        const isOpen = sidebar.classList.contains('open');
        console.log(`📱 Sidebar ${isOpen ? 'ouverte' : 'fermée'}`);
        
        // Changer l'icône du bouton
        const icon = burgerBtn.querySelector('i');
        if (icon) {
            icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
        }
    }
    
    // Fonction pour fermer la sidebar
    function closeSidebar() {
        console.log('❌ Fermeture sidebar');
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        
        // Remettre l'icône burger
        const icon = burgerBtn.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-list';
        }
    }
    
    // Event listeners
    burgerBtn.addEventListener('click', toggleSidebar);
    console.log('✅ Event listener ajouté au bouton burger');
    
    overlay.addEventListener('click', closeSidebar);
    console.log('✅ Event listener ajouté à l\'overlay');
    
    // Fermer la sidebar avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992 && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    console.log('✅ Admin sidebar initialisée avec succès');
});