<?php
/*
 * ADMIN ÁTTEKINTÉS
 * Számlálók az adatbázisból: hány állat van, hány kérelem vár elbírálásra stb.
 * Az admin_kell() gondoskodik róla, hogy csak admin szerepű felhasználó láthassa.
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();

$szamok = kapcsolat()->query(
    "SELECT
        (SELECT COUNT(*) FROM allatok)                                                      AS osszes_allat,
        (SELECT COUNT(*) FROM allatok WHERE statusz = 'gazdat_keres')                       AS gazdat_keres,
        (SELECT COUNT(*) FROM allatok WHERE statusz = 'orokbefogadva')                      AS orokbefogadva,
        (SELECT COUNT(*) FROM orokbefogadasi_kerelmek WHERE statusz = 'fuggoben')           AS fuggo_orokbefogadas,
        (SELECT COUNT(*) FROM allat_felvetel_kerelmek WHERE statusz = 'fuggoben')           AS fuggo_felvetel,
        (SELECT COUNT(*) FROM felhasznalok)                                                 AS felhasznalok"
)->fetch();

$oldalCim = 'Admin – áttekintés';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<h1>Admin áttekintés</h1>

<div class="statisztika">
    <div class="stat-doboz"><span><?= (int)$szamok['osszes_allat'] ?></span>állat összesen</div>
    <div class="stat-doboz"><span><?= (int)$szamok['gazdat_keres'] ?></span>gazdát keres</div>
    <div class="stat-doboz"><span><?= (int)$szamok['orokbefogadva'] ?></span>örökbefogadva</div>
    <div class="stat-doboz"><span><?= (int)$szamok['felhasznalok'] ?></span>felhasználó</div>
</div>

<div class="ket-oszlop">
    <section class="doboz">
        <h2>Örökbefogadási kérelmek</h2>
        <p><strong><?= (int)$szamok['fuggo_orokbefogadas'] ?></strong> kérelem vár elbírálásra.</p>
        <a class="gomb gomb-kicsi" href="<?= ALAP_URL ?>/admin/orokbefogadasi_kerelmek.php">Megnyitás</a>
    </section>
    <section class="doboz">
        <h2>Állatbejelentések</h2>
        <p><strong><?= (int)$szamok['fuggo_felvetel'] ?></strong> bejelentés vár elbírálásra.</p>
        <a class="gomb gomb-kicsi" href="<?= ALAP_URL ?>/admin/felvetel_kerelmek.php">Megnyitás</a>
    </section>
</div>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
