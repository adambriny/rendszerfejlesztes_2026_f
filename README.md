# Kisállat örökbefogadás weboldal

## F csoport
A csoportban 2 fő vesz részt: 

• [Gazdik Bálint](https://github.com/just4jase) – Frontendért felelős fejlesztő

• [Szabó Patrícia](https://github.com/SzaboPatricia) – Backendért és adatbázisért felelős fejlesztő

### A téma és a tervezés (wip)

A téma egy *kisállat örökbefogadásáról* szóló weboldal lesz. A terv, hogy ez a weboldal **tiszta és áttekinthető** legyen, míg **egyszerű és megérthető funkciót** kínál a felhasználó számára.

## A fejlesztők és feladataik (wip)

A fejlesztők feladatai elkülönbözhetnek, de minden fejlesztő a projektmunkában próbál a **kommunikáció és az összhangoltság** fenntartásához, illetve a feladat **egyszerűsítés és közös megbeszélés** odafigyeléséhez.

## SMART projektcél

**S (Specific):** Egy olyan weboldalt hozunk létre, ahol a látogatók böngészhetik az örökbefogadható állatokat (szűréssel), az adminisztrátorok pedig új állatokat tölthetnek fel, módosíthatják vagy törölhetik azokat (CRUD műveletek).

**M (Measurable):** A projekt elkészültét a GitHubon definiált 4 fő mérföldkő és a hozzájuk tartozó összesen kb. 15-20 lezárt Issue jelzi. A kód 100%-ban működőképesen fut majd a lokális környezetben.

**A (Achievable):** A funkciók körét szűken tartjuk, a feladatokat igyekszünk egyenlően elosztani kettőnk között, mindketten egyenlő mennyiségű munkát fektetve bele.

**R (Relevant):** Minden fejlesztési feladat közvetlenül a tiszta dizájnt és az egyszerű kezelhetőséget támogatja. Nem fejlesztünk feleslegesen extra funkciókat.

**T (Time-bound):** A fejlesztést 1 hónap alatt végezzük el, mindegyik héten egy-egy kész mérföldkővel.

## Főbb szerveroldali funkciók

**Adatbázis sématerv és modellek létrehozása:** Felhasználók (örökbefogadók, menhelyi adminok) és kisállatok (név, faj, kor, leírás, státusz) adatstruktúrájának  implementálása a backend oldalon történik.

**Statikus nyitóoldal és reszponzív navigáció:** A weboldal főoldala bemutatja a platform célját, tartalmaz egy hős (hero) szekciót és egy működő menüt (Asztali és Mobil nézetben is).

**Kisállat-katalógus és dinamikus szűrés:** Egy olyan aloldal létrehozása, ahol az összes örökbefogadható állat megjelenik kártya (Card) formátumban. A felhasználónak tudnia kell szűrni faj (kutya/macska) és kor szerint is.

**Örökbefogadási jelentkezési űrlap és validáció:** Egy adott állat adatlapjáról elérhető űrlap, ahol az örökbefogadó megadhatja az adatait (név, telefon, motivációs leírás). Az űrlap nem küldhető el üres mezőkkel vagy hibás e-mail formátummal (kliens és szerveroldali validáció). A sikeres jelentkezés mentésre kerül az adatbázisba 'Függőben' státusszal.

