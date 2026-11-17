<?php
/*
 * KÉRELMEIM
 * A bejelentkezett felhasználó itt látja mindkét típusú kérelmét:
 *   - örökbefogadási kérelmek (egy meglévő állatra)
 *   - állatbejelentések (új állat felvétele)
 * A még elbírálatlan ("fuggoben") kérelmet vissza lehet vonni.
 */
require_once __DIR__ . '/includes/fuggvenyek.php';
belepes_kell();
$felhasznalo = bejelentkezett_felhasznalo();

/* ---------- Visszavonás feldolgozása ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    $kerelemId = (int)($_POST['kerelem_id'] ?? 0);
    $tipus = $_POST['tipus'] ?? '';

    if ($tipus === 'orokbefogadas') {
        $visszavonas = kapcsolat()->prepare(
            "UPDATE orokbefogadasi_kerelmek SET statusz = 'visszavonva', elbiralva = NOW()
             WHERE id = ? AND felhasznalo_id = ? AND statusz = 'fuggoben'"
        );
        $visszavonas->execute([$kerelemId, $felhasznalo['id']]);
        $sikerult = $visszavonas->rowCount() > 0;

    } elseif ($tipus === 'felvetel') {
        // Előbb lekérjük a képet, hogy visszavonáskor a fájlt is törölhessük
        $kepLekeres = kapcsolat()->prepare(
            "SELECT kep FROM allat_felvetel_kerelmek WHERE id = ? AND felhasznalo_id = ? AND statusz = 'fuggoben'"
        );
        $kepLekeres->execute([$kerelemId, $felhasznalo['id']]);
        $kep = $kepLekeres->fetchColumn();

        $visszavonas = kapcsolat()->prepare(
            "UPDATE allat_felvetel_kerelmek SET statusz = 'visszavonva', elbiralva = NOW()
             WHERE id = ? AND felhasznalo_id = ? AND statusz = 'fuggoben'"
        );
        $visszavonas->execute([$kerelemId, $felhasznalo['id']]);
        $sikerult = $visszavonas->rowCount() > 0;
        if ($sikerult) kep_torlese($kep ?: null);
    } else {
        $sikerult = false;
    }

    uzenet_beallit($sikerult ? 'siker' : 'hiba', $sikerult ? 'A kérelmet visszavontad.' : 'A kérelem nem vonható vissza.');
    atiranyit('kerelmeim.php');
}

/* ---------- Kérelmek lekérése ---------- */
$lekerdezes = kapcsolat()->prepare(
    'SELECT k.*, a.nev AS allat_nev
     FROM orokbefogadasi_kerelmek k
     JOIN allatok a ON a.id = k.allat_id
     WHERE k.felhasznalo_id = ?
     ORDER BY k.letrehozva DESC'
);
$lekerdezes->execute([$felhasznalo['id']]);
$orokbefogadasiKerelmek = $lekerdezes->fetchAll();

$lekerdezes = kapcsolat()->prepare(
    'SELECT * FROM allat_felvetel_kerelmek WHERE felhasznalo_id = ? ORDER BY letrehozva DESC'
);
$lekerdezes->execute([$felhasznalo['id']]);
$felvetelKerelmek = $lekerdezes->fetchAll();

$oldalCim = 'Kérelmeim';
require __DIR__ . '/includes/fejlec.php';
?>
<h1>Kérelmeim</h1>

<h2>Örökbefogadási kérelmeim</h2>
<?php if (!$orokbefogadasiKerelmek): ?>
    <div class="ures">Még nem adtál be örökbefogadási kérelmet. <a href="<?= ALAP_URL ?>/allatok.php">Válassz egy állatot!</a></div>
<?php else: ?>
    <table class="tabla">
        <thead><tr><th>Állat</th><th>Beadva</th><th>Állapot</th><th>Üzenetem</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orokbefogadasiKerelmek as $kerelem): ?>
            <tr>
                <td><a href="<?= ALAP_URL ?>/allat.php?id=<?= $kerelem['allat_id'] ?>"><?= kiir($kerelem['allat_nev']) ?></a></td>
                <td><?= kiir(date('Y.m.d. H:i', strtotime($kerelem['letrehozva']))) ?></td>
                <td><span class="cimke cimke-<?= kiir($kerelem['statusz']) ?>"><?= statusz_felirat($kerelem['statusz']) ?></span></td>
                <td><?= kiir(mb_strimwidth($kerelem['uzenet'], 0, 70, '…')) ?></td>
                <td>
                    <?php if ($kerelem['statusz'] === 'fuggoben'): ?>
                        <form method="post" data-megerosites="Biztosan visszavonod a kérelmet?">
                            <?= csrf_mezo() ?>
                            <input type="hidden" name="tipus" value="orokbefogadas">
                            <input type="hidden" name="kerelem_id" value="<?= $kerelem['id'] ?>">
                            <button class="gomb gomb-kicsi gomb-veszely" type="submit">Visszavonás</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Bejelentett állataim</h2>
<?php if (!$felvetelKerelmek): ?>
    <div class="ures">Még nem jelentettél be állatot. <a href="<?= ALAP_URL ?>/uj_allat.php">Állat bejelentése</a></div>
<?php else: ?>
    <table class="tabla">
        <thead><tr><th>Név</th><th>Faj</th><th>Beadva</th><th>Állapot</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($felvetelKerelmek as $kerelem): ?>
            <tr>
                <td><?= kiir($kerelem['allat_nev']) ?></td>
                <td><?= kiir($kerelem['faj']) ?></td>
                <td><?= kiir(date('Y.m.d. H:i', strtotime($kerelem['letrehozva']))) ?></td>
                <td><span class="cimke cimke-<?= kiir($kerelem['statusz']) ?>"><?= statusz_felirat($kerelem['statusz']) ?></span></td>
                <td>
                    <?php if ($kerelem['statusz'] === 'fuggoben'): ?>
                        <form method="post" data-megerosites="Biztosan visszavonod a bejelentést?">
                            <?= csrf_mezo() ?>
                            <input type="hidden" name="tipus" value="felvetel">
                            <input type="hidden" name="kerelem_id" value="<?= $kerelem['id'] ?>">
                            <button class="gomb gomb-kicsi gomb-veszely" type="submit">Visszavonás</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/lablec.php'; ?>
