<?php
require_once __DIR__ . '/get_stats.php';

// Récupération des statistiques
$stats = getStatistiques();
?>
<!-- Stats Section -->
<section id="stats" class="stats section dark-background">
  <img src="assets/img/stats-bg.jpg" alt="" data-aos="fade-in" />

  <div class="container position-relative" data-aos="fade-up" data-aos-delay="100">
    <div class="row gy-4">
      <div class="col-lg-3 col-md-6">
        <div class="stats-item text-center w-100 h-100">
          <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['clients']; ?>" data-purecounter-duration="1" class="purecounter"></span>
          <p>Clients</p>
        </div>
      </div>
      <!-- End Stats Item -->

      <div class="col-lg-3 col-md-6">
        <div class="stats-item text-center w-100 h-100">
          <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['menus']; ?>" data-purecounter-duration="1" class="purecounter"></span>
          <p>Menus</p>
        </div>
      </div>
      <!-- End Stats Item -->

      <div class="col-lg-3 col-md-6">
        <div class="stats-item text-center w-100 h-100">
          <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['heures_ouverture']; ?>" data-purecounter-duration="1" class="purecounter"></span>
          <p>Heures d'ouverture / semaine</p>
        </div>
      </div>
      <!-- End Stats Item -->

      <div class="col-lg-3 col-md-6">
        <div class="stats-item text-center w-100 h-100">
          <span data-purecounter-start="0" data-purecounter-end="<?php echo $stats['employes']; ?>" data-purecounter-duration="1" class="purecounter"></span>
          <p>Employés</p>
        </div>
      </div>
      <!-- End Stats Item -->
    </div>
  </div>
</section>
<!-- /Stats Section -->
