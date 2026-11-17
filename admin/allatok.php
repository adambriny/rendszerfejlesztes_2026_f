<?php
/*
 * ADMIN – ÁLLATOK KEZELÉSE
 * Táblázat az összes állatról, szerkesztés és törlés gombbal.
 * Új állat felvétele kérelem nélkül is lehetséges (+ Új állat gomb).
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();

/* ---------- Törlés feldolgozása ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    $allatId = (int)($_POST['allat_id'] ?? 0);

    $kepLekeres = kapcsolat()->prepare('SELECT kep FROM allatok WHERE id = ?');
    $kepLekeres->execute([$allatId]);
    $kep = $kepLekeres->fetchColumn();

    // Az állat törlésekor az ő örökbefogadási kérelmei is törlődnek (az adatbázis külső kulcsa intézi)
    $torles = kapcsolat()->prepare('DELETE FROM allatok WHERE id = ?');
    $torles->execute([$allatId]);

    if ($torles->rowCount() > 0) {
        kep_torlese($kep ?: null);
        uzenet_beallit('siker', 'Az állat törölve.');
    } else {
        uzenet_beallit('hiba', 'Az állat nem található.');
    }
    atiranyit('admin/allatok.php');
}

$allatok = kapcsolat()->query('SELECT * FROM allatok ORDER BY id DESC')->fetchAll();

$oldalCim = 'Admin – állatok';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<div class="sor-szet">
    <h1>Állatok kezelése</h1>
    <a class="gomb" href="<?= ALAP_URL ?>/admin/allat_urlap.php">+ Új állat felvétele</a>
</div>

<table class="tabla">
    <thead><tr><th>#</th><th>Kép</th><th>Név</th><th>Faj</th><th>Kor</th><th>Szín</th><th>Állapot</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($allatok as $allat): ?>
        <tr>
            <td><?= $allat['id'] ?></td>
            <td><?= allat_kep_html($allat, 'kis-kep') ?></td>
            <td><?= kiir($allat['nev']) ?></td>
            <td><?= kiir($allat['faj']) ?></td>
            <td><?= kor_felirat((int)$allat['kor']) ?></td>
            <td><?= kiir($allat['szin']) ?></td>
            <td><span class="cimke cimke-<?= kiir($allat['statusz']) ?>"><?= statusz_felirat($allat['statusz']) ?></span></td>
            <td class="muveletek">
                <a class="gomb gomb-kicsi gomb-ures" href="<?= ALAP_URL ?>/admin/allat_urlap.php?id=<?= $allat['id'] ?>">Szerkesztés</a>
                <form method="post" data-megerosites="Biztosan törlöd <?= kiir($allat['nev']) ?> adatlapját? A hozzá tartozó kérelmek is törlődnek.">
                    <?= csrf_mezo() ?>
                    <input type="hidden" name="allat_id" value="<?= $allat['id'] ?>">
                    <button class="gomb gomb-kicsi gomb-veszely" type="submit">Törlés</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
