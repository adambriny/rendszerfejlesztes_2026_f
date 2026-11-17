<?php
/*
 * ÁLLATOK LISTÁJA
 * Minden állatot kilistázunk egymás után (nincs fajonkénti bontás).
 * A még gazdát keresők kerülnek előre, utánuk az örökbefogadottak.
 * (Az "statusz" oszlop ENUM, ezért a rendezés a felsorolás sorrendjét követi: gazdat_keres, orokbefogadva.)
 */
$oldalCim = 'Állataink';
require __DIR__ . '/includes/fejlec.php';

$allatok = kapcsolat()->query(
    'SELECT * FROM allatok ORDER BY statusz ASC, letrehozva DESC, id DESC'
)->fetchAll();
?>

<h1>Állataink</h1>

<?php if (!$allatok): ?>
    <div class="ures">Még nincs állat az adatbázisban.</div>
<?php else: ?>
    <div class="racs">
        <?php foreach ($allatok as $allat): ?>
            <article class="kartya">
                <a href="<?= ALAP_URL ?>/allat.php?id=<?= $allat['id'] ?>"><?= allat_kep_html($allat) ?></a>
                <div class="kartya-tartalom">
                    <h3><?= kiir($allat['nev']) ?></h3>
                    <p class="halvany">
                        <?= kiir($allat['faj']) ?> · <?= kor_felirat((int)$allat['kor']) ?> · <?= kiir($allat['szin']) ?>
                    </p>
                    <span class="cimke cimke-<?= kiir($allat['statusz']) ?>"><?= statusz_felirat($allat['statusz']) ?></span>
                    <a class="gomb gomb-kicsi" href="<?= ALAP_URL ?>/allat.php?id=<?= $allat['id'] ?>">Részletek</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/lablec.php'; ?>
