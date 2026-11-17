<?php
/*
 * FŐOLDAL
 * Részei: 1) bemutatkozás  2) az örökbefogadás menete  3) legújabb gazdát kereső állatok
 * Az adatbázisból csak a statisztikát és a legújabb állatokat kérjük le, a többi statikus szöveg.
 */
$oldalCim = 'Főoldal';
require __DIR__ . '/includes/fejlec.php';

// Statisztika: hány állat vár gazdára, és hányan találtak már otthonra
$statisztika = kapcsolat()->query(
    "SELECT
        SUM(statusz = 'gazdat_keres')  AS varakozik,
        SUM(statusz = 'orokbefogadva') AS otthonra_talalt
     FROM allatok"
)->fetch();

// A 3 legújabb állat, aki még gazdát keres
$legujabbAllatok = kapcsolat()->query(
    "SELECT * FROM allatok WHERE statusz = 'gazdat_keres' ORDER BY letrehozva DESC, id DESC LIMIT 3"
)->fetchAll();
?>

<!-- 1) BEMUTATKOZÁS -->
<section class="bemutatkozas">
    <h1>Üdvözlünk a Mancs Menhelyen!</h1>
    <p>
        Menhelyünk elhagyott és gazdátlan kisállatoknak keres szerető családot. Nálunk kutyák,
        macskák és más kedves kisállatok várják, hogy új otthonra találjanak. Az oldalon
        megnézheted az állatainkat, online örökbefogadási kérelmet adhatsz be, és ha találtál
        egy gazdátlan állatot, azt is bejelentheted nekünk.
    </p>
    <a class="gomb" href="<?= ALAP_URL ?>/allatok.php">Állataink megtekintése</a>
    <?php if (!$felhasznalo): ?>
        <a class="gomb gomb-ures" href="<?= ALAP_URL ?>/regisztracio.php">Regisztráció</a>
    <?php endif; ?>
</section>

<div class="statisztika">
    <div class="stat-doboz"><span><?= (int)$statisztika['varakozik'] ?></span>állat vár gazdára</div>
    <div class="stat-doboz"><span><?= (int)$statisztika['otthonra_talalt'] ?></span>már otthonra talált</div>
</div>

<!-- 2) ÖRÖKBEFOGADÁS MENETE -->
<section class="doboz">
    <h2>Hogyan működik az örökbefogadás?</h2>
    <ol class="lepesek">
        <li><strong>Regisztrálj és jelentkezz be.</strong> Kérelmet csak bejelentkezett felhasználó adhat be.</li>
        <li><strong>Válaszd ki az állatot</strong> az Állataink oldalon, és nyisd meg az adatlapját.</li>
        <li><strong>Töltsd ki az örökbefogadási kérelmet</strong> – írd meg, ki vagy, és miért szeretnél örökbe fogadni.</li>
        <li><strong>Munkatársunk elbírálja a kérelmet.</strong> Az állapotát a Kérelmeim oldalon követheted.</li>
    </ol>
    <p class="halvany">
        Találtál egy gazdátlan állatot, vagy nem tudod tovább tartani a sajátodat?
        <a href="<?= ALAP_URL ?>/uj_allat.php">Jelentsd be nekünk</a> – ha az admin elfogadja, felvesszük az állatot az adatbázisunkba.
    </p>
</section>

<!-- 3) LEGÚJABB ÁLLATOK -->
<h2>Legújabb gazdát kereső állataink</h2>
<?php if (!$legujabbAllatok): ?>
    <div class="ures">Jelenleg nincs gazdát kereső állat.</div>
<?php else: ?>
    <div class="racs">
        <?php foreach ($legujabbAllatok as $allat): ?>
            <article class="kartya">
                <a href="<?= ALAP_URL ?>/allat.php?id=<?= $allat['id'] ?>"><?= allat_kep_html($allat) ?></a>
                <div class="kartya-tartalom">
                    <h3><?= kiir($allat['nev']) ?></h3>
                    <p class="halvany"><?= kiir($allat['faj']) ?> · <?= kor_felirat((int)$allat['kor']) ?> · <?= kiir($allat['szin']) ?></p>
                    <a class="gomb gomb-kicsi" href="<?= ALAP_URL ?>/allat.php?id=<?= $allat['id'] ?>">Részletek</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/lablec.php'; ?>
