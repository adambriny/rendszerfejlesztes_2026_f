<?php
/* KILÉPÉS: a munkamenet törlése, majd vissza a főoldalra. */
require_once __DIR__ . '/includes/fuggvenyek.php';

$_SESSION = [];
session_destroy();
session_start();                                   // új, üres munkamenet az üzenet kiírásához
uzenet_beallit('info', 'Sikeresen kijelentkeztél.');
atiranyit('index.php');
