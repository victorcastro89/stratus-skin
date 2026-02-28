<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Asistent s umělou inteligencí';
$labels['ai_assistant'] = 'Asistent s umělou inteligencí';
$labels['ai_assistant_settings'] = 'Nastavení AI Assistant';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Sestavení zprávy s umělou inteligencí';

$labels['compose_label_to'] = 'Jméno příjemce';
$labels['compose_label_from'] = 'Jméno odesílatele';
$labels['compose_label_style'] = 'Styl';
$labels['compose_label_length'] = 'Délka';
$labels['compose_label_language'] = 'Jazyk';
$labels['compose_label_creativity'] = 'Kreativita';
$labels['compose_label_instructions'] = 'Pokyny';
$labels['compose_no_change'] = 'Beze změny';
$labels['compose_instructions_placeholder'] = 'Příklad: požádejte o změnu schůzky na příští týden.';

$labels['compose_style_assertive'] = 'Asertivní';
$labels['compose_style_casual'] = 'Příležitostné';
$labels['compose_style_enthusiastic'] = 'Nadšený';
$labels['compose_style_funny'] = 'Vtipné';
$labels['compose_style_informational'] = 'Informační';
$labels['compose_style_professional'] = 'Profesionální';
$labels['compose_style_urgent'] = 'Naléhavé';
$labels['compose_style_witty'] = 'Důvtipné';

$labels['compose_length_short'] = 'Krátké';
$labels['compose_length_medium'] = 'Střední';
$labels['compose_length_long'] = 'Long';

$labels['compose_creativity_low'] = 'Nízká';
$labels['compose_creativity_medium'] = 'Střední';
$labels['compose_creativity_high'] = 'Vysoká';

$labels['compose_insert_email'] = 'Vložte e-mail';
$labels['compose_generate_email'] = 'Generování e-mailu';
$labels['compose_generate_again'] = 'Znovu generovat';
$labels['compose_stop_generating'] = 'Zastavení generování';
$labels['compose_error_help_button'] = 'Nápověda';

$labels['compose_no_instructions'] = 'Zadejte pokyny.';
$labels['compose_instructions_too_long'] = 'Vaše pokyny jsou příliš dlouhé; mohou mít maximálně $n znaků.';
$labels['compose_empty_response'] = 'Server vrátil prázdnou odpověď.';

$labels['compose_tip_to'] = 'Jméno příjemce nového e-mailu. Můžete zadat více než jedno jméno nebo použít řetězce jako "všichni", "tým" atd.';
$labels['compose_tip_from'] = 'Nový e-mail bude podepsán tímto jménem.';
$labels['compose_tip_style'] = 'Preferovaný styl psaní nového e-mailu.';
$labels['compose_tip_length'] = 'Preferovaná délka nového e-mailu.';
$labels['compose_tip_language'] = 'Jazyk, ve kterém má být nový e-mail napsán. Zadejte "Beze změny", chcete-li psát v jazyce, ve kterém jsou uvedeny pokyny.';
$labels['compose_tip_creativity'] = 'Vyšší kreativita bude generovat nepředvídatelnější a rozmanitější e-maily; nižší kreativita bude generovat e-maily, které budou věcnější a výstižnější.';
$labels['compose_tip_instructions'] = 'Pokyny, co do nového e-mailu zahrnout. Kliknutím na tlačítko Příklady pokynů si můžete prohlédnout několik příkladů.';

$labels['compose_help_button_show'] = 'Příklady pokynů';
$labels['compose_help_button_hide'] = 'Zpět';
$labels['compose_help_title'] = 'Nápověda a příklady';
$labels['compose_help_info'] = 'Řekněte umělé inteligenci, jaký e-mail chcete napsat. Uveďte konkrétní údaje, například jména, data, místa, důvody atd. Můžete také požádat o kreativní nápady nebo vysvětlení. Po vygenerování e-mailu jej budete moci upravit a vložit do stránky pro sestavení. Zde je několik vzorových pokynů, které vám pomohou začít:';

$labels['compose_help_example_1'] = 'pozdravit';
$labels['compose_help_example_2'] = 'dnešní schůzka byla zrušena';
$labels['compose_help_example_3'] = 'Domluvil jsem si s Margy schůzku na st 15:00, připravil prezentaci o výhodách využití AI v našich marketingových kampaních, abych ji přesvědčil.';
$labels['compose_help_example_4'] = 'jsme se rozhodli přidat podporu pro obsah generovaný umělou inteligencí, stručně vysvětlit, co to je.';
$labels['compose_help_example_5'] = 'pokračování pracovního úkolu z minulého týdne, potřebuji ho zítra.';
$labels['compose_help_example_6'] = 'požádat o zvýšení platu, nedostal jsem ho víc než rok.';
$labels['compose_help_example_7'] = 'připomenout, abyste zítra předložili zprávu, jinak bude mít tým problémy s Margy, která je ve stresu.';
$labels['compose_help_example_8'] = 'napište zákazníkům o 20% slevě na všechny produkty do úterý, prodáváme oblečení';
$labels['compose_help_example_9'] = 'požádat Lauru, aby jí napsala, že má o víkendu uspořádat setkání ke Kevinovým narozeninám.';
$labels['compose_help_example_10'] = 'zeptejte se na pokrok s novými webovými stránkami, uveďte 5 nápadů na marketingové slogany pro titulní stránku, opravujeme auta.';
$labels['compose_help_example_11'] = 'newsletter o nových funkcích vpn, které jsme přidali: protokol wireguard, přesměrování portů, kill switch, vyhrazená IP; vysvětlete, co jsou zač.';
$labels['compose_help_example_12'] = 'newsletter o novém věrnostním programu, počáteční cena je 10 dolarů za kartu a pak až 20% slevy na většinu produktů po dobu jednoho roku.';
$labels['compose_help_example_13'] = 'síťový e-mail někomu, koho obdivuji a chci se s ním profesně spojit.';
$labels['compose_help_example_14'] = 'profesionální poděkování za pomoc s projektem včera';
$labels['compose_help_example_15'] = 'přišla s 10 nápady strategií pro vytvoření efektivního e-mailového podání investorům, bude na něm pracovat zítra.';
$labels['compose_help_example_16'] = 'profesionální výpověď e-mailem s výpovědní lhůtou 1 měsíc podle smlouvy, společnost se jmenuje ACME.';
$labels['compose_help_example_17'] = 'přesvědčivý e-mail s žádostí o povýšení, v němž naznačuje, že pokud povýšení nedostanu, mohu rezignovat, protože jsou i jiné příležitosti.';
$labels['compose_help_example_18'] = 'bylo dobré se s vámi setkat na včerejší konferenci, rád jsem si popovídal, zůstaňme v kontaktu.';

$labels['compose_help_use_example'] = '(Použijte toto)';

// summaries
$labels['creating_summary'] = 'Vytvoření shrnutí...';
$labels['show_summary'] = 'Zobrazit shrnutí';
$labels['hide_summary'] = 'Skrýt shrnutí';
$labels['recreate_summary'] = 'Znovu vytvořit přehled';
$labels['email_has_no_text'] = 'Tento e-mail neobsahuje žádný text.';

// settings
$labels['setting_compose_style'] = 'Styl';
$labels['setting_compose_length'] = 'Délka';
$labels['setting_compose_language'] = 'Jazyk';
$labels['setting_compose_creativity'] = 'Kreativita';
$labels['setting_language'] = 'Jazyk';

$labels['summarizing_messages'] = 'Shrnutí zpráv';
$labels['setting_show_summary_on_mail_view'] = 'Zobrazení souhrnů AI při prohlížení e-mailů';
$labels['setting_show_summary_on_list_hover'] = 'Zobrazení souhrnů AI při najetí na seznam e-mailů';
$labels['setting_list_summary_placement'] = 'Umístění vyskakovacího okna se souhrnem seznamu';

$labels['above'] = 'Nad stránkami';
$labels['below'] = 'Níže';
$labels['left'] = 'Vlevo';
$labels['right'] = 'Vpravo';

$labels['ai_disclaimer'] = 'Výstup AI může být někdy neúplný nebo nesprávný. Vždy si překontrolujte podstatné informace.';

// errors
$labels['api_error'] = 'Při připojování k serveru AI došlo k chybě. Kontaktujte prosím správce.';
