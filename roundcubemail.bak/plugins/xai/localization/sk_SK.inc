<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI Assistant';
$labels['ai_assistant'] = 'AI Assistant';
$labels['ai_assistant_settings'] = 'Nastavenia AI Assistant';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Napísať správu s AI';

$labels['compose_label_to'] = 'Meno príjemcu';
$labels['compose_label_from'] = 'Meno odosielateľa';
$labels['compose_label_style'] = 'Štýl';
$labels['compose_label_length'] = 'Dĺžka';
$labels['compose_label_language'] = 'Jazyk';
$labels['compose_label_creativity'] = 'Kreativita';
$labels['compose_label_instructions'] = 'Pokyny';
$labels['compose_no_change'] = 'Žiadna zmena';
$labels['compose_instructions_placeholder'] = 'Príklad: požiadajte o zmenu termínu na budúci týždeň';

$labels['compose_style_assertive'] = 'Asertívne';
$labels['compose_style_casual'] = 'Príležitostné';
$labels['compose_style_enthusiastic'] = 'Nadšený';
$labels['compose_style_funny'] = 'Zábavné';
$labels['compose_style_informational'] = 'Informačné';
$labels['compose_style_professional'] = 'Profesionálne';
$labels['compose_style_urgent'] = 'Naliehavé';
$labels['compose_style_witty'] = 'Dôvtipný';

$labels['compose_length_short'] = 'Krátky';
$labels['compose_length_medium'] = 'Stredné';
$labels['compose_length_long'] = 'Dlhé';

$labels['compose_creativity_low'] = 'Nízka';
$labels['compose_creativity_medium'] = 'Stredné';
$labels['compose_creativity_high'] = 'Vysoká';

$labels['compose_insert_email'] = 'Vložte e-mail';
$labels['compose_generate_email'] = 'Generovať e-mail';
$labels['compose_generate_again'] = 'Znovu generovať';
$labels['compose_stop_generating'] = 'Zastavenie generovania';
$labels['compose_error_help_button'] = 'Pomoc';

$labels['compose_no_instructions'] = 'Zadajte pokyny.';
$labels['compose_instructions_too_long'] = 'Vaše pokyny sú príliš dlhé; môžu mať maximálne $n znakov.';
$labels['compose_empty_response'] = 'Server vrátil prázdnu odpoveď.';

$labels['compose_tip_to'] = 'Meno príjemcu nového e-mailu. Môžete zadať viac ako jedno meno alebo použiť reťazce ako "všetci", "tím" atď.';
$labels['compose_tip_from'] = 'Nový e-mail bude podpísaný týmto menom.';
$labels['compose_tip_style'] = 'Uprednostňovaný štýl písania nového e-mailu.';
$labels['compose_tip_length'] = 'Uprednostňovaná dĺžka nového e-mailu.';
$labels['compose_tip_language'] = 'Jazyk, v ktorom má byť nový e-mail napísaný. Zadajte "Bez zmeny", ak chcete písať v jazyku, v ktorom sú uvedené pokyny.';
$labels['compose_tip_creativity'] = 'Vyššia kreativita bude generovať nepredvídateľnejšie a rozmanitejšie e-maily; nižšia kreativita bude generovať e-maily, ktoré sú vecnejšie a vecnejšie.';
$labels['compose_tip_instructions'] = 'Pokyny, čo treba uviesť v novom e-maile. Kliknutím na tlačidlo Príklady pokynov si môžete pozrieť niekoľko príkladov.';

$labels['compose_help_button_show'] = 'Príklady inštrukcií';
$labels['compose_help_button_hide'] = 'Späť';
$labels['compose_help_title'] = 'Pomoc a príklady';
$labels['compose_help_info'] = 'Povedzte umelej inteligencii, aký e-mail chcete napísať. Uveďte konkrétne údaje, napríklad mená, dátumy, miesta, dôvody atď. Môžete tiež požiadať o kreatívne nápady alebo vysvetlenia. Po vygenerovaní e-mailu ho budete môcť upraviť a vložiť do stránky na písanie. Tu je niekoľko vzorových pokynov, ktoré vám pomôžu začať:';

$labels['compose_help_example_1'] = 'pozdraviť';
$labels['compose_help_example_2'] = 'dnešné stretnutie bolo zrušené';
$labels['compose_help_example_3'] = 'Dohodol som si stretnutie s Margy na st 3 popoludní, pripraviť prezentáciu o výhodách používania AI v našich marketingových kampaniach, aby som ju presvedčil.';
$labels['compose_help_example_4'] = 'sme sa rozhodli pridať podporu pre obsah generovaný umelou inteligenciou, stručne vysvetliť, čo to je';
$labels['compose_help_example_5'] = 'nadviazanie na pracovnú úlohu z minulého týždňa, potrebujem ju zajtra';
$labels['compose_help_example_6'] = 'požiadať o zvýšenie platu, nedostal som ho viac ako rok';
$labels['compose_help_example_7'] = 'pripomenúť, aby ste zajtra predložili správu, inak bude mať tím problémy s Margy, ktorá je vystresovaná.';
$labels['compose_help_example_8'] = 'napíšte zákazníkom o 20% zľave na všetky produkty do utorka, predávame oblečenie';
$labels['compose_help_example_9'] = 'požiadať Lauru, aby napísala a povedala jej, aby zorganizovala stretnutie na Kevinove narodeniny tento víkend.';
$labels['compose_help_example_10'] = 'opýtajte sa na pokrok s novou webovou stránkou, uveďte 5 nápadov na marketingové slogany pre titulnú stránku, opravujeme autá';
$labels['compose_help_example_11'] = 'newsletter o nových funkciách vpn, ktoré sme pridali: protokol wireguard, presmerovanie portov, kill switch, vyhradená IP; vysvetliť, čo sú';
$labels['compose_help_example_12'] = 'newsletter o novom vernostnom programe, počiatočné náklady sú 10 USD za kartu a potom až 20% zľavy na väčšinu produktov počas jedného roka';
$labels['compose_help_example_13'] = 'sieťový e-mail niekomu, koho obdivujem a s kým sa chcem profesionálne spojiť';
$labels['compose_help_example_14'] = 'profesionálne poďakovanie za pomoc s projektom včera';
$labels['compose_help_example_15'] = 'vymyslieť 10 nápadov na stratégie, ako pripraviť účinný e-mail pre investorov, bude na tom pracovať zajtra.';
$labels['compose_help_example_16'] = 'profesionálny email s výpoveďou a 1 mesačnou výpovednou lehotou podľa zmluvy, spoločnosť sa volá ACME';
$labels['compose_help_example_17'] = 'presvedčivý e-mail so žiadosťou o povýšenie, v ktorom naznačuje, že ak ho nedostanem, môžem dať výpoveď, pretože sú aj iné príležitosti.';
$labels['compose_help_example_18'] = 'bolo dobré sa s vami stretnúť na včerajšej konferencii, užil som si náš rozhovor, zostaňme v kontakte';

$labels['compose_help_use_example'] = '(Použite toto)';

// summaries
$labels['creating_summary'] = 'Vytvorenie súhrnu...';
$labels['show_summary'] = 'Zobraziť zhrnutie';
$labels['hide_summary'] = 'Skryť zhrnutie';
$labels['recreate_summary'] = 'Opätovné vytvorenie súhrnu';
$labels['email_has_no_text'] = 'Tento e-mail neobsahuje žiadny text.';

// settings
$labels['setting_compose_style'] = 'Štýl';
$labels['setting_compose_length'] = 'Dĺžka';
$labels['setting_compose_language'] = 'Jazyk';
$labels['setting_compose_creativity'] = 'Kreativita';
$labels['setting_language'] = 'Jazyk';

$labels['summarizing_messages'] = 'Zhrnutie správ';
$labels['setting_show_summary_on_mail_view'] = 'Zobrazenie súhrnov AI pri prezeraní e-mailov';
$labels['setting_show_summary_on_list_hover'] = 'Zobrazenie súhrnov AI pri prejdení nad zoznam e-mailov';
$labels['setting_list_summary_placement'] = 'Umiestnenie vyskakovacieho okna so súhrnom zoznamu';

$labels['above'] = 'Nad';
$labels['below'] = 'Pod';
$labels['left'] = 'Vľavo';
$labels['right'] = 'Vpravo';

$labels['ai_disclaimer'] = 'Výstup AI môže byť niekedy neúplný alebo nesprávny. Vždy si dvakrát skontrolujte podstatné informácie.';

// errors
$labels['api_error'] = 'Pri pripájaní k serveru AI došlo k chybe. Obráťte sa na správcu.';
