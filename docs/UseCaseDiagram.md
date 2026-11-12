# Use Case Diagram

```mermaid
usecase-beta
direction LR

actor F("Felhasználó")
systemBoundary "Weboldal"
  U1("Regisztráció")
  U2("Regisztráció feltétel")
  U3("Bejelentkezés")
  U4("Bejelentkezés ellenőrzés")
  U5("Bejentkezés elutasítva")
  U6("Jelentkezés")
  T1("Nem lehet örökbefogadni")
  T2("Jelentkezés elfogadva")
  
end
F --> U1
U1..> : include U2
F --> U3
U3..> : include U4
U3..> : extend U5
F --> U6
U6..> : extend T1
U6..> : extend T2