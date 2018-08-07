# Lectio-API

Opdateret version af [dette github repository](https://github.com/HSPDev/lectio), oprindeligt lavet af Henrik Pedersen
og Daniel Poulsen, nu vedligeholdt af mig, Krede

Dette er et simpelt, uofficielt API for Lectio som tillader at hente forskellige offentlige data.
API'et kan kun hente offentligt-tilgængeligt data som gymnasier, elever, lærere, hold og skemaer.
Den kan derfor ikke bruges til at måle fravær, tjekke opgaver eller andre private oplysninger

API'et, ligesom den tidligere version, bruger Simple HTML Dom og Regular Expressions til at finde brikker og andet information på Lectio's mange sider.
Jeg har ændret det således at den ikke bruger `cURL` men istedet `file_get_contents`

Dette API er gratis og frit at bruge og lave ændringer til såfremt at Licensen og Copyright bliver overholdt.

## Eksempel

```php
$lectio = new lectio($simple_html_dom_path);
$schools = $lectio->get_schools();
```

## Liste over funktioner

Alle de her funktioner bør sige sige selv:
```php
->get_schedule_student($gymnasie_id, $lectio_id, $ugekode)
->get_schedule_class($gymnasie_id, $lectio_id, $ugekode)
->get_schedule_teacher($gymnasie_id, $lectio_id, $ugekode)
```

Denne funktion henter alle skoler som anvender Lectio-platformen.
```php
->get_schools()
```
  
Disse tre funktioner hiver hhv. elever, hold og lærer ud fra et givent gymnasie.
```php
->get_students($gymsie_id)
->get_classes($gymsie_id)
->get_teachers($gymnasie_id)
```
Jeg vil foreslå at cache resultaterne når man bruger `get_students()` da den skal loade en ny side for hver forbogstav, hvilket vil sige at det gøres 30 gange for hver gang den bliver kørt

De her funktioner får du nok ikke brug for, de tillader at hente et skema fra en hvilken som helst URL
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

`lectio_id` refererer til det ID som Lectio tilegner hver elev, lærer eller hold. Det kan enten findes i toppen af URL'et eller ved brug af de tre funktioner til at finde elever, lærere og hold.

`ugekode` er ret vigtig. Den skal indsættes som WWYYYY dvs. for uge 7 i år 2018 så er koden `072018`
