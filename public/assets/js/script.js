/* =====================================================================
   KLIENS OLDALI MŰKÖDÉS (JavaScript)
   Ez a böngészőben fut, a szerver nem látja. Négy dolgot csinál:
     1) megerősítést kér törlés/elutasítás előtt
     2) ellenőrzi az űrlapokat beküldés előtt
     3) képelőnézetet mutat feltöltés előtt
     4) eltünteti a zöld/kék értesítő sávot pár másodperc után
   A szerveren (PHP) minden adatot ELLENŐRZÜNK, a JS csak a felhasználói élményt javítja.
   ===================================================================== */
document.addEventListener('DOMContentLoaded', function () {

    // 1) Megerősítés: minden <form data-megerosites="Kérdés?"> beküldése előtt rákérdez
    document.querySelectorAll('form[data-megerosites]').forEach(function (urlap) {
        urlap.addEventListener('submit', function (esemeny) {
            if (!window.confirm(urlap.getAttribute('data-megerosites'))) {
                esemeny.preventDefault();               // a "Mégse" megállítja a beküldést
            }
        });
    });

    // 2) Űrlap-ellenőrzés: minden <form data-ellenorzes>
    document.querySelectorAll('form[data-ellenorzes]').forEach(function (urlap) {
        urlap.addEventListener('submit', function (esemeny) {
            var minden_rendben = true;

            // előző hibák törlése
            urlap.querySelectorAll('.mezo-hiba').forEach(function (elem) { elem.remove(); });
            urlap.querySelectorAll('.hibas').forEach(function (elem) { elem.classList.remove('hibas'); });

            // hibát jelző segédfüggvény
            function hibatJelez(mezo, szoveg) {
                minden_rendben = false;
                mezo.classList.add('hibas');
                var felirat = document.createElement('span');
                felirat.className = 'mezo-hiba';
                felirat.textContent = szoveg;
                mezo.parentNode.appendChild(felirat);
            }

            // kötelező mezők és minimális hossz
            urlap.querySelectorAll('input[required], textarea[required], select[required]').forEach(function (mezo) {
                if (mezo.value.trim() === '') {
                    hibatJelez(mezo, 'Ez a mező kötelező.');
                } else if (mezo.minLength > 0 && mezo.value.length < mezo.minLength) {
                    hibatJelez(mezo, 'Legalább ' + mezo.minLength + ' karakter szükséges.');
                }
            });

            // jelszó: tartalmazzon betűt és számot, a két jelszó egyezzen (regisztrációnál)
            var jelszo = urlap.querySelector('input[name="jelszo"]');
            var jelszoUjra = urlap.querySelector('input[name="jelszo_ujra"]');
            if (jelszo && jelszo.value !== '' && !(/[A-Za-z]/.test(jelszo.value) && /[0-9]/.test(jelszo.value))) {
                hibatJelez(jelszo, 'A jelszó tartalmazzon betűt és számot is.');
            }
            if (jelszo && jelszoUjra && jelszo.value !== jelszoUjra.value) {
                hibatJelez(jelszoUjra, 'A két jelszó nem egyezik.');
            }

            if (!minden_rendben) {
                esemeny.preventDefault();               // hiba esetén nem megy el az űrlap
            }
        });
    });

    // 3) Képelőnézet: ha fájlt választanak, azonnal megjelenik alatta
    var fajlMezo = document.getElementById('kep-bemenet');
    var elonezet = document.getElementById('kep-elonezet');
    if (fajlMezo && elonezet) {
        fajlMezo.addEventListener('change', function () {
            var fajl = fajlMezo.files[0];
            if (fajl) {
                elonezet.src = URL.createObjectURL(fajl);
                elonezet.hidden = false;
            }
        });
    }

    // 4) A siker- és info-üzenetek 6 másodperc után eltűnnek
    document.querySelectorAll('.uzenet-siker, .uzenet-info').forEach(function (sav) {
        setTimeout(function () { sav.style.display = 'none'; }, 6000);
    });
});
