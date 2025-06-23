<?php
// Ce fichier est inclus dans paiements.php pour gérer les filtres

// Fonction pour appliquer les filtres aux paiements
function applyPaymentFilters($pdo, &$page, &$per_page, &$total_pages, &$paiements) {
    // Pagination
    $per_page = 15;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $per_page;

    // Filtres
    $where_clauses = [];
    $params = [];

    // Filtre par statut de réservation
    if (isset($_GET['filter_status']) && $_GET['filter_status'] !== 'all') {
        switch ($_GET['filter_status']) {
            case 'valid':
                $where_clauses[] = "r.ReservationID IS NOT NULL";
                break;
            case 'invalid':
                $where_clauses[] = "p.ReservationID IS NOT NULL AND r.ReservationID IS NULL";
                break;
            case 'no_res':
                $where_clauses[] = "p.ReservationID IS NULL OR p.ReservationID = ''";
                break;
        }
    }

    // Filtre par date
    if (isset($_GET['filter_date']) && $_GET['filter_date'] !== 'all') {
        $today = date('Y-m-d');
        switch ($_GET['filter_date']) {
            case 'today':
                $where_clauses[] = "p.DatePaiement = :today";
                $params[':today'] = $today;
                break;
            case 'week':
                $week_start = date('Y-m-d', strtotime('monday this week'));
                $week_end = date('Y-m-d', strtotime('sunday this week'));
                $where_clauses[] = "p.DatePaiement BETWEEN :week_start AND :week_end";
                $params[':week_start'] = $week_start;
                $params[':week_end'] = $week_end;
                break;
            case 'month':
                $month_start = date('Y-m-01');
                $month_end = date('Y-m-t');
                $where_clauses[] = "p.DatePaiement BETWEEN :month_start AND :month_end";
                $params[':month_start'] = $month_start;
                $params[':month_end'] = $month_end;
                break;
        }
    }

    // Construction de la clause WHERE
    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // Requête pour le total de paiements avec filtres
    $total_sql = "SELECT COUNT(*) FROM Paiements p LEFT JOIN Reservations r ON p.ReservationID = r.ReservationID" . $where_sql;
    $total_stmt = $pdo->prepare($total_sql);
    foreach ($params as $key => $value) {
        $total_stmt->bindValue($key, $value);
    }
    $total_stmt->execute();
    $total_paiements = $total_stmt->fetchColumn();
    $total_pages = ceil($total_paiements / $per_page);

    // Modified query to join with Commandes and Reservations to get more information
    $sql = "SELECT p.*, 
            c.NomClient, c.PrenomClient, c.CommandeID,
            r.nom_client, r.email_client, r.telephone, r.ReservationID as ResID
            FROM Paiements p
            LEFT JOIN Reservations r ON p.ReservationID = r.ReservationID
            LEFT JOIN Commandes c ON (r.ReservationID = c.ReservationID OR p.ReservationID = c.ReservationID)
            " . $where_sql . "
            ORDER BY p.DatePaiement DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $where_sql;
}
?>
