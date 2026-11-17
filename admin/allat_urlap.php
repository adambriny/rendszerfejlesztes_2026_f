<?php
/*
 * ADMIN – ÁLLAT FELVÉTELE / SZERKESZTÉSE
 * Ugyanaz az űrlap kétféle célra:
 *   - allat_urlap.php          → új állat felvétele (kérelem nélkül, közvetlenül)
 *   - allat_urlap.php?id=5     → meglévő állat szerkesztése
 */
require_once __DIR__ . '/../includes/fuggvenyek.php';
admin_kell();

$allatId = (int)($_GET['id'] ?? 0);

// Alapértékek új állathoz
$allat = ['nev' => '', 'faj' => '', 'kor' => '', 'szin' => '', 'leiras' => '', 'kep' => null, 'statusz' => 'gazdat_keres'];

// Szerkesztésnél betöltjük a meglévő adatokat
if ($allatId) {
    $lekerdezes = kapcsolat()->prepare('SELECT * FROM allatok WHERE id = ?');
    $lekerdezes->execute([$allatId]);
    $meglevo = $lekerdezes->fetch();
    if (!$meglevo) {
        http_response_code(404);
        die('Nincs ilyen állat.');
    }
    $allat = $meglevo;
}

$hibak = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();

    $bekert = [
        'nev'     => trim($_POST['nev'] ?? ''),
        'faj'     => trim($_POST['faj'] ?? ''),
        'kor'     => trim($_POST['kor'] ?? ''),
        'szin'    => trim($_POST['szin'] ?? ''),
        'leiras'  => trim($_POST['leiras'] ?? ''),
        'statusz' => $_POST['statusz'] ?? 'gazdat_keres',
    ];

    // --- ellenőrzések ---
    if ($bekert['nev'] === '' || mb_strlen($bekert['nev']) > 60) $hibak[] = 'A név kötelező (legfeljebb 60 karakter).';
    if ($bekert['faj'] === '' || mb_strlen($bekert['faj']) > 40) $hibak[] = 'A faj kötelező (legfeljebb 40 karakter).';
    if (!ctype_digit($bekert['kor']) || (int)$bekert['kor'] > 40) $hibak[] = 'A kor 0 és 40 közötti egész szám legyen.';
    if ($bekert['szin'] === '') $hibak[] = 'A szín kötelező.';
    if (!in_array($bekert['statusz'], ['gazdat_keres', 'orokbefogadva'], true)) $hibak[] = 'Érvénytelen állapot.';

    // --- kép ---
    $ujKep = '';
    if (!$hibak) {
        try {
            $ujKep = kep_mentese('kep');
        } catch (RuntimeException $kivetel) {
            $hibak[] = $kivetel->getMessage();
        }
    }

    // --- mentés ---
    if (!$hibak) {
        $kepNev = $allat['kep'];
        if ($ujKep !== '') {                    // új képet töltöttek fel → a régit töröljük
            kep_torlese($allat['kep']);
            $kepNev = $ujKep;
        }

        $ertekek = [
            $bekert['nev'], $bekert['faj'], (int)$bekert['kor'], $bekert['szin'],
            $bekert['leiras'], $kepNev, $bekert['statusz'],
        ];

        if ($allatId) {
            $ertekek[] = $allatId;
            kapcsolat()->prepare(
                'UPDATE allatok SET nev = ?, faj = ?, kor = ?, szin = ?, leiras = ?, kep = ?, statusz = ? WHERE id = ?'
            )->execute($ertekek);
            uzenet_beallit('siker', 'Az állat adatai frissítve.');
        } else {
            kapcsolat()->prepare(
                'INSERT INTO allatok (nev, faj, kor, szin, leiras, kep, statusz) VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute($ertekek);
            uzenet_beallit('siker', 'Az új állat felvéve.');
        }
        atiranyit('admin/allatok.php');
    }

    // Hiba esetén a beírt adatok maradjanak az űrlapban
    $allat = array_merge($allat, $bekert);
}

$oldalCim = $allatId ? 'Állat szerkesztése' : 'Új állat';
require __DIR__ . '/../includes/fejlec.php';
require __DIR__ . '/_menu.php';
?>
<h1><?= $allatId ? 'Állat szerkesztése' : 'Új állat felvétele' ?></h1>
<?php foreach ($hibak as $hiba): ?><div class="uzenet uzenet-hiba"><?= kiir($hiba) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="urlap doboz" data-ellenorzes>
    <?= csrf_mezo() ?>
    <div class="urlap-racs">
        <label>Név <input type="text" name="nev" required maxlength="60" value="<?= kiir($allat['nev']) ?>"></label>
        <label>Faj <input type="text" name="faj" required maxlength="40" value="<?= kiir($allat['faj']) ?>"></label>
        <label>Kor (év) <input type="number" name="kor" required min="0" max="40" value="<?= kiir($allat['kor']) ?>"></label>
        <label>Szín <input type="text" name="szin" required maxlength="40" value="<?= kiir($allat['szin']) ?>"></label>
        <label>Állapot
            <select name="statusz">
                <option value="gazdat_keres" <?= $allat['statusz'] === 'gazdat_keres' ? 'selected' : '' ?>>Gazdit keres</option>
                <option value="orokbefogadva" <?= $allat['statusz'] === 'orokbefogadva' ? 'selected' : '' ?>>Örökbefogadva</option>
            </select>
        </label>
    </div>
    <label>Leírás <textarea name="leiras" rows="5"><?= kiir($allat['leiras']) ?></textarea></label>
    <label>Fénykép (JPG/PNG/WEBP, max. 2 MB)
        <input type="file" name="kep" id="kep-bemenet" accept="image/jpeg,image/png,image/webp">
    </label>

    <?php if ($allat['kep']): ?>
        <p class="halvany">Jelenlegi kép (új feltöltésével lecserélődik):</p>
        <img class="elonezet" id="kep-elonezet" src="<?= ALAP_URL ?>/feltoltesek/<?= kiir($allat['kep']) ?>" alt="">
    <?php else: ?>
        <img class="elonezet" id="kep-elonezet" alt="" hidden>
    <?php endif; ?>

    <div class="sor-szet">
        <a class="gomb gomb-ures" href="<?= ALAP_URL ?>/admin/allatok.php">Mégse</a>
        <button class="gomb" type="submit">Mentés</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/lablec.php'; ?>
