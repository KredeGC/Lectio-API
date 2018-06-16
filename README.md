# Lectio-API

Opdateret version af [dette github repository](https://github.com/HSPDev/lectio)

Dette er et simpelt, uofficielt API for Lectio som tillader at hente alle nyttige offentlige data.
API'et kan kune hente offentligt-tilgængeligt data som gymnasier, elever, lærere, hold og skemaer.

## Liste over funktioner

Alle de her bør sige sig selv:
```php
get_schedule_student(gymnasiekode, lectio_id, ugekode)
get_schedule_class(gymnasiekode, lectio_id, ugekode)
get_schedule_teacher(gymnasiekode, laerer_id, ugekode)
```

Denne funktion henter alle skoler som anvender Lectio-platformen.
```php
get_schools()
```
  
Disse tre funktioner hiver hhv. elever, hold og lærer ud fra et givent gymnasie.
```php
get_students_from_school(gymsiekode)
get_classes(gymsiekode)
get_teachers(gymnasiekode)
```

De her funktioner får du nok ikke brug for, de tillader at hente et skema fra en hvilken som helst URL
man selv konstruerer, samt at hente elever fra en given side hvis man f.eks. kun vil have elever
hvis navn begynder med B.
```php
get_schedule(url til skemaet)
get_students_from_page(url til elevsiden)
```

## Forklaringer på parametre
`gymnasiekode` er den talkode hvert gymnasie har. Den kan ses i toppen af URL'et når man er på en 
vilkårlig side på et gymnasie.
Feks. her er Nakskov Gymnasiums URL: "http://www.lectio.dk/lectio/402/default.aspx".
`402` er gymnasiekoden i dette tilfælde.

`lectio_id` referer til det ID som Lectio tilegner hver elev, lærer eller hold. Det kan enten findes i toppen af URL'et eller ved brug af de tre funktioner til at finde elever, lærere og hold.

`ugekode` er ret vigtig. Den skal indsættes som WWYYYY dvs. for uge 7 i år 2018 så er koden `072018`
