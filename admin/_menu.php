<?php
/* Az admin oldalak közös almenüje. Az aktuális oldal kiemelését a fájlnév alapján döntjük el. */
$aktualisOldal = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="almenu">
    <a href="<?= ALAP_URL ?>/admin/index.php" class="<?= $aktualisOldal === 'index.php' ? 'aktiv' : '' ?>">Áttekintés</a>
    <a href="<?= ALAP_URL ?>/admin/allatok.php" class="<?= in_array($aktualisOldal, ['allatok.php', 'allat_urlap.php']) ? 'aktiv' : '' ?>">Állatok</a>
    <a href="<?= ALAP_URL ?>/admin/orokbefogadasi_kerelmek.php" class="<?= $aktualisOldal === 'orokbefogadasi_kerelmek.php' ? 'aktiv' : '' ?>">Örökbefogadási kérelmek</a>
    <a href="<?= ALAP_URL ?>/admin/felvetel_kerelmek.php" class="<?= $aktualisOldal === 'felvetel_kerelmek.php' ? 'aktiv' : '' ?>">Állatbejelentések</a>
    <a href="<?= ALAP_URL ?>/admin/felhasznalok.php" class="<?= $aktualisOldal === 'felhasznalok.php' ? 'aktiv' : '' ?>">Felhasználók</a>
</nav>
