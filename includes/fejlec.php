<?php
/*
 * OLDAL FEJLÉCE (minden oldal elején betöltjük)
 * Tartalma: <head>, a navigációs menü és az üzenetsáv.
 * Az oldalak a $oldalCim változóval adják meg a böngészőfül címét.
 */
require_once __DIR__ . '/fuggvenyek.php';

$felhasznalo = bejelentkezett_felhasznalo();
$oldalCim = $oldalCim ?? 'Kisállat menhely';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= kiir($oldalCim) ?> – Mancs Menhely</title>
    <link rel="stylesheet" href="<?= ALAP_URL ?>/assets/css/stilus.css">
</head>
<body>

<header class="fejlec">
    <div class="tarolo menusor">
        <a class="logo" href="<?= ALAP_URL ?>/index.php">🐾 Mancs Menhely</a>
        <nav>
            <a href="<?= ALAP_URL ?>/index.php">Főoldal</a>
            <a href="<?= ALAP_URL ?>/allatok.php">Állataink</a>
            <?php if ($felhasznalo): ?>
                <a href="<?= ALAP_URL ?>/uj_allat.php">Állat bejelentése</a>
                <a href="<?= ALAP_URL ?>/kerelmeim.php">Kérelmeim</a>
                <?php if (admin_e()): ?>
                    <a href="<?= ALAP_URL ?>/admin/index.php" class="admin-link">Admin</a>
                <?php endif; ?>
                <span class="belepett">👤 <?= kiir($felhasznalo['nev']) ?></span>
                <a href="<?= ALAP_URL ?>/kilepes.php">Kilépés</a>
            <?php else: ?>
                <a href="<?= ALAP_URL ?>/belepes.php">Belépés</a>
                <a href="<?= ALAP_URL ?>/regisztracio.php" class="gomb gomb-kicsi">Regisztráció</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="tarolo tartalom">
<?php foreach (uzenetek_lekerese() as $uzenet): ?>
    <div class="uzenet uzenet-<?= kiir($uzenet['tipus']) ?>"><?= kiir($uzenet['szoveg']) ?></div>
<?php endforeach; ?>
