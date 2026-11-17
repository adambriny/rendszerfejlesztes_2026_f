-- =====================================================================
--  KISÁLLAT MENHELY – ADATBÁZIS
--  Importálás: phpMyAdmin → Importálás → ezt a fájlt kiválasztani.
--  (Az adatbázist a fájl maga hozza létre.)
--
--  Táblák:
--    felhasznalok               – regisztrált felhasználók és adminok
--    allatok                    – a menhely állatai (ezek látszanak az oldalon)
--    orokbefogadasi_kerelmek    – "szeretném örökbe fogadni X-et" kérelmek
--    allat_felvetel_kerelmek    – "új állatot szeretnék bejelenteni" kérelmek
-- =====================================================================

CREATE DATABASE IF NOT EXISTS kisallatmenhely
    CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci;
USE kisallatmenhely;

-- Újraimportálásnál a régi táblák törlése (a külső kulcsok miatt ideiglenesen kikapcsoljuk az ellenőrzést)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS allat_felvetel_kerelmek;
DROP TABLE IF EXISTS orokbefogadasi_kerelmek;
DROP TABLE IF EXISTS allatok;
DROP TABLE IF EXISTS felhasznalok;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1) Felhasználók
--    A jelszót nem szövegként, hanem titkosítva (jelszo_hash) tároljuk.
-- ---------------------------------------------------------------------
CREATE TABLE felhasznalok (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nev          VARCHAR(80)  NOT NULL,
    email        VARCHAR(120) NOT NULL,
    telefon      VARCHAR(20)  NULL,
    jelszo_hash  VARCHAR(255) NOT NULL,
    szerep       ENUM('felhasznalo', 'admin') NOT NULL DEFAULT 'felhasznalo',
    letrehozva   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY egyedi_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2) Állatok
-- ---------------------------------------------------------------------
CREATE TABLE allatok (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nev         VARCHAR(60)  NOT NULL,
    faj         VARCHAR(40)  NOT NULL,                 -- pl. kutya, macska, nyúl
    kor         TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- években
    szin        VARCHAR(40)  NOT NULL,
    leiras      TEXT         NULL,
    kep         VARCHAR(60)  NULL,                     -- a feltoltesek mappában lévő fájl neve
    statusz     ENUM('gazdat_keres', 'orokbefogadva') NOT NULL DEFAULT 'gazdat_keres',
    letrehozva  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3) Örökbefogadási kérelmek (felhasználó → állat)
--    ON DELETE CASCADE: ha a felhasználót vagy az állatot töröljük, a kérelem is törlődik.
-- ---------------------------------------------------------------------
CREATE TABLE orokbefogadasi_kerelmek (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    felhasznalo_id  INT UNSIGNED NOT NULL,
    allat_id        INT UNSIGNED NOT NULL,
    uzenet          TEXT         NOT NULL,
    telefon         VARCHAR(20)  NOT NULL,
    statusz         ENUM('fuggoben', 'elfogadva', 'elutasitva', 'visszavonva') NOT NULL DEFAULT 'fuggoben',
    letrehozva      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    elbiralva       DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_orokbef_felhasznalo FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalok (id) ON DELETE CASCADE,
    CONSTRAINT fk_orokbef_allat       FOREIGN KEY (allat_id)       REFERENCES allatok (id)       ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4) Állatbejelentések (felhasználó → új állat felvétele)
--    Elfogadáskor az admin felületen létrejön belőle egy sor az "allatok" táblában;
--    az allat_id mutatja, melyik lett belőle (ha az állatot később töröljük: NULL lesz).
-- ---------------------------------------------------------------------
CREATE TABLE allat_felvetel_kerelmek (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    felhasznalo_id  INT UNSIGNED NOT NULL,
    allat_nev       VARCHAR(60)  NOT NULL,
    faj             VARCHAR(40)  NOT NULL,
    kor             TINYINT UNSIGNED NOT NULL DEFAULT 0,
    szin            VARCHAR(40)  NOT NULL,
    leiras          TEXT         NOT NULL,
    kep             VARCHAR(60)  NULL,
    megjegyzes      TEXT         NULL,
    statusz         ENUM('fuggoben', 'elfogadva', 'elutasitva', 'visszavonva') NOT NULL DEFAULT 'fuggoben',
    allat_id        INT UNSIGNED NULL,
    letrehozva      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    elbiralva       DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_felvetel_felhasznalo FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalok (id) ON DELETE CASCADE,
    CONSTRAINT fk_felvetel_allat       FOREIGN KEY (allat_id)       REFERENCES allatok (id)       ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  TESZTADATOK
--  Admin:        admin@kisallatmenhely.hu  /  Admin123!
--  Felhasználó:  teszt@kisallatmenhely.hu  /  Teszt123!
--  Felhasználó:  anna@kisallatmenhely.hu   /  Teszt123!
-- =====================================================================
INSERT INTO felhasznalok (nev, email, telefon, jelszo_hash, szerep) VALUES
('Admin Adrienn', 'admin@kisallatmenhely.hu', '+36 30 111 2222', '$2b$10$8eXcCyZpjDLUgjuRr8/3s.0SgrvX/8xtB4K4zU3T/5IbU50NE2oqm', 'admin'),
('Teszt Elek',    'teszt@kisallatmenhely.hu', '+36 20 333 4444', '$2b$10$8eXcCyZpjDLUgjuRr8/3s.ALX9Ab/LOrtn.il4reG0m63R32Fz5oq', 'felhasznalo'),
('Kovács Anna',   'anna@kisallatmenhely.hu',  '+36 70 555 6666', '$2b$10$8eXcCyZpjDLUgjuRr8/3s.ALX9Ab/LOrtn.il4reG0m63R32Fz5oq', 'felhasznalo');

INSERT INTO allatok (nev, faj, kor, szin, leiras, statusz) VALUES
('Bodri',   'kutya',        3, 'barna',          'Barátságos, játékos keverék kutya. Jól kijön a gyerekekkel, szereti a hosszú sétákat.', 'gazdat_keres'),
('Morzsi',  'macska',       2, 'cirmos',         'Kedves, dorombolós kandúr. Beltéri életre való, nagyon szereti az ölt.', 'gazdat_keres'),
('Luna',    'kutya',        5, 'fekete-fehér',   'Nyugodt, jól nevelt border collie keverék. Gyakorlott gazdit keres, aki sokat mozog vele.', 'gazdat_keres'),
('Szőrmók', 'nyúl',         1, 'szürke',         'Aranyos, szelíd törpenyúl, kézből is eszik.', 'gazdat_keres'),
('Cica',    'macska',       0, 'fekete',         'Pár hónapos kiscica, még nagyon játékos. Oltva, féregtelenítve.', 'gazdat_keres'),
('Rex',     'kutya',        7, 'sárga',          'Idősebb, higgadt labrador keverék, tökéletes családi kedvenc.', 'gazdat_keres'),
('Kispipi', 'papagáj',      2, 'zöld-sárga',     'Hullámos papagáj, mondani is tud néhány szót. Kalitkával együtt adnánk.', 'gazdat_keres'),
('Pamacs',  'tengerimalac', 1, 'fehér-barna',    'Szelíd, csendes tengerimalac, párban is elvihető.', 'gazdat_keres'),
('Vackor',  'kutya',        4, 'szürke',         'Már megtalálta az új családját, nagyon boldog!', 'orokbefogadva'),
('Mici',    'macska',       6, 'fehér',          'Szelíd házimacska, tavaly talált új otthonra.', 'orokbefogadva');

-- Függő örökbefogadási kérelmek + egy már elbírált
INSERT INTO orokbefogadasi_kerelmek (felhasznalo_id, allat_id, uzenet, telefon, statusz, elbiralva) VALUES
(2, 1, 'Családi házban lakunk kerttel, két gyerekünk van. Régóta szeretnénk egy kutyát, korábban is volt már.', '+36 20 333 4444', 'fuggoben', NULL),
(3, 1, 'Egyedül élek, otthonról dolgozom, így sok időt tudnék Bodrival tölteni.', '+36 70 555 6666', 'fuggoben', NULL),
(3, 2, 'Lakásban élek, régóta macskát szeretnék. Morzsi nagyon aranyos!', '+36 70 555 6666', 'fuggoben', NULL),
(2, 9, 'Vackort szerettem volna örökbe fogadni, de láttam, hogy már elvitték.', '+36 20 333 4444', 'elutasitva', NOW());

-- Függő állatbejelentés
INSERT INTO allat_felvetel_kerelmek (felhasznalo_id, allat_nev, faj, kor, szin, leiras, megjegyzes, statusz) VALUES
(2, 'Foltos', 'macska', 1, 'fehér-fekete', 'A kertünkben találtam, nagyon barátságos, de nem tudjuk megtartani.', 'Szerintem nincs chipje, nem vittem még orvoshoz.', 'fuggoben');
