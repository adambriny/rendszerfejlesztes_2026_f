<?php
/*
 * SEGÉDFÜGGVÉNYEK
 * Ezt a fájlt töltjük be minden oldal elején. Három dolgot csinál:
 *   1) elindítja a munkamenetet (session) – így "emlékszik" a szerver, ki van bejelentkezve
 *   2) betölti az adatbázis-kapcsolatot
 *   3) tartalmazza a gyakran használt kis függvényeket
 */
require_once __DIR__ . '/adatbazis.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============ 1. Általános segédek ============ */

/** Kiírás előtt minden szöveget ezen át engedünk (XSS támadás ellen: a < > karaktereket ártalmatlanítja). */
function kiir($szoveg): string
{
    return htmlspecialchars((string)$szoveg, ENT_QUOTES, 'UTF-8');
}

/** Átirányít egy másik oldalra az alkalmazáson belül, pl. atiranyit('belepes.php'); */
function atiranyit(string $oldal): never
{
    header('Location: ' . ALAP_URL . '/' . ltrim($oldal, '/'));
    exit;
}

/* ============ 2. Üzenetek (zöld/piros sáv az oldal tetején) ============ */

/** Elmenti az üzenetet a munkamenetbe; a következő oldalbetöltéskor jelenik meg. */
function uzenet_beallit(string $tipus, string $szoveg): void
{
    // $tipus: 'siker', 'hiba' vagy 'info'
    $_SESSION['uzenetek'][] = ['tipus' => $tipus, 'szoveg' => $szoveg];
}

/** Visszaadja és törli az eltárolt üzeneteket (egyszer jelennek meg). */
function uzenetek_lekerese(): array
{
    $uzenetek = $_SESSION['uzenetek'] ?? [];
    unset($_SESSION['uzenetek']);
    return $uzenetek;
}

/* ============ 3. CSRF-védelem ============ */
/*
 * Minden POST űrlapba beleteszünk egy titkos, véletlen kódot. Beküldéskor ellenőrizzük, hogy
 * egyezik-e a munkamenetben tárolttal. Így egy idegen oldal nem tud a nevünkben űrlapot beküldeni.
 */

function csrf_kod(): string
{
    if (empty($_SESSION['csrf_kod'])) {
        $_SESSION['csrf_kod'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_kod'];
}

/** Ezt a függvényt kell minden <form> belsejébe beírni: <?= csrf_mezo() ?> */
function csrf_mezo(): string
{
    return '<input type="hidden" name="csrf_kod" value="' . kiir(csrf_kod()) . '">';
}

/** Minden POST feldolgozás elején meg kell hívni. */
function csrf_ellenorzes(): void
{
    $jo = isset($_POST['csrf_kod']) && hash_equals($_SESSION['csrf_kod'] ?? '', $_POST['csrf_kod']);
    if (!$jo) {
        http_response_code(400);
        die('Érvénytelen kérés (CSRF). Menj vissza az előző oldalra, és próbáld újra.');
    }
}

/* ============ 4. Bejelentkezés és jogosultság ============ */

/** Visszaadja a bejelentkezett felhasználó adatait, vagy null-t, ha senki sincs belépve. */
function bejelentkezett_felhasznalo(): ?array
{
    static $felhasznalo = false;               // egy oldalbetöltés alatt csak egyszer kérdezzük le

    if ($felhasznalo === false) {
        $felhasznalo = null;
        if (!empty($_SESSION['felhasznalo_id'])) {
            $lekerdezes = kapcsolat()->prepare(
                'SELECT id, nev, email, telefon, szerep FROM felhasznalok WHERE id = ?'
            );
            $lekerdezes->execute([$_SESSION['felhasznalo_id']]);
            $felhasznalo = $lekerdezes->fetch() ?: null;
        }
    }
    return $felhasznalo;
}

function admin_e(): bool
{
    $felhasznalo = bejelentkezett_felhasznalo();
    return $felhasznalo !== null && $felhasznalo['szerep'] === 'admin';
}

/** Olyan oldal tetejére kell, amihez bejelentkezés szükséges. */
function belepes_kell(): void
{
    if (!bejelentkezett_felhasznalo()) {
        uzenet_beallit('hiba', 'Ehhez az oldalhoz be kell jelentkezned.');
        $_SESSION['vissza_oldal'] = $_SERVER['REQUEST_URI'];   // belépés után ide irányítjuk vissza
        atiranyit('belepes.php');
    }
}

/** Olyan oldal tetejére kell, amit csak admin látogathat. */
function admin_kell(): void
{
    belepes_kell();
    if (!admin_e()) {
        http_response_code(403);
        die('Nincs jogosultságod az oldal megtekintéséhez.');
    }
}

/* ============ 5. Megjelenítési segédek ============ */

/** Az adatbázisban tárolt állapotkódot olvasható felirattá alakítja. */
function statusz_felirat(string $statusz): string
{
    $feliratok = [
        'gazdat_keres'  => 'Gazdit keres',
        'orokbefogadva' => 'Örökbefogadva',
        'fuggoben'      => 'Elbírálás alatt',
        'elfogadva'     => 'Elfogadva',
        'elutasitva'    => 'Elutasítva',
        'visszavonva'   => 'Visszavonva',
    ];
    return $feliratok[$statusz] ?? $statusz;
}

function kor_felirat(int $kor): string
{
    return $kor < 1 ? '1 évnél fiatalabb' : $kor . ' éves';
}

/* ============ 6. Képfeltöltés ============ */

/**
 * Elmenti a feltöltött képet a feltoltesek mappába.
 * Visszatérés: a fájl új neve, vagy '' ha nem választottak képet. Hiba esetén kivételt dob.
 */
function kep_mentese(string $mezoNev): string
{
    if (empty($_FILES[$mezoNev]) || $_FILES[$mezoNev]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';                                              // nem töltöttek fel semmit – ez nem hiba
    }
    $fajl = $_FILES[$mezoNev];

    if ($fajl['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('A képfeltöltés nem sikerült.');
    }
    if ($fajl['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('A kép legfeljebb 2 MB lehet.');
    }

    // A fájl TÉNYLEGES típusát ellenőrizzük (a kiterjesztés hamisítható)
    $tipus = (new finfo(FILEINFO_MIME_TYPE))->file($fajl['tmp_name']);
    $kiterjesztes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$tipus] ?? null;
    if ($kiterjesztes === null) {
        throw new RuntimeException('Csak JPG, PNG vagy WEBP kép tölthető fel.');
    }

    // Véletlen fájlnév, hogy ne ütközzön és ne lehessen kitalálni
    $ujNev = bin2hex(random_bytes(8)) . '.' . $kiterjesztes;
    if (!move_uploaded_file($fajl['tmp_name'], FELTOLTES_MAPPA . $ujNev)) {
        throw new RuntimeException('A kép mentése nem sikerült (a feltoltesek mappa írható?).');
    }
    return $ujNev;
}

function kep_torlese(?string $fajlnev): void
{
    if ($fajlnev && preg_match('/^[a-f0-9]+\.(jpg|png|webp)$/', $fajlnev) && is_file(FELTOLTES_MAPPA . $fajlnev)) {
        unlink(FELTOLTES_MAPPA . $fajlnev);
    }
}

/** Az állat képét adja vissza HTML-ként; ha nincs kép, egy mancs-ikonos helyettesítőt. */
function allat_kep_html(array $allat, string $osztaly = 'allat-kep'): string
{
    $van = !empty($allat['kep']) && is_file(FELTOLTES_MAPPA . $allat['kep']);
    if ($van) {
        return '<img class="' . $osztaly . '" src="' . ALAP_URL . '/feltoltesek/' . kiir($allat['kep']) . '" alt="' . kiir($allat['nev']) . '">';
    }
    return '<div class="' . $osztaly . ' nincs-kep">🐾</div>';
}
