<?php
/*
 * EGY ÁLLAT ADATLAPJA + ÖRÖKBEFOGADÁSI KÉRELEM BEADÁSA
 * Az állat azonosítóját a címsorból kapjuk: allat.php?id=5
 * Menete:
 *   1) lekérjük az állatot (ha nincs, 404-es oldal)
 *   2) ha az űrlapot elküldték (POST), ellenőrizzük az adatokat és elmentjük a kérelmet
 *   3) megjelenítjük az adatlapot és (ha lehet) az űrlapot
 */
require_once __DIR__ . '/includes/fuggvenyek.php';

$allatId = (int)($_GET['id'] ?? 0);

$lekerdezes = kapcsolat()->prepare('SELECT * FROM allatok WHERE id = ?');
$lekerdezes->execute([$allatId]);
$allat = $lekerdezes->fetch();

if (!$allat) {
    http_response_code(404);
    $oldalCim = 'Nem található';
    require __DIR__ . '/includes/fejlec.php';
    echo '<div class="ures">Ez az állat nem található. <a href="' . ALAP_URL . '/allatok.php">Vissza a listához</a></div>';
    require __DIR__ . '/includes/lablec.php';
    exit;
}

$felhasznalo = bejelentkezett_felhasznalo();
$hibak = [];

/* ---------- 2) Űrlap feldolgozása ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();
    belepes_kell();

    $uzenet  = trim($_POST['uzenet'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');

    if ($allat['statusz'] !== 'gazdat_keres') {
        $hibak[] = 'Ez az állat már nem örökbefogadható.';
    }
    if (mb_strlen($uzenet) < 20) {
        $hibak[] = 'Írj legalább 20 karakternyi bemutatkozást (hol laksz, volt-e már állatod, miért szeretnéd).';
    }
    if (!preg_match('/^\+?[0-9 \-\/]{7,20}$/', $telefon)) {
        $hibak[] = 'A telefonszám formátuma hibás (csak számok, szóköz, + és - lehet benne).';
    }

    // Ugyanarra az állatra nem lehet két függő kérelme ugyanannak a felhasználónak
    if (!$hibak) {
        $ellenorzes = kapcsolat()->prepare(
            "SELECT COUNT(*) FROM orokbefogadasi_kerelmek
             WHERE felhasznalo_id = ? AND allat_id = ? AND statusz = 'fuggoben'"
        );
        $ellenorzes->execute([$felhasznalo['id'], $allatId]);
        if ($ellenorzes->fetchColumn() > 0) {
            $hibak[] = 'Erre az állatra már van függőben lévő kérelmed.';
        }
    }

    // Ha minden rendben, mentés az adatbázisba
    if (!$hibak) {
        $mentes = kapcsolat()->prepare(
            'INSERT INTO orokbefogadasi_kerelmek (felhasznalo_id, allat_id, uzenet, telefon) VALUES (?, ?, ?, ?)'
        );
        $mentes->execute([$felhasznalo['id'], $allatId, $uzenet, $telefon]);
        uzenet_beallit('siker', 'A kérelmedet rögzítettük! Hamarosan elbíráljuk.');
        atiranyit('kerelmeim.php');
    }
}

/* ---------- 3) Van-e már függő kérelme a belépett felhasznalonak? ---------- */
$vanFuggoKerelem = false;
if ($felhasznalo) {
    $ellenorzes = kapcsolat()->prepare(
        "SELECT COUNT(*) FROM orokbefogadasi_kerelmek
         WHERE felhasznalo_id = ? AND allat_id = ? AND statusz = 'fuggoben'"
    );
    $ellenorzes->execute([$felhasznalo['id'], $allatId]);
    $vanFuggoKerelem = $ellenorzes->fetchColumn() > 0;
}

$oldalCim = $allat['nev'];
require __DIR__ . '/includes/fejlec.php';
?>
<p><a href="<?= ALAP_URL ?>/allatok.php">← Vissza az állatokhoz</a></p>

<div class="adatlap">
    <div><?= allat_kep_html($allat, 'adatlap-kep') ?></div>
    <div>
        <h1><?= kiir($allat['nev']) ?></h1>
        <p><span class="cimke cimke-<?= kiir($allat['statusz']) ?>"><?= statusz_felirat($allat['statusz']) ?></span></p>
        <table class="adat-tabla">
            <tr><th>Faj</th><td><?= kiir($allat['faj']) ?></td></tr>
            <tr><th>Kor</th><td><?= kor_felirat((int)$allat['kor']) ?></td></tr>
            <tr><th>Szín</th><td><?= kiir($allat['szin']) ?></td></tr>
        </table>
        <p><?= nl2br(kiir($allat['leiras'])) ?></p>
    </div>
</div>

<?php if ($allat['statusz'] === 'gazdat_keres'): ?>
    <section class="doboz">
        <h2>Örökbefogadási kérelem</h2>

        <?php if (!$felhasznalo): ?>
            <p><a href="<?= ALAP_URL ?>/belepes.php">Jelentkezz be</a> vagy
               <a href="<?= ALAP_URL ?>/regisztracio.php">regisztrálj</a>, hogy kérelmet adhass be.</p>

        <?php elseif ($vanFuggoKerelem): ?>
            <p class="uzenet uzenet-info">Erre az állatra már van beadott kérelmed. Az állapotát a
                <a href="<?= ALAP_URL ?>/kerelmeim.php">Kérelmeim</a> oldalon látod.</p>

        <?php else: ?>
            <?php foreach ($hibak as $hiba): ?><div class="uzenet uzenet-hiba"><?= kiir($hiba) ?></div><?php endforeach; ?>
            <form method="post" class="urlap" data-ellenorzes>
                <?= csrf_mezo() ?>
                <label>Telefonszám
                    <input type="tel" name="telefon" required value="<?= kiir($_POST['telefon'] ?? $felhasznalo['telefon'] ?? '') ?>" placeholder="+36 30 123 4567">
                </label>
                <label>Bemutatkozás (hol laksz, volt-e már állatod, miért szeretnéd?)
                    <textarea name="uzenet" rows="5" required minlength="20"><?= kiir($_POST['uzenet'] ?? '') ?></textarea>
                </label>
                <button class="gomb" type="submit">Kérelem beküldése</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/lablec.php'; ?>
