<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI asistentas';
$labels['ai_assistant'] = 'AI asistentas';
$labels['ai_assistant_settings'] = 'AI asistento nustatymai';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Sukurti žinutę su dirbtiniu intelektu';

$labels['compose_label_to'] = 'Gavėjo vardas ir pavardė';
$labels['compose_label_from'] = 'Siuntėjo vardas ir pavardė';
$labels['compose_label_style'] = 'Stilius';
$labels['compose_label_length'] = 'Ilgis';
$labels['compose_label_language'] = 'Kalba';
$labels['compose_label_creativity'] = 'Kūrybiškumas';
$labels['compose_label_instructions'] = 'Instrukcijos';
$labels['compose_no_change'] = 'Be pakeitimų';
$labels['compose_instructions_placeholder'] = 'Pavyzdys: paprašykite pakeisti susitikimo datą kitai savaitei.';

$labels['compose_style_assertive'] = 'Užtikrintas';
$labels['compose_style_casual'] = 'Atsitiktinis';
$labels['compose_style_enthusiastic'] = 'Entuziastingas';
$labels['compose_style_funny'] = 'Juokinga';
$labels['compose_style_informational'] = 'Informacinis';
$labels['compose_style_professional'] = 'Profesionalus';
$labels['compose_style_urgent'] = 'Skubus';
$labels['compose_style_witty'] = 'Šmaikštus';

$labels['compose_length_short'] = 'Trumpas';
$labels['compose_length_medium'] = 'Vidutinis';
$labels['compose_length_long'] = 'Ilgas';

$labels['compose_creativity_low'] = 'Žemas';
$labels['compose_creativity_medium'] = 'Vidutinis';
$labels['compose_creativity_high'] = 'Aukštas';

$labels['compose_insert_email'] = 'Įterpti el. paštą';
$labels['compose_generate_email'] = 'Sukurti el. paštą';
$labels['compose_generate_again'] = 'Vėl kurti';
$labels['compose_stop_generating'] = 'Sustabdyti generavimą';
$labels['compose_error_help_button'] = 'Pagalba';

$labels['compose_no_instructions'] = 'Įveskite instrukcijas.';
$labels['compose_instructions_too_long'] = 'Jūsų instrukcijos yra per ilgos; jos gali būti ne ilgesnės kaip $n simbolių.';
$labels['compose_empty_response'] = 'Serveris grąžino tuščią atsakymą.';

$labels['compose_tip_to'] = 'Naujo el. laiško gavėjo vardas. Galite nurodyti daugiau nei vieną vardą arba naudoti tokias eilutes kaip "visi", "komanda" ir pan.';
$labels['compose_tip_from'] = 'Naujasis el. laiškas bus pasirašytas šiuo vardu.';
$labels['compose_tip_style'] = 'pageidaujamas naujo el. pašto rašymo stilius.';
$labels['compose_tip_length'] = 'pageidaujamas naujo el. laiško ilgis.';
$labels['compose_tip_language'] = 'Kalba, kuria turėtų būti parašytas naujas el. laiškas. Nurodykite "Nekeisti", jei norite rašyti ta kalba, kuria pateikti nurodymai.';
$labels['compose_tip_creativity'] = 'Didesnio kūrybiškumo el. laiškai bus labiau nenuspėjami ir įvairesni, o mažesnio kūrybiškumo el. laiškai bus labiau faktiniai ir dalykiški.';
$labels['compose_tip_instructions'] = 'nurodymai, ką įtraukti į naują el. laišką. Spustelėkite mygtuką Instrukcijų pavyzdžiai, kad pamatytumėte keletą pavyzdžių.';

$labels['compose_help_button_show'] = 'Instrukcijų pavyzdžiai';
$labels['compose_help_button_hide'] = 'Atgal';
$labels['compose_help_title'] = 'Pagalba ir pavyzdžiai';
$labels['compose_help_info'] = 'Papasakokite dirbtiniam intelektui apie el. laišką, kurį norėtumėte parašyti. Įtraukite konkrečius duomenis, pavyzdžiui, vardus, datas, vietas, priežastis ir pan. Taip pat galite paprašyti kūrybinių idėjų ar paaiškinimų. Sugeneravę el. laišką, galėsite jį pakoreguoti ir įterpti į laiško sudarymo puslapį. Štai keli pavyzdiniai nurodymai, kurie padės jums pradėti:';

$labels['compose_help_example_1'] = 'pasisveikinti';
$labels['compose_help_example_2'] = 'šiandienos susitikimas buvo atšauktas';
$labels['compose_help_example_3'] = 'Trečiadienį 3 val. po pietų susitinku su Margy, parengiu pristatymą apie dirbtinio intelekto naudojimo mūsų rinkodaros kampanijose privalumus, kad įtikinčiau ją.';
$labels['compose_help_example_4'] = 'nusprendėme pridėti dirbtinio intelekto sukurto turinio palaikymą, trumpai paaiškinkite, kas tai yra';
$labels['compose_help_example_5'] = 'tolesni veiksmai, susiję su praėjusios savaitės darbo užduotimi, man jos reikia rytoj.';
$labels['compose_help_example_6'] = 'paprašyti pakelti atlyginimą, aš jo negavau daugiau nei metus.';
$labels['compose_help_example_7'] = 'priminti, kad ataskaitą reikia pateikti rytoj, nes kitaip komanda turės problemų su stresą patiriančia Margy.';
$labels['compose_help_example_8'] = 'rašykite klientams apie 20 % nuolaidą visiems produktams iki antradienio, mes parduodame drabužius';
$labels['compose_help_example_9'] = 'paprašyti parašyti Laurai ir pasakyti, kad ji suorganizuotų susitikimą per Kevino gimtadienį šį savaitgalį.';
$labels['compose_help_example_10'] = 'paklausti apie naujos svetainės pažangą, įtraukti 5 idėjas dėl rinkodaros šūkių pirmajame puslapyje, mes atliekame automobilių remontą';
$labels['compose_help_example_11'] = 'naujienlaiškis apie naujas vpn funkcijas, kurias pridėjome: wireguard protokolas, prievadų persiuntimas, "kill switch", dedikuotas IP; paaiškinkite, kas tai yra.';
$labels['compose_help_example_12'] = 'naujienlaiškis apie naują lojalumo programą, kurios pradinė kaina - 10 USD už kortelę, o po to iki 20 % nuolaidų daugumai produktų per metus.';
$labels['compose_help_example_13'] = 'tinklo laiškas žmogui, kuriuo žaviuosi ir su kuriuo noriu užmegzti profesinį ryšį.';
$labels['compose_help_example_14'] = 'profesionalus ačiū už pagalbą vykdant vakarykštį projektą';
$labels['compose_help_example_15'] = 'sugalvojo 10 strategijų, kaip parengti veiksmingą el. laišką investuotojams, ir rytoj jį parengs.';
$labels['compose_help_example_16'] = 'profesionalus atsistatydinimo elektroninis laiškas, kuriuo pagal sutartį įspėjama prieš 1 mėnesį, įmonė vadinasi ACME.';
$labels['compose_help_example_17'] = 'įtikinamas el. laiškas, kuriame prašoma paaukštinimo ir užsimenama, kad galiu atsistatydinti, jei negausiu paaukštinimo, nes yra kitų galimybių.';
$labels['compose_help_example_18'] = 'Buvo malonu susitikti su jumis vakar konferencijoje, man patiko mūsų pokalbis, palaikykime ryšį';

$labels['compose_help_use_example'] = '(Naudokite tai)';

// summaries
$labels['creating_summary'] = 'Santraukos kūrimas...';
$labels['show_summary'] = 'Rodyti santrauką';
$labels['hide_summary'] = 'Paslėpti santrauką';
$labels['recreate_summary'] = 'Iš naujo sukurti santrauką';
$labels['email_has_no_text'] = 'Šiame el. laiške nėra teksto.';

// settings
$labels['setting_compose_style'] = 'Stilius';
$labels['setting_compose_length'] = 'Ilgis';
$labels['setting_compose_language'] = 'Kalba';
$labels['setting_compose_creativity'] = 'Kūrybiškumas';
$labels['setting_language'] = 'Kalba';

$labels['summarizing_messages'] = 'Pranešimų apibendrinimas';
$labels['setting_show_summary_on_mail_view'] = 'Rodyti AI santraukas peržiūrint el. laiškus';
$labels['setting_show_summary_on_list_hover'] = 'Rodyti dirbtinio intelekto santraukas užvedus pelės žymeklį ant el. pašto sąrašo';
$labels['setting_list_summary_placement'] = 'Sąrašo santraukos iššokančio lango talpinimas';

$labels['above'] = 'Virš';
$labels['below'] = 'Žemiau';
$labels['left'] = 'Kairėje pusėje';
$labels['right'] = 'Dešinė';

$labels['ai_disclaimer'] = 'AI išvestis kartais gali būti neišsami arba neteisinga. Visada dukart patikrinkite svarbiausią informaciją.';

// errors
$labels['api_error'] = 'Jungiantis prie AI serverio įvyko klaida. Kreipkitės į administratorių.';
