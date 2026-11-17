<?php
/*
 * ÁLLAT BEJELENTÉSE (felvételi kérelem)
 * A bejelentkezett felhasználó itt jelentheti be, hogy új állatot szeretne a menhelyre juttatni
 * (talált egy gazdátlan állatot, vagy nem tudja tovább tartani). A kérelem az admin elé kerül:
 * ha elfogadja, az állat bekerül az "allatok" táblába és megjelenik az oldalon.
 */
require_once __DIR__ . '/includes/fuggvenyek.php';
belepes_kell();
$felhasznalo = bejelentkezett_felhasznalo();

$hibak = [];
$adatok = ['allat_nev' => '', 'faj' => '', 'kor' => '', 'szin' => '', 'leiras' => '', 'megjegyzes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();

    foreach ($adatok as $mezo => $ertek) {
        $adatok[$mezo] = trim($_POST[$mezo] ?? '');
    }

    // --- ellenőrzések ---
    if ($adatok['allat_nev'] === '' || mb_strlen($adatok['allat_nev']) > 60) {
        $hibak[] = 'Az állat neve kötelező (legfeljebb 60 karakter). Ha nem tudod, írd be: "Ismeretlen".';
    }
    if ($adatok['faj'] === '' || mb_strlen($adatok['faj']) > 40) {
        $hibak[] = 'A faj megadása kötelező (pl. kutya, macska, nyúl).';
    }
    if (!ctype_digit($adatok['kor']) || (int)$adatok['kor'] > 40) {
        $hibak[] = 'A kor 0 és 40 közötti egész szám legyen (években).';
    }
    if ($adatok['szin'] === '') {
        $hibak[] = 'A szín megadása kötelező.';
    }
    if (mb_strlen($adatok['leiras']) < 10) {
        $hibak[] = 'Írj egy rövid leírást az állatról (legalább 10 karakter).';
    }

    // --- kép feltöltése (nem kötelező) ---
    $kepNev = '';
    if (!$hibak) {
        try {
            $kepNev = kep_mentese('kep');
        } catch (RuntimeException $kivetel) {
            $hibak[] = $kivetel->getMessage();
        }
    }

    // --- mentés ---
    if (!$hibak) {
        $mentes = kapcsolat()->prepare(
            'INSERT INTO allat_felvetel_kerelmek
                (felhasznalo_id, allat_nev, faj, kor, szin, leiras, kep, megjegyzes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $mentes->execute([
            $felhasznalo['id'], $adatok['allat_nev'], $adatok['faj'], (int)$adatok['kor'],
            $adatok['szin'], $adatok['leiras'], $kepNev !== '' ? $kepNev : null, $adatok['megjegyzes'],
        ]);
        uzenet_beallit('siker', 'A bejelentést rögzítettük, az admin hamarosan elbírálja.');
        atiranyit('kerelmeim.php');
    }
}

$oldalCim = 'Állat bejelentése';
require __DIR__ . '/includes/fejlec.php';
?>
<h1>Állat bejelentése a menhelynek</h1>
<p class="halvany">Töltsd ki az adatokat. Ha az admin elfogadja a kérelmet, az állat megjelenik az oldalon.</p>

<?php foreach ($hibak as $hiba): ?><div class="uzenet uzenet-hiba"><?= kiir($hiba) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="urlap doboz" data-ellenorzes>
    <?= csrf_mezo() ?>
    <div class="urlap-racs">
        <label>Az állat neve <input type="text" name="allat_nev" required maxlength="60" value="<?= kiir($adatok['allat_nev']) ?>"></label>
        <label>Faj <input type="text" name="faj" required maxlength="40" placeholder="pl. kutya, macska" value="<?= kiir($adatok['faj']) ?>"></label>
        <label>Kor (év) <input type="number" name="kor" required min="0" max="40" value="<?= kiir($adatok['kor']) ?>"></label>
        <label>Szín <input type="text" name="szin" required maxlength="40" value="<?= kiir($adatok['szin']) ?>"></label>
    </div>
    <label>Leírás (jellem, egészség, hol találtad stb.)
        <textarea name="leiras" rows="4" required minlength="10"><?= kiir($adatok['leiras']) ?></textarea>
    </label>
    <label>Megjegyzés az adminnak (nem kötelező)
        <textarea name="megjegyzes" rows="2"><?= kiir($adatok['megjegyzes']) ?></textarea>
    </label>
    <label>Fénykép (JPG/PNG/WEBP, max. 2 MB, nem kötelező)
        <input type="file" name="kep" id="kep-bemenet" accept="image/jpeg,image/png,image/webp">
    </label>
    <img class="elonezet" id="kep-elonezet" alt="" hidden>
    <button class="gomb" type="submit">Bejelentés elküldése</button>
</form>
<?php require __DIR__ . '/includes/lablec.php'; ?>
