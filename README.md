# CodeIgniter-Allegro-WebApi
Prosta biblioteka ułatwiająca połączenie z API Allegro.pl. Z założenia NIE mapuje ona dostepnych w API metod - jej celem jest raczej zarzadzanie sesją i specyficznymi danymi zalogowanego usera

## Przykład zastosowania

$this->load->library('allegro');

$user_id = $this->allegro->doGetUserID('country-id', 'nick_do_sprawdzenia', 'niepuste_wymagane', 'webapi-key');

gdzie
* doGetUserID() - metoda WebApi :)
* 'country-id', 'webapi-key' - słowa kluczowe, które zostaną zastąpione odpowiednim id kraju i kluczem api (np. z configa)
* 'nick_do_sprawdzenia' - to nick, dla którego sprawdzamy ID :)

Jeśli zamiast zdefiniowanych globalnie 'słów kluczowych' życzymy sobie użyć innych wartości, po prostu je przekazujemy

$user_id = $this->allegro->doGetUserID(2, 'nick_do_sprawdzenia', 'niepuste_wymagane', 'innykluczapi');


## Dostępne 'słowa kluczowe', po przecinku są alternatywne i równoważne.
(mechanizm miał tak działać, aby kopiując listę argumentów z dokumentacji, można je było użyć kopiuj-wklej, okazało się, że ile metod tyle nazw..)

### ustawiane w configu
* user-login, login
* user-password, password
* webapi-key, api-key
* country-id, country-code, country


### ustawiane w trakcie wykonywania skryptu
* local-version, ver-key
* session-handle, session-id, session

## Filozofia działania
jest prosta jak barszcz, wywołujemy nieistniejące metody, które przechwytywane są przez magiczne __call().
Tam wczytujemy klucz sesji zapisany w pliku (root) .allegro_webapi_session, wywołujemy funkcję z przekazanymi argumentami przepisując wcześniej słowa kluczowe na wartości.
Jeśli wywołanie jest z błędem, w catch sprawdzamy faultcode - jeśli jest związany z sesją lub kluczem wersji ERR_NO_SESSION, ERR_SESSION_EXPIRED, ERR_INVALID_VERSION_CAT_SELL_FIELDS
pukamy do allegro z doQuerySysStatus i doLogin aby je na nowo ustawić, zapisujemy nową sesję do pliku i ponownie wywołujemy metodę, od której wszystko się zaczęło.

Proste, odporne na aktualizacje metod, ale (na razie) nie udostępnia żadnych metod pomocniczych.