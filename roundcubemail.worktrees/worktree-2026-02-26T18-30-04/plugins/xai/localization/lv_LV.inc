<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Mākslīgā intelekta asistents';
$labels['ai_assistant'] = 'Mākslīgā intelekta asistents';
$labels['ai_assistant_settings'] = 'AI Assistant iestatījumi';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Rakstīt ziņu ar AI';

$labels['compose_label_to'] = 'Saņēmēja nosaukums';
$labels['compose_label_from'] = 'Sūtītāja vārds un uzvārds';
$labels['compose_label_style'] = 'Stils';
$labels['compose_label_length'] = 'Garums';
$labels['compose_label_language'] = 'Valoda';
$labels['compose_label_creativity'] = 'Radošums';
$labels['compose_label_instructions'] = 'Instrukcijas';
$labels['compose_no_change'] = 'Nav izmaiņu';
$labels['compose_instructions_placeholder'] = 'Piemērs: lūgt mainīt tikšanos uz nākamo nedēļu.';

$labels['compose_style_assertive'] = 'Assertive';
$labels['compose_style_casual'] = 'Gadījuma';
$labels['compose_style_enthusiastic'] = 'Entuziasma pilns';
$labels['compose_style_funny'] = 'Smieklīgi';
$labels['compose_style_informational'] = 'Informatīvs';
$labels['compose_style_professional'] = 'Profesionāls';
$labels['compose_style_urgent'] = 'Steidzami';
$labels['compose_style_witty'] = 'Asprātīgs';

$labels['compose_length_short'] = 'Īss';
$labels['compose_length_medium'] = 'Vidēja';
$labels['compose_length_long'] = 'Long';

$labels['compose_creativity_low'] = 'Zema';
$labels['compose_creativity_medium'] = 'Vidēja';
$labels['compose_creativity_high'] = 'Augsts';

$labels['compose_insert_email'] = 'Ievietot e-pasta adresi';
$labels['compose_generate_email'] = 'Izveidot e-pastu';
$labels['compose_generate_again'] = 'Atkārtoti ģenerēt';
$labels['compose_stop_generating'] = 'Pārtraukt ģenerēšanu';
$labels['compose_error_help_button'] = 'Palīdzība';

$labels['compose_no_instructions'] = 'Ievadiet norādījumus.';
$labels['compose_instructions_too_long'] = 'Jūsu norādījumi ir pārāk gari; tie var būt ne vairāk kā $n rakstzīmju.';
$labels['compose_empty_response'] = 'Serveris atdeva tukšu atbildi.';

$labels['compose_tip_to'] = 'Jaunā e-pasta saņēmēja vārds. Varat norādīt vairāk nekā vienu vārdu vai izmantot virknes, piemēram, "visi", "komanda" u. c.';
$labels['compose_tip_from'] = 'Jaunais e-pasts tiks parakstīts ar šo vārdu.';
$labels['compose_tip_style'] = 'Vēlamais jaunā e-pasta rakstīšanas stils.';
$labels['compose_tip_length'] = 'Vēlamais jaunā e-pasta garums.';
$labels['compose_tip_language'] = 'Valoda, kurā jāraksta jaunais e-pasts. Norādiet "Bez izmaiņām", lai rakstītu tajā valodā, kurā sniegtas instrukcijas.';
$labels['compose_tip_creativity'] = 'Augstāka radošuma pakāpe radīs neparedzamākus un daudzveidīgākus e-pasta ziņojumus; zemāka radošuma pakāpe radīs e-pasta ziņojumus, kas ir faktiskāki un precīzāki.';
$labels['compose_tip_instructions'] = 'norādījumi par to, kas jāiekļauj jaunajā e-pastā. Noklikšķiniet uz pogas Instrukciju piemēri, lai apskatītu dažus piemērus.';

$labels['compose_help_button_show'] = 'Instrukciju piemēri';
$labels['compose_help_button_hide'] = 'Atpakaļ';
$labels['compose_help_title'] = 'Palīdzība un piemēri';
$labels['compose_help_info'] = 'Pastāstiet mākslīgajam intelektam par e-pastu, ko vēlaties uzrakstīt. Norādiet konkrētus datus, piemēram, vārdus, datumus, atrašanās vietas, iemeslus u. c. Varat arī lūgt radošas idejas vai paskaidrojumus. Pēc tam, kad e-pasta vēstule būs ģenerēta, to varēsiet pielāgot un ievietot sastādīšanas lapā. Šeit ir sniegti daži norādījumu paraugi, kas palīdzēs jums sākt darbu:';

$labels['compose_help_example_1'] = 'sasveicināties';
$labels['compose_help_example_2'] = 'šodienas sanāksme ir atcelta';
$labels['compose_help_example_3'] = 'Es sarunāju tikšanos ar Margy trešdien, 15.00, sagatavoju prezentāciju par mākslīgā intelekta izmantošanas priekšrocībām mūsu mārketinga kampaņās, lai pārliecinātu viņu.';
$labels['compose_help_example_4'] = 'mēs nolēmām pievienot atbalstu mākslīgā intelekta radītam saturam, īsi paskaidrojiet, kas tas ir.';
$labels['compose_help_example_5'] = 'turpinājums pagājušās nedēļas darba uzdevumam, man tas vajadzīgs rīt.';
$labels['compose_help_example_6'] = 'lūgt paaugstināt algu, es to nesaņēmu vairāk nekā gadu.';
$labels['compose_help_example_7'] = 'atgādināt, ka rīt jāiesniedz ziņojums, citādi komandai radīsies problēmas ar Margy, kurš ir stresa nomākts.';
$labels['compose_help_example_8'] = 'rakstiet klientiem par 20% atlaidi visiem produktiem līdz otrdienai, mēs pārdodam apģērbu';
$labels['compose_help_example_9'] = 'palūgt uzrakstīt Laurai un pateikt, lai viņa organizē tikšanos Kevina dzimšanas dienā šajā nedēļas nogalē.';
$labels['compose_help_example_10'] = 'jautāt par progresu ar jauno tīmekļa vietni, iekļaut 5 idejas par mārketinga saukļiem priekšējā lapā, mēs veicam automobiļu remontu.';
$labels['compose_help_example_11'] = 'biļetens par jaunajām vpn funkcijām, ko mēs pievienojām: wireguard protokols, port forwarding, kill switch, dedicated IP; paskaidrot, kas tie ir.';
$labels['compose_help_example_12'] = 'biļetens par jaunu lojalitātes programmu, sākotnējās izmaksas ir $10 par karti un pēc tam līdz 20% atlaides lielākajai daļai produktu uz gadu.';
$labels['compose_help_example_13'] = 'sadarbības e-pasta vēstule kādam, kuru apbrīnoju un ar kuru vēlos profesionāli sazināties.';
$labels['compose_help_example_14'] = 'profesionāls paldies par palīdzību ar projektu vakar';
$labels['compose_help_example_15'] = 'nākt klajā ar 10 idejām par stratēģijām, kā izveidot efektīvu e-pasta ziņojumu investoriem, viņa strādās pie tā rīt.';
$labels['compose_help_example_16'] = 'profesionāls atlūguma e-pasts, kurā norādīts 1 mēneša iepriekšējs paziņojums saskaņā ar līgumu, uzņēmuma nosaukums ir ACME.';
$labels['compose_help_example_17'] = 'pārliecinošs e-pasts ar lūgumu paaugstināt amatā, kurā man tiek norādīts, ka es varētu atkāpties no amata, ja nesaņemšu paaugstinājumu, jo ir arī citas iespējas.';
$labels['compose_help_example_18'] = 'Bija patīkami iepazīties ar jums vakardienas konferencē, man patika mūsu tērzēšana, uzturēsim kontaktus.';

$labels['compose_help_use_example'] = '(Izmantojiet šo)';

// summaries
$labels['creating_summary'] = 'Kopsavilkuma izveide...';
$labels['show_summary'] = 'Rādīt kopsavilkumu';
$labels['hide_summary'] = 'Paslēpt kopsavilkumu';
$labels['recreate_summary'] = 'Pārveidot kopsavilkumu';
$labels['email_has_no_text'] = 'Šajā e-pasta vēstulē nav teksta.';

// settings
$labels['setting_compose_style'] = 'Stils';
$labels['setting_compose_length'] = 'Garums';
$labels['setting_compose_language'] = 'Valoda';
$labels['setting_compose_creativity'] = 'Radošums';
$labels['setting_language'] = 'Valoda';

$labels['summarizing_messages'] = 'Ziņojumu apkopošana';
$labels['setting_show_summary_on_mail_view'] = 'Rādīt AI kopsavilkumus, skatot e-pasta ziņojumus';
$labels['setting_show_summary_on_list_hover'] = 'Rādīt mākslīgā intelekta kopsavilkumus, kad uznirstat uz e-pasta saraksta';
$labels['setting_list_summary_placement'] = 'Saraksta kopsavilkuma uznirstošā loga izvietošana';

$labels['above'] = 'Virs';
$labels['below'] = 'Zemāk';
$labels['left'] = 'Kreisā';
$labels['right'] = 'Tiesības';

$labels['ai_disclaimer'] = 'Dažreiz AI izvades rezultāti var būt nepilnīgi vai nepareizi. Vienmēr vēlreiz pārbaudiet būtisko informāciju.';

// errors
$labels['api_error'] = 'Savienošanās laikā ar AI serveri radās kļūda. Lūdzu, sazinieties ar administratoru.';
