# e-rezerwacja 

Aplikacja internetowa umożliwiająca rezerwowanie sal.

### Podstawowe założenia

Funkcje dostępne dla użytkownika standardowego (_Student_, _Pracownik_):

- wybór wersji językowej (polski, angielski)
- uwierzytelnienie użytkownika (poprzez akademickie konto pocztowe za pomocą Google Sign-In)
- dodanie do wybranej sali rezerwacji w podanym terminie z uwzględnieniem autoryzacji (porównanie uprawnień dostępu<sup>[1]</sup>, sprawdzenie ograniczeń czasowych<sup>[2]</sup>)
- możliwość złożenia wniosku o rezerwację sali w przypadku niepomyślnej autoryzacji
 (tj. dodanie do listy oczekujących na zatwierdzenie przez użytkownika uprzywilejowanego)
- modyfikacja własnych rezerwacji
- wyświetlenie wszystkich rezerwacji:
  - dla jednego użytkownika w widoku tygodnia
  - dla jednej sali w widoku tygodnia (opcjonalnie miesiąca)
  - dla wielu sal w widoku dnia

Funkcje dostępne z poziomu konta uprzywilejowanego (_Zarządca_, _Administrator_):

- zaakceptowanie lub odrzucenie żądania rezerwacji
- dodanie/modyfikacja rezerwacji dowolnego użytkownika
- obsługa cyklicznej rezerwacji (np. co tydzień) w podanym przedziale czasu
- zarządzanie salami, użytkownikami, uprawnieniami, ograniczeniami (_Administrator_)

### [1] Uprawnienia dostępu

System przewiduje trzy następujące „poziomy kont&quot; użytkowników: _Student_, _Pracownik_, _Zarządca_. Uprawnienia dostępu realizowane są za pomocą „tagów&quot; przypisywanych do sal w celu ograniczenia dostępu do wskazanego „poziomu konta&quot;. Przypisanie tego samego „tagu&quot; do konkretnego użytkownika stanowi dla niego wyjątek od ograniczenia. (_Student_ nie może zgłosić żądania rezerwacji sali, której ograniczenie obejmuje poziom konta _Zarządcy_ np. sali koncertowej).

### [2] Ograniczenia czasowe

Nie można dodać rezerwacji w przeszłości, ani edytować przeszłej rezerwacji. Pojedyncza rezerwacja nie może być krótsza niż 45 minut oraz dłuższa niż 24 godziny. Rezerwację można dodać jedynie<sup>[3]</sup> w czasie przypadającym na godziny pracy uczelni (zgodnie z wprowadzonym harmonogramem). Dodanie rezerwacji nie jest autoryzowane<sup>[3]</sup> jeśli prowadzi do naruszenia jednej z restrykcji:

- maksymalne wyprzedzenie terminu rezerwacji (sugerowane 2 tygodnie)
- limit długości pojedynczej rezerwacji (sugerowane 2 godz.)
- użytkownik nie posiada innej rezerwacji<sup>[4]</sup> w proponowanym terminie
- limit długości wszystkich rezerwacji<sup>[4]</sup> w tygodniu (sugerowane 14 godz.)
- minimalna przerwa między rezerwacjami użytkownika<sup>[4]</sup> w tej samej sali (sugerowane 2 godz.)

[3] Nie dotyczy konta _Zarządcy_.  
[4] Nie dotyczy rezerwacji przypisanych użytkownikowi przez _Zarządcę_.
