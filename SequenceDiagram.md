#Sequence Diagram

```mermaid
sequenceDiagram
        actor F as Felhasználó
        participant S as Szerver
        participant A as Adatbázis

        Note over F,A: Felhasználó regisztrál
        F->>S: Adatok beírása
        S->>S: Adatok ellenőrzése
        alt Nem megfelelő regisztráció
        S-->>F: "A regisztráció helytelen"
        else Regisztrációs adatok elfogadva
        S->>A: Adatok tárolása
        A-->>S: Adattárolás igazolva
        S-->>F: "Helyes regisztráció"
        end
        Note over F,A: Felhasználó bejelentkezik
        F->>S: Bejelentkezés

        S->>A: Adatok összehasonlítása
        alt Hibás bejelentkezés
        A-->>S: Helytelen adatok
        S-->>F: "Helytelen adatok"

        else Helyes bejelentkezés
        A-->>S: Helyes adatok
        S-->>F: Weboldal betöltése
        end

        Note over F,A: Jelentkezés
        F->>S: Jelentkezés örökbefogadáshoz
        S->>A: Kisállat státusz ellenőrzése
        alt Adopted
        A-->>S: A kisállatot már örökbefogadták
        S-->>F: "Nem jelentkezhetsz ennek a kisállat örökbefogadásához"
        else Available
        A-->>S: Jelentkezés elfogadása
        S->>A: Pending státusz
        S-->>F: "A jelentkezésed megtörtént"
        end
