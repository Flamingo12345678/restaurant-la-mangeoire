<?php
/**
 * VÉRIFICATION COMPLÈTE DE L'INTERFACE ADMIN - Restaurant La Mangeoire
 * Analyse technique et diagnostic complet du système d'administration
 */

require_once 'db_connexion.php';

echo "=== VÉRIFICATION COMPLÈTE DE L'INTERFACE ADMIN ===" . PHP_EOL . PHP_EOL;

// Fonction pour vérifier l'existence et la lisibilité des fichiers
function verifyFile($path, $description) {
    $absolutePath = realpath($path);
    if (file_exists($path)) {
        $size = filesize($path);
        $readable = is_readable($path);
        echo "✅ {$description}: OK (" . number_format($size/1024, 1) . " KB)" . ($readable ? "" : " - ⚠️ Non lisible") . PHP_EOL;
        return true;
    } else {
        echo "❌ {$description}: MANQUANT - {$path}" . PHP_EOL;
        return false;
    }
}

// 1. VÉRIFICATION DES FICHIERS CORE ADMIN
echo "1️⃣ FICHIERS CORE ADMINISTRATION:" . PHP_EOL;
$coreFiles = [
    ['admin/index.php', 'Dashboard principal admin'],
    ['admin/header_template.php', 'Template header admin'],
    ['admin/footer_template.php', 'Template footer admin'],
    ['admin/check_admin_access.php', 'Contrôle d\'accès admin'],
    ['admin/login.php', 'Page de connexion admin'],
    ['admin/logout.php', 'Déconnexion admin'],
    ['dashboard-admin.php', 'Dashboard système']
];

$coreOK = 0;
foreach ($coreFiles as [$file, $desc]) {
    if (verifyFile($file, $desc)) $coreOK++;
}
echo "   📊 Fichiers core: {$coreOK}/" . count($coreFiles) . PHP_EOL . PHP_EOL;

// 2. VÉRIFICATION DES MODULES FONCTIONNELS
echo "2️⃣ MODULES FONCTIONNELS:" . PHP_EOL;
$moduleFiles = [
    ['admin/clients.php', 'Gestion clients'],
    ['admin/commandes.php', 'Gestion commandes'],
    ['admin/menus.php', 'Gestion menus'],
    ['admin/reservations.php', 'Gestion réservations'],
    ['admin/tables.php', 'Gestion tables'],
    ['admin/employes.php', 'Gestion employés'],
    ['admin/paiements.php', 'Gestion paiements'],
    ['admin/administrateurs.php', 'Gestion administrateurs']
];

$moduleOK = 0;
foreach ($moduleFiles as [$file, $desc]) {
    if (verifyFile($file, $desc)) $moduleOK++;
}
echo "   📊 Modules: {$moduleOK}/" . count($moduleFiles) . PHP_EOL . PHP_EOL;

// 3. VÉRIFICATION DES ASSETS CSS/JS
echo "3️⃣ ASSETS INTERFACE:" . PHP_EOL;
$assetFiles = [
    ['assets/css/admin.css', 'CSS principal admin'],
    ['assets/css/admin-sidebar.css', 'CSS sidebar admin'],
    ['assets/css/admin-animations.css', 'CSS animations admin'],
    ['assets/css/admin-inline-fixes.css', 'CSS correctifs admin'],
    ['assets/js/admin-sidebar.js', 'JS sidebar admin'],
    ['assets/js/admin-modals.js', 'JS modales admin']
];

$assetOK = 0;
foreach ($assetFiles as [$file, $desc]) {
    if (verifyFile($file, $desc)) $assetOK++;
}
echo "   📊 Assets: {$assetOK}/" . count($assetFiles) . PHP_EOL . PHP_EOL;

// 4. VÉRIFICATION DE LA BASE DE DONNÉES
echo "4️⃣ STRUCTURE BASE DE DONNÉES:" . PHP_EOL;
try {
    $tables = [
        'Clients' => 'Table des clients',
        'Commandes' => 'Table des commandes', 
        'Menus' => 'Table des menus',
        'Reservations' => 'Table des réservations',
        'Tables' => 'Table des tables restaurant',
        'Employes' => 'Table des employés',
        'Paiements' => 'Table des paiements',
        'Administrateurs' => 'Table des administrateurs'
    ];
    
    $tablesOK = 0;
    foreach ($tables as $table => $desc) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Compter les enregistrements
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                echo "✅ {$desc}: OK ({$count} enregistrements)" . PHP_EOL;
                $tablesOK++;
            } else {
                echo "❌ {$desc}: TABLE MANQUANTE" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo "⚠️ {$desc}: ERREUR - " . $e->getMessage() . PHP_EOL;
        }
    }
    echo "   📊 Tables: {$tablesOK}/" . count($tables) . PHP_EOL . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion base de données: " . $e->getMessage() . PHP_EOL . PHP_EOL;
}

// 5. VÉRIFICATION DES PERMISSIONS ET SÉCURITÉ
echo "5️⃣ SÉCURITÉ ET PERMISSIONS:" . PHP_EOL;

// Vérifier les fichiers sensibles
$securityFiles = [
    ['admin/check_admin_access.php', 'Contrôle d\'accès'],
    ['includes/common.php', 'Fonctions de sécurité'],
    ['db_connexion.php', 'Connexion DB sécurisée']
];

$securityOK = 0;
foreach ($securityFiles as [$file, $desc]) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasCSRF = strpos($content, 'csrf') !== false;
        $hasValidation = strpos($content, 'validate') !== false || strpos($content, 'filter') !== false;
        
        echo "✅ {$desc}: OK";
        if ($hasCSRF) echo " [CSRF Protection]";
        if ($hasValidation) echo " [Validation]";
        echo PHP_EOL;
        $securityOK++;
    } else {
        echo "❌ {$desc}: MANQUANT" . PHP_EOL;
    }
}
echo "   📊 Sécurité: {$securityOK}/" . count($securityFiles) . PHP_EOL . PHP_EOL;

// 6. VÉRIFICATION DE LA NAVIGATION ET LIENS
echo "6️⃣ NAVIGATION ET LIENS:" . PHP_EOL;

// Analyser le header template pour les liens de navigation
if (file_exists('admin/header_template.php')) {
    $headerContent = file_get_contents('admin/header_template.php');
    $links = [];
    preg_match_all('/href="([^"]+)"/', $headerContent, $matches);
    
    foreach ($matches[1] as $link) {
        if (strpos($link, '.php') !== false && strpos($link, 'http') !== 0) {
            $links[] = $link;
        }
    }
    
    echo "🔗 Liens détectés dans la navigation:" . PHP_EOL;
    $linksOK = 0;
    foreach (array_unique($links) as $link) {
        $filePath = str_replace('../', '', $link);
        if (file_exists($filePath) || file_exists('admin/' . $link)) {
            echo "  ✅ {$link}" . PHP_EOL;
            $linksOK++;
        } else {
            echo "  ❌ {$link} - FICHIER MANQUANT" . PHP_EOL;
        }
    }
    echo "   📊 Liens valides: {$linksOK}/" . count(array_unique($links)) . PHP_EOL . PHP_EOL;
}

// 7. ANALYSE DES ERREURS COMMUNES
echo "7️⃣ ANALYSE DES ERREURS COMMUNES:" . PHP_EOL;

$errorPatterns = [
    'Chemins relatifs incorrects' => '../',
    'Classes CSS manquantes' => 'alert-error',
    'Scripts non inclus' => 'bootstrap.bundle.min.js',
    'Icons Bootstrap' => 'bi bi-'
];

$issuesFound = [];
foreach (glob('admin/*.php') as $file) {
    $content = file_get_contents($file);
    
    // Vérifier les chemins relatifs
    if (preg_match_all('/\.\.\/((?!\.\.).)*\.(?:css|js|php)/', $content, $matches)) {
        foreach ($matches[0] as $path) {
            $cleanPath = str_replace('../', '', $path);
            if (!file_exists($cleanPath)) {
                $issuesFound[] = basename($file) . ": Chemin invalide {$path}";
            }
        }
    }
    
    // Vérifier les classes CSS d'erreur personnalisées
    if (strpos($content, 'alert-error') !== false) {
        $issuesFound[] = basename($file) . ": Utilise 'alert-error' (non-Bootstrap standard)";
    }
}

if (empty($issuesFound)) {
    echo "✅ Aucun problème majeur détecté" . PHP_EOL;
} else {
    echo "⚠️ Problèmes détectés:" . PHP_EOL;
    foreach (array_slice($issuesFound, 0, 10) as $issue) {
        echo "  • {$issue}" . PHP_EOL;
    }
    if (count($issuesFound) > 10) {
        echo "  ... et " . (count($issuesFound) - 10) . " autres problèmes" . PHP_EOL;
    }
}
echo PHP_EOL;

// 8. RECOMMANDATIONS D'AMÉLIORATION
echo "8️⃣ RECOMMANDATIONS D'AMÉLIORATION:" . PHP_EOL;

$recommendations = [
    "🎨 Harmoniser les classes CSS (remplacer 'alert-error' par 'alert-danger')",
    "🔒 Ajouter validation CSRF sur tous les formulaires",
    "📱 Optimiser la responsivité mobile des tableaux",
    "🚀 Implémenter la pagination AJAX pour de meilleures performances",
    "🔍 Ajouter des filtres de recherche dans les listes",
    "📊 Créer des graphiques interactifs pour les statistiques",
    "💾 Implémenter un système de cache pour les requêtes fréquentes",
    "🔔 Ajouter un système de notifications en temps réel"
];

foreach ($recommendations as $rec) {
    echo "  {$rec}" . PHP_EOL;
}

echo PHP_EOL . "🎯 RÉSUMÉ FINAL:" . PHP_EOL;
echo "• Fichiers core: {$coreOK}/" . count($coreFiles) . PHP_EOL;
echo "• Modules: {$moduleOK}/" . count($moduleFiles) . PHP_EOL;
echo "• Assets: {$assetOK}/" . count($assetFiles) . PHP_EOL;
echo "• Sécurité: {$securityOK}/" . count($securityFiles) . PHP_EOL;

$totalScore = $coreOK + $moduleOK + $assetOK + $securityOK;
$maxScore = count($coreFiles) + count($moduleFiles) + count($assetFiles) + count($securityFiles);
$percentage = round(($totalScore / $maxScore) * 100);

echo PHP_EOL . "📈 SCORE GLOBAL: {$percentage}% ({$totalScore}/{$maxScore})" . PHP_EOL;

if ($percentage >= 90) {
    echo "🏆 EXCELLENT - Interface admin très bien structurée!" . PHP_EOL;
} elseif ($percentage >= 75) {
    echo "✅ BIEN - Interface admin fonctionnelle avec quelques améliorations possibles" . PHP_EOL;
} elseif ($percentage >= 60) {
    echo "⚠️ MOYEN - Interface admin nécessite des corrections importantes" . PHP_EOL;
} else {
    echo "❌ CRITIQUE - Interface admin nécessite une refonte majeure" . PHP_EOL;
}

echo PHP_EOL . "=== VÉRIFICATION TERMINÉE ===" . PHP_EOL;
?>
