<?php
/*
 * BELÉPÉS
 * Az e-mail alapján megkeressük a felhasználót, majd password_verify()-val összevetjük
 * a beírt jelszót az adatbázisban tárolt hash-sel.
 */
require_once __DIR__ . '/includes/fuggvenyek.php';
if (bejelentkezett_felhasznalo()) atiranyit('index.php');

$hiba = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_ellenorzes();

    $email  = trim($_POST['email'] ?? '');
    $jelszo = $_POST['jelszo'] ?? '';

    $lekerdezes = kapcsolat()->prepare('SELECT id, jelszo_hash FROM felhasznalok WHERE email = ?');
    $lekerdezes->execute([$email]);
    $talalat = $lekerdezes->fetch();

    if ($talalat && password_verify($jelszo, $talalat['jelszo_hash'])) {
        session_regenerate_id(true);
        $_SESSION['felhasznalo_id'] = (int)$talalat['id'];   // ettől számít "bejelentkezettnek"

        // Ha egy védett oldalról dobtak ide, oda irányítjuk vissza
        $visszaOldal = $_SESSION['vissza_oldal'] ?? '';
        unset($_SESSION['vissza_oldal']);
        if ($visszaOldal && strpos($visszaOldal, ALAP_URL . '/') === 0) {
            header('Location: ' . $visszaOldal);
            exit;
        }
        atiranyit('index.php');
    }
    // Szándékosan nem árulja el, hogy az e-mail vagy a jelszó volt-e rossz
    $hiba = 'Hibás e-mail cím vagy jelszó.';
}

$oldalCim = 'Belépés';
require __DIR__ . '/includes/fejlec.php';
?>
<div class="belepo-doboz">
    <h1>Belépés</h1>
    <?php if ($hiba): ?><div class="uzenet uzenet-hiba"><?= kiir($hiba) ?></div><?php endif; ?>

    <form method="post" class="urlap">
        <?= csrf_mezo() ?>
        <label>E-mail <input type="email" name="email" required value="<?= kiir($email) ?>"></label>
        <label>Jelszó <input type="password" name="jelszo" required></label>
        <button class="gomb" type="submit">Belépek</button>
    </form>
    <p class="halvany">Nincs még fiókod? <a href="<?= ALAP_URL ?>/regisztracio.php">Regisztráció</a></p>
</div>
<?php require __DIR__ . '/includes/lablec.php'; ?>
