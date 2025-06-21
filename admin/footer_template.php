<?php

require_once 'check_admin_access.php';
// Ce fichier contient le footer commun pour toutes les pages admin
// Il doit être inclus à la fin de chaque page avant la fermeture des balises </body>

// Protection contre l'inclusion directe
if (!defined('INCLUDED_IN_PAGE')) {
  die('Ce fichier ne peut pas être appelé directement.');
}
?>
</div><!-- Fermeture de .admin-main-content -->

<!-- Scripts pour les fonctionnalités d'administration -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin-animations.js"></script>
<script src="../assets/js/admin-unified.js"></script>
<script src="../assets/js/admin-remove-inline-styles.js"></script>
<script src="../assets/js/admin-modals.js"></script>
<script src="../assets/js/harmonize-admin-styles.js"></script>