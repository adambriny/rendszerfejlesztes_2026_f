<?php
/*
 * ADMIN – FELHASZNÁLÓK
 * Lista, szerepkör váltás (user ↔ admin) és törlés.
 * A saját fiókodat itt nem módosíthatod, hogy véletlenül ne zárd ki magad.
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();
$bejelentkezett = bejelentkezett_felhasznalo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    $felhasznaloId = (int)($_POST['felhasznalo_id'] ?? 0);
    $muvelet = $_POST['muvelet'] ?? '';

    if ($felhasznaloId === (int)$bejelentkezett['id']) {
        uzenet_beallit('hiba', 'A saját fiókodat itt nem módosíthatod.');
    } elseif ($muvelet === 'szerep') {
        $ujSzerep = ($_POST['szerep'] ?? '') === 'admin' ? 'admin' : 'felhasznalo';
        kapcsolat()->prepare('UPDATE felhasznalok SET szerep = ? WHERE id = ?')->execute([$ujSzerep, $felhasznaloId]);
        uzenet_beallit('siker', 'Szerepkör módosítva.');
    } elseif ($muvelet === 'torles') {
        kapcsolat()->prepare('DELETE FROM felhasznalok WHERE id = ?')->execute([$felhasznaloId]);
        uzenet_beallit('siker', 'Felhasználó törölve.');
    }
    atiranyit('admin/felhasznalok.php');
}

$felhasznalok = kapcsolat()->query(
    'SELECT f.*,
            (SELECT COUNT(*) FROM orokbefogadasi_kerelmek k WHERE k.felhasznalo_id = f.id) AS kerelmek_szama
     FROM felhasznalok f ORDER BY f.id'
)->fetchAll();

$oldalCim = 'Admin – felhasználók';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<h1>Felhasználók</h1>
<table class="tabla">
    <thead><tr><th>#</th><th>Név</th><th>E-mail</th><th>Örökbef. kérelmek</th><th>Szerepkör</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($felhasznalok as $sor): ?>
        <tr>
            <td><?= $sor['id'] ?></td>
            <td><?= kiir($sor['nev']) ?></td>
            <td><?= kiir($sor['email']) ?></td>
            <td><?= (int)$sor['kerelmek_szama'] ?></td>
            <td><?= $sor['szerep'] === 'admin' ? 'Admin' : 'Felhasználó' ?></td>
            <td class="muveletek">
                <?php if ((int)$sor['id'] !== (int)$bejelentkezett['id']): ?>
                    <form method="post">
                        <?= csrf_mezo() ?>
                        <input type="hidden" name="muvelet" value="szerep">
                        <input type="hidden" name="felhasznalo_id" value="<?= $sor['id'] ?>">
                        <input type="hidden" name="szerep" value="<?= $sor['szerep'] === 'admin' ? 'felhasznalo' : 'admin' ?>">
                        <button class="gomb gomb-kicsi gomb-ures" type="submit"><?= $sor['szerep'] === 'admin' ? 'Admin jog elvétele' : 'Adminná tesz' ?></button>
                    </form>
                    <form method="post" data-megerosites="Biztosan törlöd a felhasználót és a kérelmeit?">
                        <?= csrf_mezo() ?>
                        <input type="hidden" name="muvelet" value="torles">
                        <input type="hidden" name="felhasznalo_id" value="<?= $sor['id'] ?>">
                        <button class="gomb gomb-kicsi gomb-veszely" type="submit">Törlés</button>
                    </form>
                <?php else: ?>
                    <span class="halvany">(te)</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
