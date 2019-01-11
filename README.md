# Lectio-API

Opdateret version af [dette github repository](https://github.com/HSPDev/lectio), oprindeligt lavet af Henrik Pedersen
og Daniel Poulsen, nu vedligeholdt af mig, Krede

Dette er et simpelt, uofficielt API for Lectio som tillader at hente forskellige offentlige data.
API'et kan kun hente offentligt-tilgængeligt data som gymnasier, elever, lærere, hold og skemaer.
Den kan derfor ikke bruges til at måle fravær, tjekke opgaver eller andre private oplysninger

API'et, ligesom den tidligere version, bruger Simple HTML Dom og Regular Expressions til at finde brikker og andet information på Lectio's mange sider.
Jeg har ændret det således at den ikke bruger `cURL` men istedet `file_get_contents`.

Denne version er testet på en installation af PHP 7.0. Den virker muligvis på tidligere versioner.

Dette API er gratis og frit at bruge og lave ændringer til såfremt at Licensen og Copyright bliver overholdt.

## Eksempel

```php
require('lectio.php');
$lectio = new lectio('simple_html_dom.php');
$schools = $lectio->get_schools(); // Henter alle skoler fra Lectio
print_r($schools); // Print skolerne og deres gymnasie kode
```

## Liste over funktioner

Alle de her funktioner henter skemaet for forskellige brugere/hold som et Array.
```php
->get_schedule_student($gymnasie_id, $lectio_id, $unixtime)
->get_schedule_class($gymnasie_id, $lectio_id, $unixtime)
->get_schedule_teacher($gymnasie_id, $lectio_id, $unixtime)
```

Denne funktion henter alle skoler som anvender Lectio-platformen. Arrayet har navnet som `key` og dets `gymnasie_id` som `value`.
```php
->get_schools()
```

Disse funktioner giver alle lokaler og deres tilsvarende id, `get_empty_rooms` viser kun de lokaler som ikke er i brug. Arrayet har lokalets navn som `key` og dets `id` som `value`.
```php
->get_rooms($gymnasie_id)
->get_empty_rooms($gymnasie_id, $unixtime)
```
`get_empty_rooms` kan være meget langsom alt efter hvor mange klasselokaler skolen har da den loader en ny side per lokale. Man kan cache resultatet hvis man har lyst, det ødelægger bare lidt ideen med hurtigt at finde et tomt lokale.

Disse tre funktioner hiver hhv. elever, hold og lærer ud fra et givent gymnasie som et Array. Arrayet har personens/holdets navn som `key` og deres `lectio_id` som `value`.
```php
->get_students($gymnasie_id)
->get_classes($gymnasie_id)
->get_teachers($gymnasie_id)
```
Jeg vil foreslå at cache resultaterne når man bruger `get_students()` da den skal loade en ny side for hver forbogstav, hvilket vil sige at det gøres 30 gange for hver gang den bliver kørt

De her funktioner får du nok ikke brug for, da de tillader at hente et skema fra en hvilken som helst URL
man selv konstruerer, samt at hente elever fra en given side hvis man f.eks. kun vil have elever
hvis navn begynder med B.
```php
->get_schedule($url_til_skemaet)
->get_students_page($url_til_elevsiden)
```

## Forklaringer på parametre
`gymnasie_id` er den talkode hvert gymnasie har. Den kan ses i toppen af URL'et når man er på en 
vilkårlig side på et gymnasie.
Feks. her er Nakskov Gymnasiums URL: "http://www.lectio.dk/lectio/402/default.aspx".
`402` er gymnasiekoden i dette tilfælde.

`lectio_id` refererer til det ID som Lectio tilegner hver elev, lærer eller hold. Det kan enten findes i toppen af URL'et når man er logget ind eller ved brug af de tre ovenstående funktioner til at finde elever, lærere og hold.

`unixtime` er tiden i sekunder siden d. 1 Januar 1970. Idag ville f.eks. være 1534785460 (20-8-2018 19:17:40)
