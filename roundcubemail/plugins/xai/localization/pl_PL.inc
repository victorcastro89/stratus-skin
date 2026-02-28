<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Asystent AI';
$labels['ai_assistant'] = 'Asystent AI';
$labels['ai_assistant_settings'] = 'Ustawienia asystenta AI';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Napisz wiadomość ze sztuczną inteligencją';

$labels['compose_label_to'] = 'Imię odbiorcy';
$labels['compose_label_from'] = 'Imię nadawcy';
$labels['compose_label_style'] = 'Styl';
$labels['compose_label_length'] = 'Długość';
$labels['compose_label_language'] = 'Język';
$labels['compose_label_creativity'] = 'Kreatywność';
$labels['compose_label_instructions'] = 'Instrukcje';
$labels['compose_no_change'] = 'Bez zmian';
$labels['compose_instructions_placeholder'] = 'Przykład: poproś o zmianę terminu spotkania na następny tydzień';

$labels['compose_style_assertive'] = 'Asertywny';
$labels['compose_style_casual'] = 'Swobodny';
$labels['compose_style_enthusiastic'] = 'Entuzjastyczny';
$labels['compose_style_funny'] = 'Zabawny';
$labels['compose_style_informational'] = 'Informacyjny';
$labels['compose_style_professional'] = 'Profesjonalny';
$labels['compose_style_urgent'] = 'Pilny';
$labels['compose_style_witty'] = 'Dowcipny';

$labels['compose_length_short'] = 'Krótki';
$labels['compose_length_medium'] = 'Średni';
$labels['compose_length_long'] = 'Długi';

$labels['compose_creativity_low'] = 'Niska';
$labels['compose_creativity_medium'] = 'Średnia';
$labels['compose_creativity_high'] = 'Wysoka';

$labels['compose_insert_email'] = 'Wstaw Email';
$labels['compose_generate_email'] = 'Wygeneruj e-mail';
$labels['compose_generate_again'] = 'Wygeneruj ponownie';
$labels['compose_stop_generating'] = 'Przestań generować';
$labels['compose_error_help_button'] = 'Pomoc';

$labels['compose_no_instructions'] = 'Wpisz instrukcje.';
$labels['compose_instructions_too_long'] = 'Twoje instrukcje są zbyt długie; mogą mieć co najwyżej $n znaków.';
$labels['compose_empty_response'] = 'Serwer zwrócił pustą odpowiedź.';

$labels['compose_tip_to'] = 'Nazwa odbiorcy nowej wiadomości e-mail. Możesz określić więcej niż jedną nazwę lub użyć ciągów znaków, takich jak "wszyscy", "zespół" itp.';
$labels['compose_tip_from'] = 'Nowy e-mail będzie podpisany tym imieniem/nazwiskiem.';
$labels['compose_tip_style'] = 'Preferowany styl pisania dla nowej wiadomości e-mail.';
$labels['compose_tip_length'] = 'Preferowana długość nowej wiadomości e-mail.';
$labels['compose_tip_language'] = 'Język, w którym powinna być napisana nowa wiadomość e-mail. Określ "Bez zmian", aby pisać w języku, w którym podano instrukcje.';
$labels['compose_tip_creativity'] = 'Wyższa kreatywność wygeneruje bardziej nieprzewidywalne i różnorodne e-maile; niższa kreatywność wygeneruje e-maile, które są bardziej rzeczowe.';
$labels['compose_tip_instructions'] = 'Instrukcje dotyczące tego, co należy zawrzeć w nowej wiadomości e-mail. Kliknij przycisk Instrukcje, aby zobaczyć kilka przykładów.';

$labels['compose_help_button_show'] = 'Przykłady instrukcji';
$labels['compose_help_button_hide'] = 'Wstecz';
$labels['compose_help_title'] = 'Pomoc i przykłady';
$labels['compose_help_info'] = 'Opowiedz o mailu, który chciałbyś napisać. Zawrzyj w nim konkrety, takie jak nazwiska, daty, lokalizacje, powody itp. Możesz również poprosić o kreatywne pomysły lub wyjaśnienia. Po wygenerowaniu tekstu będziesz mógł go poprawić i wstawić do wiadomości. Oto kilka przykładowych instrukcji:';

$labels['compose_help_example_1'] = 'przywitaj się';
$labels['compose_help_example_2'] = 'dzisiejsze spotkanie zostało odwołane';
$labels['compose_help_example_3'] = 'Umówiłem się z Magdą na spotkanie w środę o 15:00, przygotowałem prezentację na temat zalet wykorzystania SI w naszych kampaniach marketingowych, aby ją przekonać.';
$labels['compose_help_example_4'] = 'zdecydowaliśmy się dodać wsparcie dla treści generowanych przez SI, wyjaśnij krótko, co to jest';
$labels['compose_help_example_5'] = 'zapytaj o projekt z zeszłego tygodnia, potrzebuję ten projekt na jutro';
$labels['compose_help_example_6'] = 'poprosić o podwyżkę, nie dostałem jej przez ponad rok';
$labels['compose_help_example_7'] = 'przypomnienie o złożeniu raportu na jutro albo drużyna będzie miała kłopoty z szefową, która jest zestresowana';
$labels['compose_help_example_8'] = 'napisz do klientów o 20% rabacie na wszystkie produkty do wtorku, sprzedajemy odzież';
$labels['compose_help_example_9'] = 'poprosić o napisanie do Laury, żeby zorganizowała spotkanie z okazji urodzin Marka w ten weekend';
$labels['compose_help_example_10'] = 'zapytaj o postępy z nową stroną internetową, zamieść 5 pomysłów na hasła marketingowe na pierwszą stronę, zajmujemy się naprawą samochodów';
$labels['compose_help_example_11'] = 'newsletter o nowych funkcjach vpn, które dodaliśmy: wireguard protocol, port forwarding, kill switch, dedicated IP; wyjaśnij na czym polegają';
$labels['compose_help_example_12'] = 'newsletter o nowym programie lojalnościowym, początkowy koszt to 10$ za kartę, a następnie do 20% zniżki na większość produktów przez rok';
$labels['compose_help_example_13'] = 'e-mail networkingowy do osoby, którą podziwiam i z którą chcę się związać zawodowo';
$labels['compose_help_example_14'] = 'profesjonalne podziękowanie za pomoc w realizacji projektu w dniu wczorajszym';
$labels['compose_help_example_15'] = 'wymyśl 10 pomysłów na strategie tworzenia skutecznego e-maila do inwestorów, będziemy nad tym pracować jutro';
$labels['compose_help_example_16'] = 'profesjonalny e-mail z rezygnacją, dający 1-miesięczne wypowiedzenie zgodnie z umową, firma nazywa się ACME';
$labels['compose_help_example_17'] = 'e-mail z prośbą o awans, sugerujący, że mogę zrezygnować, jeśli go nie dostanę, bo są inne możliwości';
$labels['compose_help_example_18'] = 'dobrze było Cię poznać na wczorajszej konferencji, miło mi się z Tobą rozmawiało, pozostańmy w kontakcie';

$labels['compose_help_use_example'] = '(Użyj tego)';

// summaries
$labels['creating_summary'] = 'Tworzenie podsumowania...';
$labels['show_summary'] = 'Pokaż podsumowanie';
$labels['hide_summary'] = 'Ukryj podsumowanie';
$labels['recreate_summary'] = 'Utwórz podsumowanie ponownie';
$labels['email_has_no_text'] = 'Ta wiadomość e-mail nie zawiera tekstu.';

// settings
$labels['setting_compose_style'] = 'Styl';
$labels['setting_compose_length'] = 'Długość';
$labels['setting_compose_language'] = 'Język';
$labels['setting_compose_creativity'] = 'Kreatywność';
$labels['setting_language'] = 'Język';

$labels['summarizing_messages'] = 'Podsumowanie wiadomości';
$labels['setting_show_summary_on_mail_view'] = 'Wyświetlanie podsumowań AI podczas przeglądania wiadomości e-mail';
$labels['setting_show_summary_on_list_hover'] = 'Wyświetlanie podsumowań AI po najechaniu kursorem na listę e-mail.';
$labels['setting_list_summary_placement'] = 'Umieszczenie wyskakującego okienka podsumowania listy';

$labels['above'] = 'Powyżej';
$labels['below'] = 'Poniżej';
$labels['left'] = 'Po lewej';
$labels['right'] = 'Po prawej';

$labels['ai_disclaimer'] = 'Wyniki generowane przez AI mogą być czasem niekompletne lub niepoprawne. Zawsze sprawdzaj kluczowe informacje.';

// errors
$labels['api_error'] = 'Wystąpił błąd podczas łączenia się z serwerem AI. Prosimy o kontakt z administratorem.';
