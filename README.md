# Lectio-API

Opdateret version af [dette github repository](https://github.com/HSPDev/lectio)

Dette er et simpelt API for Lectio som tillader at hente alle nyttige offentlige data.
Selve API'et fungerer som følger.

Liste over funktioner:

Alle de her bør sige sig selv:
```
get_schedule_student(gymnasiekode, lectio_id, ugekode)
get_schedule_class(gymnasiekode, lectio_id, ugekode)
get_schedule_teacher(gymnasiekode, laerer_id, ugekode)
```

Denne funktion henter alle skoler som bruger Lectio-platformen.
```
get_schools()
```
  
Denne funktion hiver elever ud fra et givent gymnasie.
```
get_students_from_school(gymsiekode)
```

Denne funktion henter alle hold på et givent gymnasie.
```
get_classes(gymsiekode)
```

Denne funktion henter alle lærerne ud med navn og initialer.
```
get_teachers(gymnasiekode)
```

De her funktioner får du nok ikke brug for, de tillader at hente et skema fra en hvilken som helst URL
man selv konstruerer, samt at hente elever fra en given side hvis man f.eks. kun vil have elever
hvis navn begynder med B.
```
get_schedule(url til skemaet)
get_students_from_page(url til elevsiden)
```

Forklaringer på parametre:
"Gymnasiekode" er den talkode hvert gymnasie har. Den kan ses i toppen af url'et når man er på en 
vilkårlig side på et gymnasie.
Feks. her er Nakskov Gymnasiums URL:
	http://www.lectio.dk/lectio/402/default.aspx
402 er gymnasiekoden i dette tilfælde.

"Lectio_id" referer til det ID som Lectio tilegner hver elev, lærer eller hold. Det er for at adskille fra Elev nummer som
er det tal skolen tildeler hver elev i hver klasse.

"Ugekode" er ret vigtig. Den skal indsættes som WWYYYY dvs. for uge 11 i år 2013 så er koden 112013
