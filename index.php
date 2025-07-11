<?php
session_start();
require_once 'includes/https-security.php'; // Sécurité HTTPS
require_once 'includes/common.php';
require_once 'db_connexion.php';
require_once 'includes/currency_manager.php';

// Gestion du changement de devise
if (isset($_GET['currency'])) {
    if (CurrencyManager::setCurrency($_GET['currency'])) {
        // Rediriger pour supprimer le paramètre de l'URL
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// Custom function to display cart messages using the session variable format we've set up
function display_cart_message() {
  // Gérer les messages du panier (format moderne)
  if (isset($_SESSION['cart_message'])) {
    $message = $_SESSION['cart_message'];
    $message_type = $message['type'] ?? 'info';
    $alert_class = ($message_type == 'error') ? 'alert-danger' : 'alert-success';
    
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-' . ($message_type == 'error' ? 'exclamation-triangle' : 'check-circle') . '"></i> ';
    echo htmlspecialchars($message['text']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    
    // Clear the message after displaying it
    unset($_SESSION['cart_message']);
  }
  
  // Gérer les anciens messages (format legacy)
  if (isset($_SESSION['message'])) {
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    $alert_class = ($message_type == 'error') ? 'alert-danger' : 'alert-success';
    
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-' . ($message_type == 'error' ? 'exclamation-triangle' : 'check-circle') . '"></i> ';
    echo htmlspecialchars($_SESSION['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    
    // Clear the message after displaying it
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
  }
}

// Récupérer les prix des menus depuis la base de données
$menu_prices = [];
try {
  $stmt = $pdo->prepare("SELECT MenuID, NomItem, Prix FROM Menus");
  $stmt->execute();
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  foreach ($menus as $menu) {
    $menu_prices[$menu['MenuID']] = [
      'nom' => $menu['NomItem'],
      'prix' => $menu['Prix'],
      'prix_formate' => CurrencyManager::formatPrice($menu['Prix'], true)
    ];
  }
} catch (PDOException $e) {
  error_log("Erreur récupération prix menus: " . $e->getMessage());
}

// Obtenir les informations de devise actuelle
$current_currency = CurrencyManager::getCurrentCurrency();
$user_country = CurrencyManager::detectCountry();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>La Mangeoire</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <!-- Icone de favoris -->
    <link href="assets/img/favcon.jpeg" rel="icon" />
    <link href="assets/img/apple-touch-ico.png" rel="apple-touch-icon" />
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Amatic+SC:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <!-- Style pour le système de gestion des cookies -->
    <link href="assets/css/cookie-consent.css" rel="stylesheet" />
    <!-- Vendor CSS Files -->
    <link
      href="assets/vendor/bootstrap/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="assets/vendor/bootstrap-icons/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link href="assets/vendor/aos/aos.css" rel="stylesheet" />
    <link
      href="assets/vendor/glightbox/css/glightbox.min.css"
      rel="stylesheet"
    />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />
    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet" />
  </head>
  <body class="index-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
      <div
        class="container position-relative d-flex align-items-center justify-content-between"
      >
        <a
          href="index.php"
          class="logo d-flex align-items-center me-auto me-xl-0"
        >
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <!-- <img src="assets/img/logo.png" alt=""> -->
          <h1 class="sitename">La Mangeoire</h1>
          <span>.</span>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li>
              <a href="#hero" class="active">Home<br /></a>
            </li>
            <li><a href="#about">A Propos</a></li>
            <li><a href="#menu">Menu</a></li>
            <li><a href="#events">Evenements</a></li>
            <li><a href="#chefs">Chefs</a></li>
            <li><a href="#gallery">Galeries</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="panier.php"><i class="bi bi-cart"></i> Panier 
              <?php 
              $cart_count = 0;
              if (isset($_SESSION['client_id'])) {
                // Count from database
                $stmt = $pdo->prepare("SELECT SUM(Quantite) FROM Panier WHERE ClientID = ?");
                $stmt->execute([$_SESSION['client_id']]);
                $cart_count = $stmt->fetchColumn() ?: 0;
              } else if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
                // Count from session
                foreach ($_SESSION['panier'] as $item) {
                  $cart_count += $item['Quantite'];
                }
              }
              if ($cart_count > 0) {
                echo '<span class="badge bg-danger rounded-pill">' . $cart_count . '</span>';
              }
              ?>
            </a></li>
            <li>
              <?php if (isset($_SESSION['client_id']) || isset($_SESSION['user_id'])): ?>
                <a href="mon-compte.php"><i class="bi bi-person"></i> Mon Compte</a>
              <?php else: ?>
                <a href="connexion-unifiee.php"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
              <?php endif; ?>
            </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-currency-exchange"></i> <?php echo $current_currency['symbol']; ?>
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?currency=CM"><span class="fi fi-cm"></span> FCFA (Cameroun)</a></li>
                <li><a class="dropdown-item" href="?currency=FR"><span class="fi fi-fr"></span> € (Euro)</a></li>
                <li><a class="dropdown-item" href="?currency=US"><span class="fi fi-us"></span> $ (USD)</a></li>
                <li><a class="dropdown-item" href="?currency=GB"><span class="fi fi-gb"></span> £ (GBP)</a></li>
                <li><a class="dropdown-item" href="?currency=CA"><span class="fi fi-ca"></span> C$ (CAD)</a></li>
                <li><a class="dropdown-item" href="?currency=CH"><span class="fi fi-ch"></span> CHF (Suisse)</a></li>
                <li><a class="dropdown-item" href="?currency=AU"><span class="fi fi-au"></span> A$ (AUD)</a></li>
              </ul>
            </li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="reserver-table.php"
          >Réserver une Table</a
        >
      </div>
    </header>
    
    <!-- Display success/error messages -->
    <div class="container mt-2">
      <?php display_cart_message(); ?>
    </div>

    <main class="main">
      <!-- Hero Section -->
      <section id="hero" class="hero section light-background">
        <div class="container">
          <div
            class="row gy-4 justify-content-center justify-content-lg-between"
          >
            <div
              class="col-lg-5 order-2 order-lg-1 d-flex flex-column justify-content-center"
            >
              <h1 data-aos="fade-up">
                Profitez de votre<br />nourriture délicieuse et saine
              </h1>
              <p data-aos="fade-up" data-aos-delay="100">
                Personne ne nous egalise dans la qualité de nos plats. Venez
              </p>
              <div class="d-flex" data-aos="fade-up" data-aos-delay="200">
                <a href="reserver-table.php" class="btn-get-started"
                  >Réserver une Table</a
                >
                <a
                  href="https://www.youtube.com/watch?v=Y7f98aduVJ8"
                  class="glightbox btn-watch-video d-flex align-items-center"
                  ><i class="bi bi-play-circle"></i
                  ><span>Regarder la Vidéo</span></a
                >
              </div>
            </div>
            <div
              class="col-lg-5 order-1 order-lg-2 hero-img"
              data-aos="zoom-out"
            >
              <img
                src="assets/img/hero-img.png"
                class="img-fluid animated"
                alt=""
              />
            </div>
          </div>
        </div>
      </section>
      <!-- /Hero Section -->

      <!-- About Section -->
      <section id="about" class="about section">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>A Propos De Nous<br /></h2>
          <p>
            <span>En savoir plus</span>
            <span class="description-title">À Propos De Nous</span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="row gy-4">
            <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
              <img src="assets/img/about.jpg" class="img-fluid mb-4" alt="" />
              <div class="book-a-table">
                <h3>Réserver une Table</h3>
                <p>+237 6 96 56 85 20</p>
                <!-- 
                BOUTON "RÉSERVER EN LIGNE" COMMENTÉ
                Ce bouton était redondant avec les autres options de réservation.
                Les utilisateurs peuvent utiliser le bouton principal "Réserver une Table"
                qui les dirige vers le formulaire détaillé unifié.
                
                <a
                  href="forms/book-a-table.php"
                  class="btn btn-primary btn-book-table"
                  >Réserver en ligne</a
                >
                -->
                <div class="mt-3 p-3 bg-light rounded">
                  <small class="text-primary">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Réservation :</strong> Utilisez le bouton "Réserver une Table" en haut de page
                  </small>
                </div>
              </div>
            </div>
            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="250">
              <div class="content ps-0 ps-lg-5">
                <p class="fst-italic">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
                  do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>
                <ul>
                  <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span
                      >Ullamco laboris nisi ut aliquip ex ea commodo
                      consequat.</span
                    >
                  </li>
                  <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span
                      >Duis aute irure dolor in reprehenderit in voluptate
                      velit.</span
                    >
                  </li>
                  <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span
                      >Ullamco laboris nisi ut aliquip ex ea commodo consequat.
                      Duis aute irure dolor in reprehenderit in voluptate
                      trideta storacalaperda mastiro dolore eu fugiat nulla
                      pariatur.</span
                    >
                  </li>
                </ul>
                <p>
                  Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis
                  aute irure dolor in reprehenderit in voluptate velit esse
                  cillum dolore eu fugiat nulla pariatur. Excepteur sint
                  occaecat cupidatat non proident
                </p>

                <div class="position-relative mt-4">
                  <!--cette div permet de mettre une image et un lien vers une video-->
                  <img src="assets/img/about-2.jpg" class="img-fluid" alt="" />
                  <!--image-->
                  <a
                    href="https://www.youtube.com/watch?v=Y7f98aduVJ8"
                    class="glightbox pulsating-play-btn"
                  ></a>
                </div>
                <!--fin de la div-->
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- /About Section -->

      <!-- Why Us Section -->
      <section id="why-us" class="why-us section light-background">
        <div class="container">
          <div class="row gy-4">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
              <div class="why-box">
                <h3>Pourquoi choisir la Mangeoire</h3>
                <p>
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
                  do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                  Duis aute irure dolor in reprehenderit Asperiores dolores sed
                  et. Tenetur quia eos. Autem tempore quibusdam vel
                  necessitatibus optio ad corporis.
                </p>
                <div class="text-center">
                  <a href="#" class="more-btn"
                    ><span>En savoir plus</span>
                    <i class="bi bi-chevron-right"></i
                  ></a>
                </div>
              </div>
            </div>
            <!-- End Why Box -->

            <div class="col-lg-8 d-flex align-items-stretch">
              <div class="row gy-4" data-aos="fade-up" data-aos-delay="200">
                <div class="col-xl-4">
                  <div
                    class="icon-box d-flex flex-column justify-content-center align-items-center"
                  >
                    <i class="bi bi-clipboard-data"></i>
                    <h4>statistique</h4>
                    <p>
                      Consequuntur sunt aut quasi enim aliquam quae harum
                      pariatur laboris nisi ut aliquip
                    </p>
                  </div>
                </div>
                <!-- End Icon Box -->

                <div class="col-xl-4" data-aos="fade-up" data-aos-delay="300">
                  <div
                    class="icon-box d-flex flex-column justify-content-center align-items-center"
                  >
                    <i class="bi bi-gem"></i>
                    <h4>Qualité</h4>
                    <p>
                      Excepteur sint occaecat cupidatat non proident, sunt in
                      culpa qui officia deserunt
                    </p>
                  </div>
                </div>
                <!-- End Icon Box -->

                <div class="col-xl-4" data-aos="fade-up" data-aos-delay="400">
                  <div
                    class="icon-box d-flex flex-column justify-content-center align-items-center"
                  >
                    <i class="bi bi-inboxes"></i>
                    <h4>Labore consequatur incidid dolore</h4>
                    <p>
                      Aut suscipit aut cum nemo deleniti aut omnis. Doloribus ut
                      maiores omnis facere
                    </p>
                  </div>
                </div>
                <!-- End Icon Box -->
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- /Why Us Section -->

      <!-- Stats Section -->
<?php include "includes/stats_section.php"; ?>
      <!-- /Stats Section -->

      <!-- Menu Section -->
      <section id="menu" class="menu section">
        <!-- Debut du titre du menu -->
        <div class="container section-title" data-aos="fade-up">
          <h2>Notre Menu</h2>
          <p>
            <span>Découvrez Notre</span>
            <span class="description-title"> Menu</span>
          </p>
        </div>
        <!-- Fin du titre du menu -->

        <div class="container">
          <ul
            class="nav nav-tabs d-flex justify-content-center"
            data-aos="fade-up"
            data-aos-delay="100"
          >
            <li class="nav-item">
              <a
                class="nav-link active show"
                data-bs-toggle="tab"
                data-bs-target="#menu-starters"
              >
                <h4>Starters</h4>
              </a>
            </li>
            <!-- End tab nav item -->

            <li class="nav-item">
              <a
                class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#menu-breakfast"
              >
                <h4>Breakfast</h4> </a
              ><!-- End tab nav item -->
            </li>
            <li class="nav-item">
              <a
                class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#menu-lunch"
              >
                <h4>Lunch</h4>
              </a>
            </li>
            <!-- End tab nav item -->

            <li class="nav-item">
              <a
                class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#menu-dinner"
              >
                <h4>Dinner</h4>
              </a>
            </li>
            <!-- End tab nav item -->
          </ul>

          <div class="tab-content" data-aos="fade-up" data-aos-delay="200">
            <div class="tab-pane fade active show" id="menu-starters">
              <div class="tab-header text-center">
                <p>Menu</p>
                <h3>Starters</h3>
              </div>

              <div class="row gy-5">
                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/bongo.png" class="glightbox"
                    ><img
                      src="assets/img/menu/bongo.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Bongo</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                    <!--ingrediednt-->
                  </p>
                  <p class="price"><?php echo isset($menu_prices[5]) ? $menu_prices[5]['prix_formate'] : CurrencyManager::formatPrice(9.20, true); ?></p>
                  <!--Prix-->
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="5">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/eru.png" class="glightbox"
                    ><img
                      src="assets/img/menu/eru.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eru</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[2]) ? $menu_prices[2]['prix_formate'] : CurrencyManager::formatPrice(14.80, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="2">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/koki.png" class="glightbox"
                    ><img
                      src="assets/img/menu/koki.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Koki</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[3]) ? $menu_prices[3]['prix_formate'] : CurrencyManager::formatPrice(8.50, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="3">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/ndole.png" class="glightbox"
                    ><img
                      src="assets/img/menu/ndole.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Ndole</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[1]) ? $menu_prices[1]['prix_formate'] : CurrencyManager::formatPrice(15.50, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="1">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/okok.png" class="glightbox"
                    ><img
                      src="assets/img/menu/okok.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Okok</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[4]) ? $menu_prices[4]['prix_formate'] : CurrencyManager::formatPrice(12.90, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="4">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/poisson_braisé.png" class="glightbox"
                    ><img
                      src="assets/img/menu/poisson_braisé.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Poisson Braisé</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[7]) ? $menu_prices[7]['prix_formate'] : CurrencyManager::formatPrice(18.90, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="7">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/taro.png" class="glightbox"
                    ><img
                      src="assets/img/menu/taro.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Taro</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price"><?php echo isset($menu_prices[6]) ? $menu_prices[6]['prix_formate'] : CurrencyManager::formatPrice(7.80, true); ?></p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="6">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->
              </div>
            </div>
            <!-- End Starter Menu Content -->

            <div class="tab-pane fade" id="menu-breakfast">
              <div class="tab-header text-center">
                <p>Menu</p>
                <h3>Breakfast</h3>
              </div>

              <div class="row gy-5">
                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-1.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-1.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Magnam Tiste</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">5000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="8">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-2.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-2.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Aut Luia</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">14000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="9">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-3.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-3.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Est Eligendi</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">15000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="10">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-4.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-4.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="11">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-5.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-5.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="12">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-6.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-6.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Laboriosam Direva</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">9000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="13">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->
              </div>
            </div>
            <!-- End Breakfast Menu Content -->

            <div class="tab-pane fade" id="menu-lunch">
              <div class="tab-header text-center">
                <p>Menu</p>
                <h3>Lunch</h3>
              </div>

              <div class="row gy-5">
                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-1.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-1.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Magnam Tiste</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">9000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="14">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-2.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-2.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Aut Luia</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">9000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="15">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-3.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-3.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Est Eligendi</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">9000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="16">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-4.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-4.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="11">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-5.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-5.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="12">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-6.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-6.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Laboriosam Direva</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">9000 €</p>
                  <form action="ajouter-au-panier.php" method="post" class="mt-2">
                    <input type="hidden" name="menu_id" value="13">
                    <input type="hidden" name="action" value="add">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="input-group input-group-sm" style="max-width: 100px;">
                        <span class="input-group-text">Qté</span>
                        <input type="number" name="quantite" class="form-control" value="1" min="1">
                      </div>
                      <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-cart-plus"></i> Ajouter
                      </button>
                    </div>
                  </form>
                </div>
                <!-- Menu Item -->
              </div>
            </div>
            <!-- End Lunch Menu Content -->

            <div class="tab-pane fade" id="menu-dinner">
              <div class="tab-header text-center">
                <p>Menu</p>
                <h3>Dinner</h3>
              </div>

              <div class="row gy-5">
                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-1.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-1.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Magnam Tiste</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-2.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-2.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Aut Luia</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-3.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-3.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Est Eligendi</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-4.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-4.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-5.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-5.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Eos Luibusdam</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->

                <div class="col-lg-4 menu-item">
                  <a href="assets/img/menu/menu-item-6.png" class="glightbox"
                    ><img
                      src="assets/img/menu/menu-item-6.png"
                      class="menu-img img-fluid"
                      alt=""
                  /></a>
                  <h4>Laboriosam Direva</h4>
                  <p class="ingredients">
                    Lorem, deren, trataro, filede, nerada
                  </p>
                  <p class="price">12000 €</p>
                </div>
                <!-- Menu Item -->
              </div>
            </div>
            <!-- End Dinner Menu Content -->
          </div>
        </div>
      </section>
      <!-- /Menu Section -->

      <!-- Section Temoignage -->
      <section id="testimonials" class="testimonials section light-background">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>TEMOIGNAGES</h2>
          <p>
            Que Disent-ils
            <span class="description-title">À Propos De Nous</span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">
          <div class="swiper init-swiper">
            <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 5000
                },
                "slidesPerView": "auto",
                "pagination": {
                  "el": ".swiper-pagination",
                  "type": "bullets",
                  "clickable": true
                }
              }
            </script>
            <div class="swiper-wrapper">
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="row gy-4 justify-content-center">
                    <div class="col-lg-6">
                      <div class="testimonial-content">
                        <p>
                          <i class="bi bi-quote quote-icon-left"></i>
                          <span
                            >Proin iaculis purus consequat sem cure digni ssim
                            donec porttitora entum suscipit rhoncus. Accusantium
                            quam, ultricies eget id, aliquam eget nibh et.
                            Maecen aliquam, risus at semper.</span
                          >
                          <i class="bi bi-quote quote-icon-right"></i>
                        </p>
                        <h3>MBELE JOSEPH</h3>
                        <h4>Ceo &amp; Founder</h4>
                        <div class="stars">
                          <i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-2 text-center">
                      <img
                        src="assets/img/testimonials/testimonials-1.jpg"
                        class="img-fluid testimonial-img"
                        alt=""
                      />
                    </div>
                  </div>
                </div>
              </div>
              <!-- End testimonial item -->

              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="row gy-4 justify-content-center">
                    <div class="col-lg-6">
                      <div class="testimonial-content">
                        <p>
                          <i class="bi bi-quote quote-icon-left"></i>
                          <span
                            >Export tempor illum tamen malis malis eram quae
                            irure esse labore quem cillum quid cillum eram malis
                            quorum velit fore eram velit sunt aliqua noster
                            fugiat irure amet legam anim culpa.</span
                          >
                          <i class="bi bi-quote quote-icon-right"></i>
                        </p>
                        <h3>YOMBI Enest</h3>
                        <h4>Designer</h4>
                        <div class="stars">
                          <i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-2 text-center">
                      <img
                        src="assets/img/testimonials/testimonials-2.jpg"
                        class="img-fluid testimonial-img"
                        alt=""
                      />
                    </div>
                  </div>
                </div>
              </div>
              <!-- End testimonial item -->

              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="row gy-4 justify-content-center">
                    <div class="col-lg-6">
                      <div class="testimonial-content">
                        <p>
                          <i class="bi bi-quote quote-icon-left"></i>
                          <span
                            >Enim nisi quem export duis labore cillum quae magna
                            enim sint quorum nulla quem veniam duis minim tempor
                            labore quem eram duis noster aute amet eram fore
                            quis sint minim.</span
                          >
                          <i class="bi bi-quote quote-icon-right"></i>
                        </p>
                        <h3>Jena Karlis</h3>
                        <h4>Store Owner</h4>
                        <div class="stars">
                          <i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-2 text-center">
                      <img
                        src="assets/img/testimonials/testimonials-3.jpg"
                        class="img-fluid testimonial-img"
                        alt=""
                      />
                    </div>
                  </div>
                </div>
              </div>
              <!-- End testimonial item -->

              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="row gy-4 justify-content-center">
                    <div class="col-lg-6">
                      <div class="testimonial-content">
                        <p>
                          <i class="bi bi-quote quote-icon-left"></i>
                          <span
                            >Fugiat enim eram quae cillum dolore dolor amet
                            nulla culpa multos export minim fugiat minim velit
                            minim dolor enim duis veniam ipsum anim magna sunt
                            elit fore quem dolore labore illum veniam.</span
                          >
                          <i class="bi bi-quote quote-icon-right"></i>
                        </p>
                        <h3>John Larson</h3>
                        <h4>Entrepreneur</h4>
                        <div class="stars">
                          <i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i
                          ><i class="bi bi-star-fill"></i>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-2 text-center">
                      <img
                        src="assets/img/testimonials/testimonials-4.jpg"
                        class="img-fluid testimonial-img"
                        alt=""
                      />
                    </div>
                  </div>
                </div>
              </div>
              <!-- End testimonial item -->
            </div>
            <div class="swiper-pagination"></div>
          </div>
        </div>
      </section>
      <!-- /Testimonials Section -->

      <!-- Section Evenement -->
      <section id="events" class="events section">
        <div class="container-fluid" data-aos="fade-up" data-aos-delay="100">
          <div class="swiper init-swiper">
            <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 5000
                },
                "slidesPerView": "auto",
                "pagination": {
                  "el": ".swiper-pagination",
                  "type": "bullets",
                  "clickable": true
                },
                "breakpoints": {
                  "320": {
                    "slidesPerView": 1,
                    "spaceBetween": 40
                  },
                  "1200": {
                    "slidesPerView": 3,
                    "spaceBetween": 1
                  }
                }
              }
            </script>
            <div class="swiper-wrapper">
              <div
                class="swiper-slide event-item d-flex flex-column justify-content-end"
                style="background-image: url(assets/img/events-1.jpg)"
              >
                <h3>Fêtes Personnalisées</h3>
                <div class="price align-self-start">100000 €</div>
                <p class="description">
                  Quo corporis voluptas ea ad. Consectetur inventore sapiente
                  ipsum voluptas eos omnis facere. Enim facilis veritatis id est
                  rem repudiandae nulla expedita quas.
                </p>
              </div>
              <!-- End Event item -->

              <div
                class="swiper-slide event-item d-flex flex-column justify-content-end"
                style="background-image: url(assets/img/events-2.jpg)"
              >
                <h3>Fêtes Privées</h3>
                <div class="price align-self-start">170000 €</div>
                <p class="description">
                  In delectus sint qui et enim. Et ab repudiandae inventore
                  quaerat doloribus. Facere nemo vero est ut dolores ea
                  assumenda et. Delectus saepe accusamus aspernatur.
                </p>
              </div>
              <!-- End Event item -->

              <div
                class="swiper-slide event-item d-flex flex-column justify-content-end"
                style="background-image: url(assets/img/events-3.jpg)"
              >
                <h3>Anniversaires</h3>
                <div class="price align-self-start">299000 €</div>
                <p class="description">
                  Laborum aperiam atque omnis minus omnis est qui assumenda
                  quos. Quis id sit quibusdam. Esse quisquam ducimus officia
                  ipsum ut quibusdam maxime. Non enim perspiciatis.
                </p>
              </div>
              <!-- End Event item -->

              <div
                class="swiper-slide event-item d-flex flex-column justify-content-end"
                style="background-image: url(assets/img/events-4.jpg)"
              >
                <h3>Fêtes de Mariage</h3>
                <div class="price align-self-start">500000 €</div>
                <p class="description">
                  Laborum aperiam atque omnis minus omnis est qui assumenda
                  quos. Quis id sit quibusdam. Esse quisquam ducimus officia
                  ipsum ut quibusdam maxime. Non enim perspiciatis.
                </p>
              </div>
              <!-- End Event item -->
            </div>
            <div class="swiper-pagination"></div>
          </div>
        </div>
      </section>
      <!-- /Events Section -->

      <!-- Chefs Section -->
      <section id="chefs" class="chefs section">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>chefs</h2>
          <p>
            <span>Our</span>
            <span class="description-title">Proffesional Chefs<br /></span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="row gy-4">
            <div
              class="col-lg-4 d-flex align-items-stretch"
              data-aos="fade-up"
              data-aos-delay="100"
            >
              <div class="team-member">
                <div class="member-img">
                  <img
                    src="assets/img/chefs/chefs-1.jpg"
                    class="img-fluid"
                    alt=""
                  />
                  <div class="social">
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>EZABOTO</h4>
                  <span>Master Chef</span>
                  <p>
                    Velit aut quia fugit et et. Dolorum ea voluptate vel tempore
                    tenetur ipsa quae aut. Ipsum exercitationem iure minima enim
                    corporis et voluptate.
                  </p>
                </div>
              </div>
            </div>
            <!-- End Chef Team Member -->

            <div
              class="col-lg-4 d-flex align-items-stretch"
              data-aos="fade-up"
              data-aos-delay="200"
            >
              <div class="team-member">
                <div class="member-img">
                  <img
                    src="assets/img/chefs/chefs-2.jpg"
                    class="img-fluid"
                    alt=""
                  />
                  <div class="social">
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>MAMI MAKALA</h4>
                  <span>Patissier</span>
                  <p>
                    Quo esse repellendus quia id. Est eum et accusantium
                    pariatur fugit nihil minima suscipit corporis. Voluptate sed
                    quas reiciendis animi neque sapiente.
                  </p>
                </div>
              </div>
            </div>
            <!-- End Chef Team Member -->

            <div
              class="col-lg-4 d-flex align-items-stretch"
              data-aos="fade-up"
              data-aos-delay="300"
            >
              <div class="team-member">
                <div class="member-img">
                  <img
                    src="assets/img/chefs/chefs-3.jpg"
                    class="img-fluid"
                    alt=""
                  />
                  <div class="social">
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                  </div>
                </div>
                <div class="member-info">
                  <h4>BIGMOP</h4>
                  <span>Cook</span>
                  <p>
                    Vero omnis enim consequatur. Voluptas consectetur unde qui
                    molestiae deserunt. Voluptates enim aut architecto porro
                    aspernatur molestiae modi.
                  </p>
                </div>
              </div>
            </div>
            <!-- End Chef Team Member -->
          </div>
        </div>
      </section>
      <!-- /Chefs Section -->

      <!-- Section Reservation de table -->
      <!-- Book A Table Section - Redirection vers formulaire détaillé -->
      <section id="book-a-table" class="book-a-table section">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>Réserver une table</h2>
          <p>
            <span>Réservez La Vôtre</span>
            <span class="description-title"
              >Et Venez Goûter Nos Plats<br
            /></span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="row g-0 justify-content-center" data-aos="fade-up" data-aos-delay="100">
            <div class="col-lg-8 text-center">
              <div class="reservation-redirect-card p-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div class="mb-4">
                  <i class="bi bi-calendar-check" style="font-size: 4rem; color: #d4af37;"></i>
                </div>
                
                <h3 class="mb-3" style="color: #2c3e50;">Réservation de Table</h3>
                <p class="mb-4 text-muted">
                  Réservez votre table en quelques clics avec notre formulaire détaillé. 
                  Nous vous garantissons une expérience culinaire exceptionnelle.
                </p>
                
                <div class="features-list mb-4">
                  <div class="row text-center">
                    <div class="col-md-4 mb-3">
                      <i class="bi bi-clock text-primary"></i>
                      <p class="small mb-0">Réservation rapide</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <i class="bi bi-shield-check text-success"></i>
                      <p class="small mb-0">Confirmation garantie</p>
                    </div>
                    <div class="col-md-4 mb-3">
                      <i class="bi bi-envelope text-info"></i>
                      <p class="small mb-0">Notification par email</p>
                    </div>
                  </div>
                </div>
                
                <a href="reserver-table.php" class="btn btn-primary btn-lg px-5 py-3" style="background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%); border: none; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                  <i class="bi bi-calendar-plus me-2"></i>
                  Réserver Maintenant
                </a>
                
                <div class="mt-3">
                  <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Réservation gratuite • Annulation jusqu'à 2h avant
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      
      <!-- 
      ========================================================================
      ANCIENNE SECTION DE RÉSERVATION RAPIDE - COMMENTÉE
      ========================================================================
      Cette section contenait un formulaire de réservation rapide intégré.
      Elle a été remplacée par une redirection vers le formulaire détaillé 
      pour une meilleure expérience utilisateur et une gestion centralisée.
      
      <section id="book-a-table" class="book-a-table section">
        <div class="container section-title" data-aos="fade-up">
          <h2>Reserver une table</h2>
          <p>
            <span>Reserver La Votre</span>
            <span class="description-title"
              >Et Venez Gouter Nos Plats<br
            /></span>
          </p>
        </div>

        <div class="container">
          <div class="row g-0" data-aos="fade-up" data-aos-delay="100">
            <div
              class="col-lg-4 reservation-img"
              style="background-image: url(assets/img/reservation.jpg)"
            ></div>

            <div
              class="col-lg-8 d-flex align-items-center reservation-form-bg"
              data-aos="fade-up"
              data-aos-delay="200"
            >
              <form
                action="reserver-table.php"
                method="get"
                role="form"
                class="php-email-form"
              >
                <div class="text-center mb-4">
                  <h3>Réservation Rapide</h3>
                  <p class="text-muted">Remplissez ce formulaire pour réserver votre table</p>
                </div>
                
                <div class="row gy-4">
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="text"
                      name="name"
                      class="form-control"
                      id="name"
                      placeholder="Votre Nom"
                      required=""
                    />
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="email"
                      class="form-control"
                      name="email"
                      id="email"
                      placeholder="Votre Email"
                      required=""
                    />
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="text"
                      class="form-control"
                      name="phone"
                      id="phone"
                      placeholder="Votre numero de telephone"
                      required=""
                    />
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="number"
                      class="form-control"
                      name="people"
                      id="people"
                      placeholder="# Nombre de Personnes"
                      min="1"
                      required=""
                    />
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="date"
                      name="date"
                      class="form-control"
                      id="date"
                      placeholder="Date"
                      min="<?php echo date('Y-m-d'); ?>"
                      required=""
                    />
                  </div>
                  <div class="col-lg-6 col-md-6">
                    <input
                      type="time"
                      class="form-control"
                      name="time"
                      id="time"
                      placeholder="Heure"
                      min="11:00"
                      max="23:00"
                      required=""
                    />
                  </div>
                </div>

                <div class="form-group mt-3">
                  <textarea
                    class="form-control"
                    name="message"
                    rows="3"
                    placeholder="Message (optionnel)"
                  ></textarea>
                </div>

                <div class="text-center mt-4">
                  <div class="row">
                    <div class="col-md-6 mb-2">
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check"></i> Réservation Complète
                      </button>
                    </div>
                    <div class="col-md-6">
                      <a href="reserver-table.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-arrow-right"></i> Formulaire Détaillé
                      </a>
                    </div>
                  </div>
                  <small class="text-muted mt-2 d-block">
                    Nous vous contacterons pour confirmer votre réservation
                  </small>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>
      ========================================================================
      FIN DE L'ANCIENNE SECTION COMMENTÉE
      ========================================================================
      -->
      <!-- /Book A Table Section -->

      <!-- Gallery Section -->
      <section id="gallery" class="gallery section light-background">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>Galerie</h2>
          <p>
            <span>Visitez</span>
            <span class="description-title">Notre Galerie</span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">
          <div class="swiper init-swiper">
            <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 5000
                },
                "slidesPerView": "auto",
                "centeredSlides": true,
                "pagination": {
                  "el": ".swiper-pagination",
                  "type": "bullets",
                  "clickable": true
                },
                "breakpoints": {
                  "320": {
                    "slidesPerView": 1,
                    "spaceBetween": 0
                  },
                  "768": {
                    "slidesPerView": 3,
                    "spaceBetween": 20
                  },
                  "1200": {
                    "slidesPerView": 5,
                    "spaceBetween": 20
                  }
                }
              }
            </script>
            <div class="swiper-wrapper align-items-center">
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-1.jpg"
                  ><img
                    src="assets/img/gallery/gallery-1.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-2.jpg"
                  ><img
                    src="assets/img/gallery/gallery-2.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-3.jpg"
                  ><img
                    src="assets/img/gallery/gallery-3.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-4.jpg"
                  ><img
                    src="assets/img/gallery/gallery-4.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-5.jpg"
                  ><img
                    src="assets/img/gallery/gallery-5.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-6.jpg"
                  ><img
                    src="assets/img/gallery/gallery-6.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-7.jpg"
                  ><img
                    src="assets/img/gallery/gallery-7.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
              <div class="swiper-slide">
                <a
                  class="glightbox"
                  data-gallery="images-gallery"
                  href="assets/img/gallery/gallery-8.jpg"
                  ><img
                    src="assets/img/gallery/gallery-8.jpg"
                    class="img-fluid"
                    alt=""
                /></a>
              </div>
            </div>
            <div class="swiper-pagination"></div>
          </div>
        </div>
      </section>
      <!-- /Gallery Section -->

      <!-- Contact Section -->
      <section id="contact" class="contact section">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>Contact</h2>
          <p>
            <span>Besoin d'aide?</span>
            <span class="description-title">Contactez-Nous</span>
          </p>
        </div>
        <!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">
          <?php
          // Affichage des messages de succès/erreur pour le formulaire de contact
          if (isset($_SESSION['contact_success'])) {
              echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                      <i class="bi bi-check-circle-fill"></i> ' . $_SESSION['contact_success'] . '
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
              unset($_SESSION['contact_success']);
          }
          if (isset($_SESSION['contact_error'])) {
              echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <i class="bi bi-exclamation-triangle-fill"></i> ' . $_SESSION['contact_error'] . '
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
              unset($_SESSION['contact_error']);
          }
          ?>
          
          <div class="mb-5">
            <iframe
              style="width: 100%; height: 400px"
              src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d12097.433213460943!2d-74.0062269!3d40.7101282!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xb89d1fe6bc499443!2sDowntown+Conference+Center!5e0!3m2!1smk!2sbg!4v1539943755621"
              frameborder="0"
              allowfullscreen=""
            ></iframe>
          </div>
          <!-- End Google Maps -->

          <div class="row gy-4">
            <div class="col-md-6">
              <div
                class="info-item d-flex align-items-center"
                data-aos="fade-up"
                data-aos-delay="200"
              >
                <i class="icon bi bi-geo-alt flex-shrink-0"></i>
                <div>
                  <h3>Adresse</h3>
                  <p>Hotel du plateau ESSOS</p>
                </div>
              </div>
            </div>
            <!-- End Info Item -->

            <div class="col-md-6">
              <div
                class="info-item d-flex align-items-center"
                data-aos="fade-up"
                data-aos-delay="300"
              >
                <i class="icon bi bi-telephone flex-shrink-0"></i>
                <div>
                  <h3>Appelez Nous</h3>
                  <p>+237 6 96 56 85 20</p>
                </div>
              </div>
            </div>
            <!-- End Info Item -->

            <div class="col-md-6">
              <div
                class="info-item d-flex align-items-center"
                data-aos="fade-up"
                data-aos-delay="400"
              >
                <i class="icon bi bi-envelope flex-shrink-0"></i>
                <div>
                  <h3>laissez nous un Email</h3>
                  <p>la-mangeoire@gmail.com</p>
                </div>
              </div>
            </div>
            <!-- End Info Item -->

            <div class="col-md-6">
              <div
                class="info-item d-flex align-items-center"
                data-aos="fade-up"
                data-aos-delay="500"
              >
                <i class="icon bi bi-clock flex-shrink-0"></i>
                <div>
                  <h3>Heure D'ouverture<br /></h3>
                  <p>
                    <strong>Lun-Sam:</strong> 11H - 23H;
                    <strong>Dimanche:</strong> Fermé
                  </p>
                </div>
              </div>
            </div>
            <!-- End Info Item -->
          </div>

          <form
            action="forms/contact.php"
            method="post"
            class="php-email-form"
            data-aos="fade-up"
            data-aos-delay="600"
          >
            <div class="row gy-4">
              <div class="col-md-6">
                <input
                  type="text"
                  name="name"
                  class="form-control"
                  placeholder="Votre Nom"
                  required=""
                />
              </div>

              <div class="col-md-6">
                <input
                  type="email"
                  class="form-control"
                  name="email"
                  placeholder="Votre Email"
                  required=""
                />
              </div>

              <div class="col-md-12">
                <input
                  type="text"
                  class="form-control"
                  name="subject"
                  placeholder="Objet"
                  required=""
                />
              </div>

              <div class="col-md-12">
                <textarea
                  class="form-control"
                  name="message"
                  rows="6"
                  placeholder="Message"
                  required=""
                ></textarea>
              </div>

              <div class="col-md-12 text-center">
                <div class="loading">Loading</div>
                <div class="error-message"></div>
                <div class="sent-message">
                  Votre message a été envoyé avec succès !
                </div>

                <button type="submit">Envoyer le Message</button>
              </div>
            </div>
          </form>
          <!-- End Contact Form -->
        </div>
      </section>
      <!-- /Contact Section -->
    </main>

    <footer id="footer" class="footer dark-background">
      <div class="container">
        <div class="row gy-3">
          <div class="col-lg-3 col-md-6 d-flex">
            <i class="bi bi-geo-alt icon"></i>
            <div class="address">
              <h4>Adresse</h4>
              <p>Hotel du plateau</p>
              <p>ESSOS</p>
              <p></p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 d-flex">
            <i class="bi bi-telephone icon"></i>
            <div>
              <h4>Contact</h4>
              <p>
                <strong>telephone:</strong> <span>+237 6 96 56 85 20</span
                ><br />
                <strong>Email:</strong> <span>la-mangeoire@gmail.com</span
                ><br />
              </p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 d-flex">
            <i class="bi bi-clock icon"></i>
            <div>
              <h4>Heures d'ouverture</h4>
              <p>
                <strong>Lun-Sam:</strong> <span>11H - 23H</span><br />
                <strong>Dimanche</strong>: <span>Fermé</span>
              </p>
            </div>
          </div>

          <div class="col-lg-3 col-md-6">
            <h4>Suivez Nous</h4>
            <div class="social-links d-flex">
              <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
              <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
              <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
              <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="container copyright text-center mt-4">
        <p>
          © <span>Copyright</span>
          <strong class="px-1 sitename">La Mangeoire</strong>
          <span>All Rights Reserved</span>
        </p>
        <div class="credits">
          <!-- All the links in the footer should remain intact. -->
          <!-- You can delete the links only if you've purchased the pro version. -->
          <!-- Licensing information: https://bootstrapmade.com/license/ -->
          <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
          Designed by
          <a href="https://bootstrapmade.com/">FLAMINGO</a> Distributed by
          <a href="https://themewagon.com">JOSEPH</a>
        </div>
      </div>
    </footer>

    <!-- Scroll Top -->
    <a
      href="#"
      id="scroll-top"
      class="scroll-top d-flex align-items-center justify-content-center"
      ><i class="bi bi-arrow-up-short"></i
    ></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    
    <!-- Script pour le système de gestion des cookies -->
    <script src="assets/js/cookie-consent.js"></script>
  </body>
</html>
