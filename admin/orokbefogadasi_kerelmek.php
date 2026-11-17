<?php
/*
 * ADMIN – ÖRÖKBEFOGADÁSI KÉRELMEK ELBÍRÁLÁSA
 * Elfogadáskor (egy adatbázis-tranzakcióban, hogy vagy minden lefusson, vagy semmi):
 *   1) a kérelem "elfogadva" lesz
 *   2) az állat "orokbefogadva" lesz
 *   3) ugyanarra az állatra érkezett többi függő kérelem automatikusan "elutasitva" lesz
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();

/* ---------- Döntés feldolgozása ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    $kerelemId = (int)($_POST['kerelem_id'] ?? 0);
    $dontes    = $_POST['dontes'] ?? '';

    $lekerdezes = kapcsolat()->prepare(
        "SELECT id, allat_id FROM orokbefogadasi_kerelmek WHERE id = ? AND statusz = 'fuggoben'"
    );
    $lekerdezes->execute([$kerelemId]);
    $kerelem = $lekerdezes->fetch();

    if (!$kerelem || !in_array($dontes, ['elfogadva', 'elutasitva'], true)) {
        uzenet_beallit('hiba', 'A kérelem már el van bírálva, vagy nem létezik.');
    } else {
        $kapcsolat = kapcsolat();
        $kapcsolat->beginTransaction();
        try {
            $kapcsolat->prepare('UPDATE orokbefogadasi_kerelmek SET statusz = ?, elbiralva = NOW() WHERE id = ?')
                      ->execute([$dontes, $kerelemId]);

            if ($dontes === 'elfogadva') {
                $kapcsolat->prepare("UPDATE allatok SET statusz = 'orokbefogadva' WHERE id = ?")
                          ->execute([$kerelem['allat_id']]);
                $kapcsolat->prepare(
                    "UPDATE orokbefogadasi_kerelmek SET statusz = 'elutasitva', elbiralva = NOW()
                     WHERE allat_id = ? AND statusz = 'fuggoben'"
                )->execute([$kerelem['allat_id']]);
            }
            $kapcsolat->commit();
            uzenet_beallit('siker', $dontes === 'elfogadva'
                ? 'Kérelem elfogadva, az állat örökbefogadottra állítva.'
                : 'Kérelem elutasítva.');
        } catch (Throwable $kivetel) {
            $kapcsolat->rollBack();                           // hiba esetén semmi sem változik
            uzenet_beallit('hiba', 'Hiba történt, a módosítás nem mentődött.');
        }
    }
    atiranyit('admin/orokbefogadasi_kerelmek.php');
}

/* ---------- Lista (szűrhető állapot szerint, alapból a függők) ---------- */
$szuro = $_GET['statusz'] ?? 'fuggoben';
$engedett = ['fuggoben', 'elfogadva', 'elutasitva', 'visszavonva'];

$sql = 'SELECT k.*, f.nev AS felhasznalo_nev, f.email, a.nev AS allat_nev
        FROM orokbefogadasi_kerelmek k
        JOIN felhasznalok f ON f.id = k.felhasznalo_id
        JOIN allatok a ON a.id = k.allat_id';
$parameterek = [];
if (in_array($szuro, $engedett, true)) {
    $sql .= ' WHERE k.statusz = ?';
    $parameterek[] = $szuro;
}
$sql .= ' ORDER BY k.letrehozva DESC';
$lekerdezes = kapcsolat()->prepare($sql);
$lekerdezes->execute($parameterek);
$kerelmek = $lekerdezes->fetchAll();

$oldalCim = 'Admin – örökbefogadási kérelmek';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<h1>Örökbefogadási kérelmek</h1>

<form method="get" class="szuro-sor">
    <select name="statusz" onchange="this.form.submit()">
        <?php foreach ($engedett as $allapot): ?>
            <option value="<?= $allapot ?>" <?= $szuro === $allapot ? 'selected' : '' ?>><?= statusz_felirat($allapot) ?></option>
        <?php endforeach; ?>
        <option value="mind" <?= $szuro === 'mind' ? 'selected' : '' ?>>Mind</option>
    </select>
</form>

<?php if (!$kerelmek): ?>
    <div class="ures">Nincs ilyen kérelem.</div>
<?php endif; ?>

<?php foreach ($kerelmek as $kerelem): ?>
    <article class="doboz">
        <div class="sor-szet">
            <h3><?= kiir($kerelem['felhasznalo_nev']) ?> → <?= kiir($kerelem['allat_nev']) ?></h3>
            <span class="cimke cimke-<?= kiir($kerelem['statusz']) ?>"><?= statusz_felirat($kerelem['statusz']) ?></span>
        </div>
        <p class="halvany">
            <?= kiir($kerelem['email']) ?> · <?= kiir($kerelem['telefon']) ?> ·
            <?= kiir(date('Y.m.d. H:i', strtotime($kerelem['letrehozva']))) ?>
        </p>
        <p><?= nl2br(kiir($kerelem['uzenet'])) ?></p>

        <?php if ($kerelem['statusz'] === 'fuggoben'): ?>
            <div class="muveletek">
                <form method="post" data-megerosites="Elfogadod? Az állat örökbefogadott lesz, a többi kérelem elutasításra kerül.">
                    <?= csrf_mezo() ?>
                    <input type="hidden" name="kerelem_id" value="<?= $kerelem['id'] ?>">
                    <input type="hidden" name="dontes" value="elfogadva">
                    <button class="gomb gomb-kicsi" type="submit">✔ Elfogadás</button>
                </form>
                <form method="post" data-megerosites="Biztosan elutasítod?">
                    <?= csrf_mezo() ?>
                    <input type="hidden" name="kerelem_id" value="<?= $kerelem['id'] ?>">
                    <input type="hidden" name="dontes" value="elutasitva">
                    <button class="gomb gomb-kicsi gomb-veszely" type="submit">✖ Elutasítás</button>
                </form>
            </div>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
