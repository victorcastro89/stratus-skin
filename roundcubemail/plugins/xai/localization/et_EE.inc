<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI assistent';
$labels['ai_assistant'] = 'AI assistent';
$labels['ai_assistant_settings'] = 'AI assistendi seaded';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Kirjutage sõnum koos AI-ga';

$labels['compose_label_to'] = 'Saaja nimi';
$labels['compose_label_from'] = 'Saatja nimi';
$labels['compose_label_style'] = 'Stiil';
$labels['compose_label_length'] = 'Pikkus';
$labels['compose_label_language'] = 'Keel';
$labels['compose_label_creativity'] = 'Loovus';
$labels['compose_label_instructions'] = 'Juhised';
$labels['compose_no_change'] = 'Ei ole muutusi';
$labels['compose_instructions_placeholder'] = 'Näide: paluge muuta kohtumine järgmiseks nädalaks.';

$labels['compose_style_assertive'] = 'Assertive';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entusiastlik';
$labels['compose_style_funny'] = 'Naljakas';
$labels['compose_style_informational'] = 'Informatiivne';
$labels['compose_style_professional'] = 'Professionaalne';
$labels['compose_style_urgent'] = 'Kiireloomuline';
$labels['compose_style_witty'] = 'Witty';

$labels['compose_length_short'] = 'Lühike';
$labels['compose_length_medium'] = 'Keskmine';
$labels['compose_length_long'] = 'Pikk';

$labels['compose_creativity_low'] = 'Madal';
$labels['compose_creativity_medium'] = 'Keskmine';
$labels['compose_creativity_high'] = 'Kõrge';

$labels['compose_insert_email'] = 'Sisesta e-kiri';
$labels['compose_generate_email'] = 'Genereeri e-kiri';
$labels['compose_generate_again'] = 'Genereeri uuesti';
$labels['compose_stop_generating'] = 'Lõpeta genereerimine';
$labels['compose_error_help_button'] = 'Abi';

$labels['compose_no_instructions'] = 'Kirjutage juhised.';
$labels['compose_instructions_too_long'] = 'Teie juhised on liiga pikad; need võivad olla maksimaalselt $n tähemärki.';
$labels['compose_empty_response'] = 'Server tagastas tühja vastuse.';

$labels['compose_tip_to'] = 'Uue e-kirja saaja nimi. Saate määrata rohkem kui ühe nime või kasutada stringi nagu "kõik", "meeskond" jne.';
$labels['compose_tip_from'] = 'Uus e-kiri allkirjastatakse selle nimega.';
$labels['compose_tip_style'] = 'Uue e-kirja eelistatud kirjastiil.';
$labels['compose_tip_length'] = 'Uue e-kirja eelistatud pikkus.';
$labels['compose_tip_language'] = 'Keel, milles uus e-kiri tuleks kirjutada. Määrake "No change", et kirjutada keeles, milles on antud juhised.';
$labels['compose_tip_creativity'] = 'Suurem loovus tekitab ettearvamatuid ja mitmekesiseid e-kirju; väiksem loovus tekitab e-kirju, mis on rohkem faktipõhised ja konkreetsed.';
$labels['compose_tip_instructions'] = 'Juhised selle kohta, mida uues e-kirjas lisada. Näidete vaatamiseks klõpsake nuppu Juhendi näited.';

$labels['compose_help_button_show'] = 'Juhendi näited';
$labels['compose_help_button_hide'] = 'Tagasi';
$labels['compose_help_title'] = 'Abi ja näited';
$labels['compose_help_info'] = 'Rääkige AI-le e-kirjast, mida soovite kirjutada. Lisage üksikasjad, nagu nimed, kuupäevad, asukohad, põhjused jne. Võite küsida ka loomingulisi ideid või selgitusi. Pärast e-kirja genereerimist saate seda sättida ja sisestada selle koostamislehele. Siin on mõned näidisjuhised, et alustada:';

$labels['compose_help_example_1'] = 'tere tulemast';
$labels['compose_help_example_2'] = 'tänane koosolek on tühistatud';
$labels['compose_help_example_3'] = 'Ma määrasin Margyga kohtumise kolmeks õhtul, valmistan ette esitluse AI kasutamise eelistest meie turunduskampaaniates, et teda veenda.';
$labels['compose_help_example_4'] = 'otsustasime lisada toetuse tehisintellekti loodud sisule, selgitame lühidalt, mis see on';
$labels['compose_help_example_5'] = 'eelmise nädala tööülesande järelkontroll, mul on seda homme vaja.';
$labels['compose_help_example_6'] = 'paluda palgakõrgendust, ma ei saanud seda üle aasta.';
$labels['compose_help_example_7'] = 'tuletage meelde, et esitage aruanne homme, muidu satub meeskond hätta Margyga, kes on stressis';
$labels['compose_help_example_8'] = 'kirjutage klientidele 20% allahindlusest kõikidele toodetele kuni teisipäevani, müüme riideid';
$labels['compose_help_example_9'] = 'paluda kirjutada Laurale ja öelda, et ta korraldaks sel nädalavahetusel Kevini sünnipäeva puhul koosviibimise.';
$labels['compose_help_example_10'] = 'küsige uue veebilehe edusammude kohta, lisage 5 ideed turunduslauseks esilehele, teeme autoremonti';
$labels['compose_help_example_11'] = 'uudiskiri uutest vpn-funktsioonidest, mida me lisasime: wireguard protokoll, port forwarding, kill switch, dedicated IP; selgitage, mida need endast kujutavad.';
$labels['compose_help_example_12'] = 'uudiskiri uuest püsikliendiprogrammist, esialgne maksumus on 10 dollarit kaardile ja seejärel kuni 20% allahindlusi enamikule toodetele aasta jooksul.';
$labels['compose_help_example_13'] = 'võrgustiku loomise e-kiri kellelegi, keda ma imetlen ja kellega ma tahan tööalaselt suhelda.';
$labels['compose_help_example_14'] = 'professionaalne aitäh eilse projekti abi eest';
$labels['compose_help_example_15'] = 'tulla välja 10 ideed strateegiate kohta, kuidas koostada investoritele tõhusat e-kirja pigi, ta töötab selle kallal homme.';
$labels['compose_help_example_16'] = 'professionaalne lahkumismeil, milles esitatakse 1 kuu etteteatamisaeg vastavalt lepingule, ettevõtte nimi on ACME.';
$labels['compose_help_example_17'] = 'veenev e-kiri, milles taotlen edutamist, vihjates, et kui ma seda ei saa, siis võin tagasi astuda, sest on teisi võimalusi';
$labels['compose_help_example_18'] = 'oli hea kohtuda sinuga eile konverentsil, nautisin meie vestlust, jääme ühendust võtma.';

$labels['compose_help_use_example'] = '(Kasutage seda)';

// summaries
$labels['creating_summary'] = 'Kokkuvõtte loomine...';
$labels['show_summary'] = 'Näita kokkuvõtet';
$labels['hide_summary'] = 'Peida kokkuvõte';
$labels['recreate_summary'] = 'Uuesti luua kokkuvõte';
$labels['email_has_no_text'] = 'Sellel e-kirjal ei ole teksti.';

// settings
$labels['setting_compose_style'] = 'Stiil';
$labels['setting_compose_length'] = 'Pikkus';
$labels['setting_compose_language'] = 'Keel';
$labels['setting_compose_creativity'] = 'Loovus';
$labels['setting_language'] = 'Keel';

$labels['summarizing_messages'] = 'Sõnumite kokkuvõte';
$labels['setting_show_summary_on_mail_view'] = 'E-kirjade vaatamisel AI kokkuvõtete kuvamine';
$labels['setting_show_summary_on_list_hover'] = 'Näita tehisintellekti kokkuvõtteid, kui viibid e-kirjade loendi kohal.';
$labels['setting_list_summary_placement'] = 'Nimekirja kokkuvõtte hüpikakna paigutamine';

$labels['above'] = 'Üleval';
$labels['below'] = 'Allpool';
$labels['left'] = 'Vasakpoolne';
$labels['right'] = 'Õigus';

$labels['ai_disclaimer'] = 'AI väljund võib mõnikord olla ebatäielik või ebaõige. Kontrollige alati olulist teavet kaks korda.';

// errors
$labels['api_error'] = 'AI-serveriga ühenduse loomisel tekkis viga. Palun võtke ühendust administraatoriga.';
