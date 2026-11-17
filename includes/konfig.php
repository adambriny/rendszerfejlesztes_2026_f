<?php
/*
 * KONFIGURÁCIÓ
 * Itt van minden beállítás egy helyen. Ha más gépen futtatod, csak ezt a fájlt kell átírni.
 */

// Adatbázis-kapcsolat adatai (XAMPP alapértelmezés: root felhasználó, üres jelszó)
const ADATBAZIS_HOST         = 'localhost';
const ADATBAZIS_NEV          = 'kisallatmenhely';
const ADATBAZIS_FELHASZNALO  = 'root';
const ADATBAZIS_JELSZO       = '';

// A htdocs-on belüli mappa neve. Ha átnevezed a mappát, ezt is írd át!
const ALAP_URL = '/kisallatmenhely';

// Ide kerülnek a feltöltött állatképek
const FELTOLTES_MAPPA = __DIR__ . '/../feltoltesek/';
