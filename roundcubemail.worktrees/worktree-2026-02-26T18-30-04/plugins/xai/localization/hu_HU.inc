<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI asszisztens';
$labels['ai_assistant'] = 'AI asszisztens';
$labels['ai_assistant_settings'] = 'AI Assistant beállítások';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Üzenet írása AI-val';

$labels['compose_label_to'] = 'A címzett neve';
$labels['compose_label_from'] = 'A feladó neve';
$labels['compose_label_style'] = 'Stílus';
$labels['compose_label_length'] = 'Hosszúság';
$labels['compose_label_language'] = 'Nyelv';
$labels['compose_label_creativity'] = 'Kreativitás';
$labels['compose_label_instructions'] = 'Utasítások';
$labels['compose_no_change'] = 'Nincs változás';
$labels['compose_instructions_placeholder'] = 'Példa: kérje a jövő heti időpont megváltoztatását.';

$labels['compose_style_assertive'] = 'Asszertív';
$labels['compose_style_casual'] = 'Alkalmi';
$labels['compose_style_enthusiastic'] = 'Lelkes';
$labels['compose_style_funny'] = 'Vicces';
$labels['compose_style_informational'] = 'Tájékoztató';
$labels['compose_style_professional'] = 'Szakmai';
$labels['compose_style_urgent'] = 'Sürgős';
$labels['compose_style_witty'] = 'Szellemes';

$labels['compose_length_short'] = 'Rövid';
$labels['compose_length_medium'] = 'Közepes';
$labels['compose_length_long'] = 'Hosszú';

$labels['compose_creativity_low'] = 'Alacsony';
$labels['compose_creativity_medium'] = 'Közepes';
$labels['compose_creativity_high'] = 'Magas';

$labels['compose_insert_email'] = 'E-mail beillesztése';
$labels['compose_generate_email'] = 'E-mail generálása';
$labels['compose_generate_again'] = 'Újra generálni';
$labels['compose_stop_generating'] = 'Generálás leállítása';
$labels['compose_error_help_button'] = 'Segítség';

$labels['compose_no_instructions'] = 'Írja be az utasításokat.';
$labels['compose_instructions_too_long'] = 'Az utasításod túl hosszú; legfeljebb $n karakter lehet.';
$labels['compose_empty_response'] = 'A kiszolgáló üres választ küldött vissza.';

$labels['compose_tip_to'] = 'Az új e-mail címzettjének neve. Több nevet is megadhat, vagy olyan karakterláncokat is használhat, mint "mindenki", "csapat" stb.';
$labels['compose_tip_from'] = 'Az új e-mailt ezzel a névvel írja alá.';
$labels['compose_tip_style'] = 'Az új e-mail preferált írásmódja.';
$labels['compose_tip_length'] = 'Az új e-mail kívánt hossza.';
$labels['compose_tip_language'] = 'A nyelv, amelyen az új e-mailt meg kell írni. A "Nincs változás" beállítással az utasítások nyelvén írja az e-mailt.';
$labels['compose_tip_creativity'] = 'A magasabb kreativitás kiszámíthatatlanabb és változatosabb e-maileket generál; az alacsonyabb kreativitás inkább tényszerű és lényegre törő e-maileket fog generálni.';
$labels['compose_tip_instructions'] = 'Útmutató arra vonatkozóan, hogy mit tartalmazzon az új e-mail. Kattintson az Utasítás példák gombra néhány példa megtekintéséhez.';

$labels['compose_help_button_show'] = 'Utasítás példák';
$labels['compose_help_button_hide'] = 'Vissza';
$labels['compose_help_title'] = 'Segítség és példák';
$labels['compose_help_info'] = 'Mondja el a mesterséges intelligenciának, hogy milyen e-mailt szeretne írni. Adjon meg konkrétumokat, például neveket, dátumokat, helyszíneket, okokat stb. Kreatív ötleteket vagy magyarázatokat is kérhet. Miután az e-mail elkészült, finomíthat rajta, és beillesztheti a kompozíciós oldalra. Íme néhány minta utasítás, hogy elkezdhesse:';

$labels['compose_help_example_1'] = 'köszönj szépen';
$labels['compose_help_example_2'] = 'a mai találkozót törölték';
$labels['compose_help_example_3'] = 'Összehoztam egy találkozót Margyval szerda délután 3-ra, előkészítek egy prezentációt az AI használatának előnyeiről a marketingkampányainkban, hogy meggyőzzem őt.';
$labels['compose_help_example_4'] = 'úgy döntöttünk, hogy hozzáadjuk az AI generált tartalom támogatását, röviden elmagyarázzuk, hogy mi ez';
$labels['compose_help_example_5'] = 'a múlt heti munkafeladat nyomon követése, holnapra kell.';
$labels['compose_help_example_6'] = 'fizetésemelést kérek, több mint egy évig nem kaptam.';
$labels['compose_help_example_7'] = 'emlékeztessük, hogy holnap be kell nyújtani a jelentést, különben a csapat bajba kerül Margyval, aki stresszes.';
$labels['compose_help_example_8'] = 'írja meg a vásárlóknak, hogy keddig 20% kedvezményt adunk minden termékre, ruhákat árulunk.';
$labels['compose_help_example_9'] = 'megkérem, hogy írjon Laurának, és mondja meg neki, hogy szervezzen egy összejövetelt Kevin születésnapjára a hétvégén.';
$labels['compose_help_example_10'] = 'kérdezzünk az új honlap előrehaladásáról, 5 marketing szlogen ötletet a címlapra, autójavítást végzünk.';
$labels['compose_help_example_11'] = 'hírlevél az általunk hozzáadott új vpn funkciókról: wireguard protokoll, port forwarding, kill switch, dedikált IP; magyarázza el, hogy mik ezek.';
$labels['compose_help_example_12'] = 'hírlevél egy új hűségprogramról, a kezdeti költség 10 dollár a kártyáért, majd egy évig akár 20%-os kedvezmény a legtöbb termékre.';
$labels['compose_help_example_13'] = 'kapcsolatépítő e-mail valakinek, akit csodálok és akivel szakmai kapcsolatot szeretnék kialakítani';
$labels['compose_help_example_14'] = 'egy szakmai köszönet a tegnapi projekthez nyújtott segítségért';
$labels['compose_help_example_15'] = '10 ötletet dolgozott ki a befektetőknek szóló hatékony e-mailes pályázat elkészítéséhez, holnap dolgozik rajta.';
$labels['compose_help_example_16'] = 'egy professzionális felmondó e-mailt, amelyben a szerződésnek megfelelően 1 hónapos felmondási időt adok meg, a cég neve ACME';
$labels['compose_help_example_17'] = 'kényszerítő e-mail, amelyben előléptetést kérek, utalva arra, hogy lemondhatok, ha nem kapom meg, mert vannak más lehetőségek is.';
$labels['compose_help_example_18'] = 'jó volt találkozni veled a tegnapi konferencián, élveztem a beszélgetésünket, maradjunk kapcsolatban!';

$labels['compose_help_use_example'] = '(Használja ezt)';

// summaries
$labels['creating_summary'] = 'Összefoglaló készítése...';
$labels['show_summary'] = 'Összefoglaló megjelenítése';
$labels['hide_summary'] = 'Összefoglaló elrejtése';
$labels['recreate_summary'] = 'Összefoglaló újbóli létrehozása';
$labels['email_has_no_text'] = 'Ennek az e-mailnek nincs szövege.';

// settings
$labels['setting_compose_style'] = 'Stílus';
$labels['setting_compose_length'] = 'Hosszúság';
$labels['setting_compose_language'] = 'Nyelv';
$labels['setting_compose_creativity'] = 'Kreativitás';
$labels['setting_language'] = 'Nyelv';

$labels['summarizing_messages'] = 'Üzenetek összefoglalása';
$labels['setting_show_summary_on_mail_view'] = 'AI összefoglalók megjelenítése e-mailek megtekintésekor';
$labels['setting_show_summary_on_list_hover'] = 'AI összefoglalók megjelenítése az e-mail lista fölött történő lebegéskor';
$labels['setting_list_summary_placement'] = 'Lista összefoglaló felugró ablak elhelyezése';

$labels['above'] = 'Fentebb';
$labels['below'] = 'A  alatt.';
$labels['left'] = 'Balra';
$labels['right'] = 'Jobbra';

$labels['ai_disclaimer'] = 'A mesterséges intelligencia kimenete néha hiányos vagy hibás lehet. Mindig ellenőrizze kétszer a lényeges információkat.';

// errors
$labels['api_error'] = 'Hiba történt az AI-kiszolgálóhoz való csatlakozás során. Kérjük, lépjen kapcsolatba a rendszergazdával.';
