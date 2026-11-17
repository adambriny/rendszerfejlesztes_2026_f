<?php
/*
 * ADATBÁZIS-KAPCSOLAT
 * A kapcsolat() függvényt bárhol meghívhatjuk, mindig ugyanazt a kapcsolatot adja vissza
 * (a "static" miatt csak az első híváskor jön létre).
 * PDO-t használunk: ez védi az alkalmazást az SQL-injekció ellen, ha "prepared statement"-et írunk.
 */
require_once __DIR__ . '/konfig.php';

function kapcsolat(): PDO
{
    static $kapcsolat = null;

    if ($kapcsolat === null) {
        try {
            $kapcsolat = new PDO(
                'mysql:host=' . ADATBAZIS_HOST . ';dbname=' . ADATBAZIS_NEV . ';charset=utf8mb4',
                ADATBAZIS_FELHASZNALO,
                ADATBAZIS_JELSZO,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // hiba esetén kivételt dob
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // soronként asszociatív tömb
                    PDO::ATTR_EMULATE_PREPARES   => false,                    // valódi prepared statement
                ]
            );
        } catch (PDOException $hiba) {
            http_response_code(500);
            die('Nem sikerült kapcsolódni az adatbázishoz. Fut a MySQL az XAMPP-ban, és beimportáltad a kisallatmenhely.sql fájlt?');
        }
    }
    return $kapcsolat;
}
