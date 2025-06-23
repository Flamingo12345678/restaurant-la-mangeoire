<?php
/**
 * Script de Test Complet du Système de Réservation
 * Ce script vérifie que tous les composants fonctionnent correctement
 */

echo "=== TEST COMPLET DU SYSTÈME DE RÉSERVATION ===\n\n";

// Test 1: Vérification de la configuration .env
echo "1. Test Configuration .env\n";
echo "----------------------------\n";

if (!file_exists('.env')) {
    echo "❌ ERREUR: Fichier .env manquant\n";
    echo "   → Créez le fichier .env en copiant .env.example\n\n";
} else {
    echo "✅ Fichier .env présent\n";
    
    // Charger les variables d'environnement
    $env_lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env_vars = [];
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $env_vars[trim($key)] = trim($value, '"\'');
        }
    }
    
    $required_vars = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'FROM_EMAIL'];
    $missing_vars = [];
    
    foreach ($required_vars as $var) {
        if (!isset($env_vars[$var]) || empty($env_vars[$var])) {
            $missing_vars[] = $var;
        }
    }
    
    if (!empty($missing_vars)) {
        echo "❌ Variables manquantes: " . implode(', ', $missing_vars) . "\n";
    } else {
        echo "✅ Toutes les variables SMTP sont configurées\n";
    }
    echo "\n";
}

// Test 2: Vérification des fichiers système
echo "2. Test Présence des Fichiers Système\n";
echo "--------------------------------------\n";

$required_files = [
    'config/email_config.php' => 'Configuration email',
    'includes/email_notifications.php' => 'Système de notifications',
    'includes/common.php' => 'Fonctions communes',
    'admin/check_admin_access.php' => 'Contrôle accès admin',
    'admin/includes/security_utils.php' => 'Utilitaires sécurité',
    'reserver-table.php' => 'Formulaire de réservation',
    'admin-reservations.php' => 'Interface admin réservations'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)\n";
    } else {
        echo "❌ MANQUANT: $description ($file)\n";
    }
}
echo "\n";

// Test 3: Vérification de la base de données
echo "3. Test Connexion Base de Données\n";
echo "----------------------------------\n";

try {
    require_once 'db_connexion.php';
    echo "✅ Connexion à la base de données réussie\n";
    
    // Vérifier la table reservations
    $stmt = $pdo->query("SHOW TABLES LIKE 'reservations'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'reservations' existe\n";
        
        // Vérifier la structure de la table
        $stmt = $pdo->query("DESCRIBE reservations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'nom', 'email', 'telephone', 'date_reservation', 'heure_reservation', 'nombre_personnes', 'message', 'statut', 'date_creation'];
        
        $missing_columns = array_diff($required_columns, $columns);
        if (empty($missing_columns)) {
            echo "✅ Structure de la table 'reservations' correcte\n";
        } else {
            echo "❌ Colonnes manquantes dans 'reservations': " . implode(', ', $missing_columns) . "\n";
        }
    } else {
        echo "❌ Table 'reservations' manquante\n";
        echo "   → Exécutez: php create_reservations_table.php\n";
    }
} catch (Exception $e) {
    echo "❌ ERREUR de connexion DB: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test de configuration email
echo "4. Test Configuration Email\n";
echo "----------------------------\n";

if (file_exists('config/email_config.php')) {
    try {
        require_once 'config/email_config.php';
        
        if (isset($email_config) && is_array($email_config)) {
            if (!empty($email_config['smtp_host']) && !empty($email_config['smtp_username'])) {
                echo "✅ Configuration email chargée depuis .env\n";
                echo "   SMTP Host: " . $email_config['smtp_host'] . "\n";
                echo "   SMTP Port: " . $email_config['smtp_port'] . "\n";
                echo "   De: " . $email_config['from_email'] . "\n";
            } else {
                echo "❌ Configuration email incomplète\n";
            }
        } else {
            echo "❌ Variable \$email_config non trouvée\n";
        }
    } catch (Exception $e) {
        echo "❌ ERREUR lors du chargement de la config email: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Fichier config/email_config.php manquant\n";
}
echo "\n";

// Test 5: Vérification des permissions
echo "5. Test Permissions Fichiers\n";
echo "-----------------------------\n";

if (file_exists('.env')) {
    $perms = fileperms('.env');
    $octal_perms = substr(sprintf('%o', $perms), -4);
    
    if ($octal_perms === '0600' || $octal_perms === '0644') {
        echo "✅ Permissions .env appropriées ($octal_perms)\n";
    } else {
        echo "⚠️  Permissions .env: $octal_perms (recommandé: 0600)\n";
        echo "   → Exécutez: chmod 600 .env\n";
    }
}

if (!is_dir('logs')) {
    echo "❌ Dossier 'logs' manquant - création...\n";
    mkdir('logs', 0755, true);
    echo "✅ Dossier 'logs' créé\n";
} else {
    echo "✅ Dossier 'logs' présent\n";
}

if (!is_writable('logs')) {
    echo "❌ Dossier 'logs' non accessible en écriture\n";
    echo "   → Exécutez: chmod 755 logs\n";
} else {
    echo "✅ Dossier 'logs' accessible en écriture\n";
}
echo "\n";

// Test 6: Syntaxe PHP des fichiers critiques
echo "6. Test Syntaxe PHP\n";
echo "-------------------\n";

$php_files = [
    'reserver-table.php',
    'admin-reservations.php',
    'config/email_config.php',
    'includes/email_notifications.php'
];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_var = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✅ $file - syntaxe correcte\n";
        } else {
            echo "❌ $file - erreur de syntaxe:\n";
            echo "   " . implode("\n   ", $output) . "\n";
        }
    }
}
echo "\n";

// Résumé final
echo "=== RÉSUMÉ DU TEST ===\n";
echo "Système de réservation testé le " . date('d/m/Y à H:i:s') . "\n\n";

echo "Actions recommandées:\n";
echo "1. Si des erreurs sont présentes, corrigez-les avant de continuer\n";
echo "2. Testez manuellement le formulaire de réservation\n";
echo "3. Testez l'interface admin des réservations\n";
echo "4. Vérifiez l'envoi d'emails avec test-email-config.php\n\n";

echo "Documentation complète: GUIDE_COMPLET_RESERVATIONS.md\n";
echo "===============================================\n";
?>
