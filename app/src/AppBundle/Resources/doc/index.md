## Alapvetések

[majd átírom a többit is ide]

## idpAdd
### Elvi dolgok, csak ahogy eszembe jutnak (többhelyütt egyszerűsödött az eredetihez képest :) )
* akárki akármennyi IdP-t létrehozhat
* minden IdP 30 napos full free változattal indul, utána 30 nap az első hónap, amely végén már fizetni kell, 8 nap türelmi idővel, utána életbe lép a korlátozás, 5 aktív júzer / hó
* minden IdP-hez lehet akár belső, akár külső júzeradatforrást használni
* IdP regisztrációkor ha megvannak az alapadatok meg az authsource adatok is működnek, onnantól indul a 30 nap

### Kellenek a hozzáadáshoz
#### Kötelező
* azonosító (`hostname` - ebből generálódik az entityId, meg a scopes), ez az érték később nem módosítható
* logó feltöltve (ebből kalkulálódik a logoWidth, logoHeight, logoSrc)
* az intézmény típusa (school, university, research, commercial) --> ez ugye a pricingplannél volt eddig, ez sem változtatható utólag (csak általunk, adminok által, ha valaki csalni akart)
* az intézmény neve tetszőleges nyelveken (OrganizationElement)
##### Opcionális
* description (OrganizationElement)
* privacystatementurl (OrganizationElement)
* informationurl (OrganizationElement)

#### Kiegészítő
* a kontakt a regisztráló júzer lesz, kontrolleridőben tesszük hozzá

### Képernyőkép
#### Wizard
1. alapadatok (azonosító, logó, kontaktok, típus)
2. authsource típus kiválasztása, külső esetén paraméterek megadása, kapcsolat / lekérdezés tesztelése
3. összegzés: kiírni meddig tart a free időszak, mikor kell először tejelni, milyen címre fogunk levelet küldeni, hogyan tovább

## idpShow
nem kell, az idpListen minden látszódik egy IdP-vel kapcsolatban, ami lényeges

## idpList
táblázatos forma
* Intézmény neve, alatta kicsiben az entityId, ami egyben url is (target=_blank)
* Az aktuális időszak jellemzői (eleje, vége, időszakbani aktív júzerek száma, következő fizetés esedékessége, ha esedékes, akkor nagy warning gombbal átirányítás a fizetésre, ha túlfutott, akkor ugyanez dangerrel)
* szerkesztés gomb (/idpEdit), ami csak akkor aktív, ha nincs lejárt tartozása a parasztnak

## idpEdit
ugyanaz a wizard, mint az addnál, csak már előre kitöltve az aktuális adatokkal

## idpPay
Itt tudja a paraszt megadni, hogy az aktuális fizetési kötelezettségét miként kívánja teljesíteni, BTC or Paypal, és fizetni tud, megjelennek az összegek, magyarázatok (aktív júzerek száma, egységár...stb)

