<?php
/*
 * REGISZTRÁCIÓ
 * Az űrlap adatait ellenőrizzük, a jelszót password_hash()-sel titkosítva (hash) tároljuk –
 * az eredeti jelszót soha nem mentjük az adatbázisba.
 */
require_once __DIR__ . '/includes/fuggvenyek.php';
if (bejelentkezett_felhasznalo()) atiranyit('index.php');

$hibak = [];
$nev = $email = $telefon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();

    $nev     = trim($_POST['nev'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $jelszo  = $_POST['jelszo'] ?? '';
    $jelszoUjra = $_POST['jelszo_ujra'] ?? '';

    // --- ellenőrzések ---
    if (mb_strlen($nev) < 2 || mb_strlen($nev) > 80) {
        $hibak[] = 'A név 2 és 80 karakter között legyen.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hibak[] = 'Érvénytelen e-mail cím.';
    }
    if ($telefon !== '' && !preg_match('/^\+?[0-9 \-\/]{7,20}$/', $telefon)) {
        $hibak[] = 'A telefonszám formátuma hibás.';
    }
    if (strlen($jelszo) < 8 || !preg_match('/[A-Za-z]/', $jelszo) || !preg_match('/[0-9]/', $jelszo)) {
        $hibak[] = 'A jelszó legalább 8 karakter legyen, és tartalmazzon betűt és számot is.';
    }
    if ($jelszo !== $jelszoUjra) {
        $hibak[] = 'A két jelszó nem egyezik.';
    }

    // Egy e-mail címmel csak egy fiók lehet
    if (!$hibak) {
        $ellenorzes = kapcsolat()->prepare('SELECT COUNT(*) FROM felhasznalok WHERE email = ?');
        $ellenorzes->execute([$email]);
        if ($ellenorzes->fetchColumn() > 0) {
            $hibak[] = 'Ezzel az e-mail címmel már regisztráltak.';
        }
    }

    // --- mentés és automatikus belépés ---
    if (!$hibak) {
        $mentes = kapcsolat()->prepare(
            'INSERT INTO felhasznalok (nev, email, telefon, jelszo_hash) VALUES (?, ?, ?, ?)'
        );
        $mentes->execute([
            $nev,
            $email,
            $telefon !== '' ? $telefon : null,
            password_hash($jelszo, PASSWORD_DEFAULT),
        ]);

        session_regenerate_id(true);                         // új munkamenet-azonosító (session fixation ellen)
        $_SESSION['felhasznalo_id'] = (int)kapcsolat()->lastInsertId();
        uzenet_beallit('siker', 'Sikeres regisztráció, üdvözlünk!');
        atiranyit('index.php');
    }
}

$oldalCim = 'Regisztráció';
require __DIR__ . '/includes/fejlec.php';
?>
<div class="belepo-doboz">
    <h1>Regisztráció</h1>
    <?php foreach ($hibak as $hiba): ?><div class="uzenet uzenet-hiba"><?= kiir($hiba) ?></div><?php endforeach; ?>

    <form method="post" class="urlap" data-ellenorzes>
        <?= csrf_mezo() ?>
        <label>Név <input type="text" name="nev" required minlength="2" maxlength="80" value="<?= kiir($nev) ?>"></label>
        <label>E-mail <input type="email" name="email" required value="<?= kiir($email) ?>"></label>
        <label>Telefonszám (nem kötelező) <input type="tel" name="telefon" value="<?= kiir($telefon) ?>"></label>
        <label>Jelszó <input type="password" name="jelszo" required minlength="8"></label>
        <label>Jelszó újra <input type="password" name="jelszo_ujra" required minlength="8"></label>
        <button class="gomb" type="submit">Regisztrálok</button>
    </form>
    <p class="halvany">Már van fiókod? <a href="<?= ALAP_URL ?>/belepes.php">Belépés</a></p>
</div>
<?php require __DIR__ . '/includes/lablec.php'; ?>
