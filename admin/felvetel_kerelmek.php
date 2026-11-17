<?php
/*
 * ADMIN – ÁLLATBEJELENTÉSEK (felvételi kérelmek) ELBÍRÁLÁSA
 * Elfogadáskor (tranzakcióban):
 *   1) a kérelem adataiból új sor jön létre az "allatok" táblában (innentől látszik az oldalon)
 *   2) a kérelem "elfogadva" lesz, és eltároljuk, melyik állat lett belőle (allat_id)
 * Elutasításkor a feltöltött képet töröljük a szerverről.
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    $kerelemId = (int)($_POST['kerelem_id'] ?? 0);
    $dontes    = $_POST['dontes'] ?? '';

    $lekerdezes = kapcsolat()->prepare("SELECT * FROM allat_felvetel_kerelmek WHERE id = ? AND statusz = 'fuggoben'");
    $lekerdezes->execute([$kerelemId]);
    $kerelem = $lekerdezes->fetch();

    if (!$kerelem || !in_array($dontes, ['elfogadva', 'elutasitva'], true)) {
        uzenet_beallit('hiba', 'A bejelentés már el van bírálva, vagy nem létezik.');
    } else {
        $kapcsolat = kapcsolat();
        $kapcsolat->beginTransaction();
        try {
            if ($dontes === 'elfogadva') {
                $kapcsolat->prepare(
                    "INSERT INTO allatok (nev, faj, kor, szin, leiras, kep, statusz)
                     VALUES (?, ?, ?, ?, ?, ?, 'gazdat_keres')"
                )->execute([
                    $kerelem['allat_nev'], $kerelem['faj'], $kerelem['kor'],
                    $kerelem['szin'], $kerelem['leiras'], $kerelem['kep'],
                ]);
                $ujAllatId = (int)$kapcsolat->lastInsertId();

                $kapcsolat->prepare(
                    "UPDATE allat_felvetel_kerelmek SET statusz = 'elfogadva', elbiralva = NOW(), allat_id = ? WHERE id = ?"
                )->execute([$ujAllatId, $kerelemId]);
            } else {
                $kapcsolat->prepare(
                    "UPDATE allat_felvetel_kerelmek SET statusz = 'elutasitva', elbiralva = NOW() WHERE id = ?"
                )->execute([$kerelemId]);
            }
            $kapcsolat->commit();

            if ($dontes === 'elutasitva') kep_torlese($kerelem['kep']);
            uzenet_beallit('siker', $dontes === 'elfogadva'
                ? 'Bejelentés elfogadva, az állat felkerült az oldalra.'
                : 'Bejelentés elutasítva.');
        } catch (Throwable $kivetel) {
            $kapcsolat->rollBack();
            uzenet_beallit('hiba', 'Hiba történt, a módosítás nem mentődött.');
        }
    }
    atiranyit('admin/felvetel_kerelmek.php');
}

$szuro = $_GET['statusz'] ?? 'fuggoben';
$engedett = ['fuggoben', 'elfogadva', 'elutasitva', 'visszavonva'];

$sql = 'SELECT k.*, f.nev AS felhasznalo_nev, f.email
        FROM allat_felvetel_kerelmek k JOIN felhasznalok f ON f.id = k.felhasznalo_id';
$parameterek = [];
if (in_array($szuro, $engedett, true)) {
    $sql .= ' WHERE k.statusz = ?';
    $parameterek[] = $szuro;
}
$sql .= ' ORDER BY k.letrehozva DESC';
$lekerdezes = kapcsolat()->prepare($sql);
$lekerdezes->execute($parameterek);
$kerelmek = $lekerdezes->fetchAll();

$oldalCim = 'Admin – állatbejelentések';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<h1>Állatbejelentések</h1>

<form method="get" class="szuro-sor">
    <select name="statusz" onchange="this.form.submit()">
        <?php foreach ($engedett as $allapot): ?>
            <option value="<?= $allapot ?>" <?= $szuro === $allapot ? 'selected' : '' ?>><?= statusz_felirat($allapot) ?></option>
        <?php endforeach; ?>
        <option value="mind" <?= $szuro === 'mind' ? 'selected' : '' ?>>Mind</option>
    </select>
</form>

<?php if (!$kerelmek): ?>
    <div class="ures">Nincs ilyen bejelentés.</div>
<?php endif; ?>

<?php foreach ($kerelmek as $kerelem): ?>
    <article class="doboz felvetel-kartya">
        <?= allat_kep_html(['kep' => $kerelem['kep'], 'nev' => $kerelem['allat_nev']], 'kozepes-kep') ?>
        <div>
            <div class="sor-szet">
                <h3><?= kiir($kerelem['allat_nev']) ?> <span class="halvany">(<?= kiir($kerelem['faj']) ?>)</span></h3>
                <span class="cimke cimke-<?= kiir($kerelem['statusz']) ?>"><?= statusz_felirat($kerelem['statusz']) ?></span>
            </div>
            <p class="halvany">
                Bejelentő: <?= kiir($kerelem['felhasznalo_nev']) ?> (<?= kiir($kerelem['email']) ?>) ·
                <?= kiir(date('Y.m.d. H:i', strtotime($kerelem['letrehozva']))) ?>
            </p>
            <p><strong>Kor:</strong> <?= kor_felirat((int)$kerelem['kor']) ?> · <strong>Szín:</strong> <?= kiir($kerelem['szin']) ?></p>
            <p><?= nl2br(kiir($kerelem['leiras'])) ?></p>
            <?php if ($kerelem['megjegyzes']): ?>
                <p class="halvany">Megjegyzés: <?= nl2br(kiir($kerelem['megjegyzes'])) ?></p>
            <?php endif; ?>

            <?php if ($kerelem['statusz'] === 'fuggoben'): ?>
                <div class="muveletek">
                    <form method="post" data-megerosites="Elfogadod? Az állat felkerül az oldalra.">
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
        </div>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
