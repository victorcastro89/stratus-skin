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
$labels['ai_assistant_settings'] = 'AI Assistant -asetukset';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Laadi viesti tekoälyn avulla';

$labels['compose_label_to'] = 'Vastaanottajan nimi';
$labels['compose_label_from'] = 'Lähettäjän nimi';
$labels['compose_label_style'] = 'Tyyli';
$labels['compose_label_length'] = 'Pituus';
$labels['compose_label_language'] = 'Kieli';
$labels['compose_label_creativity'] = 'Luovuus';
$labels['compose_label_instructions'] = 'Ohjeet';
$labels['compose_no_change'] = 'Ei muutosta';
$labels['compose_instructions_placeholder'] = 'Esimerkki: pyydä tapaamisen vaihtamista ensi viikolle.';

$labels['compose_style_assertive'] = 'Assertive';
$labels['compose_style_casual'] = 'Rento';
$labels['compose_style_enthusiastic'] = 'Innostunut';
$labels['compose_style_funny'] = 'Hauska';
$labels['compose_style_informational'] = 'Tietoa';
$labels['compose_style_professional'] = 'Ammattilainen';
$labels['compose_style_urgent'] = 'Kiireellinen';
$labels['compose_style_witty'] = 'Nokkela';

$labels['compose_length_short'] = 'Lyhyt';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Pitkä';

$labels['compose_creativity_low'] = 'Matala';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = 'Korkea';

$labels['compose_insert_email'] = 'Lisää sähköpostiosoite';
$labels['compose_generate_email'] = 'Luo sähköpostiosoite';
$labels['compose_generate_again'] = 'Luo uudelleen';
$labels['compose_stop_generating'] = 'Lopeta tuottaminen';
$labels['compose_error_help_button'] = 'Apua';

$labels['compose_no_instructions'] = 'Kirjoita ohjeet.';
$labels['compose_instructions_too_long'] = 'Ohjeesi ovat liian pitkiä; ne voivat olla enintään $n merkkiä.';
$labels['compose_empty_response'] = 'Palvelin palautti tyhjän vastauksen.';

$labels['compose_tip_to'] = 'Uuden sähköpostin vastaanottajan nimi. Voit määrittää useamman kuin yhden nimen tai käyttää merkkijonoja, kuten "everybody", "team" jne.';
$labels['compose_tip_from'] = 'Uusi sähköposti allekirjoitetaan tällä nimellä.';
$labels['compose_tip_style'] = 'Uuden sähköpostin suositeltava kirjoitustyyli.';
$labels['compose_tip_length'] = 'Uuden sähköpostin haluttu pituus.';
$labels['compose_tip_language'] = 'Kieli, jolla uusi sähköposti on kirjoitettava. Määritä "Ei muutosta", jos haluat kirjoittaa sillä kielellä, jolla ohjeet on annettu.';
$labels['compose_tip_creativity'] = 'Korkeampi luovuus tuottaa arvaamattomampia ja monipuolisempia sähköposteja, matalampi luovuus tuottaa asiallisempia ja ytimekkäämpiä sähköposteja.';
$labels['compose_tip_instructions'] = 'Ohjeet uuden sähköpostin sisällöstä. Napsauta Ohje-esimerkkejä-painiketta nähdäksesi joitakin esimerkkejä.';

$labels['compose_help_button_show'] = 'Esimerkkejä ohjeista';
$labels['compose_help_button_hide'] = 'Takaisin';
$labels['compose_help_title'] = 'Apua & esimerkkejä';
$labels['compose_help_info'] = 'Kerro tekoälylle sähköpostiviestistä, jonka haluat kirjoittaa. Sisällytä yksityiskohtia, kuten nimet, päivämäärät, paikat, syyt jne. Voit myös pyytää luovia ideoita tai selityksiä. Kun sähköposti on luotu, voit muokata sitä ja lisätä sen kirjoitussivulle. Tässä on muutamia esimerkkiohjeita, joiden avulla pääset alkuun:';

$labels['compose_help_example_1'] = 'tervehdi';
$labels['compose_help_example_2'] = 'tämänpäiväinen kokous on peruttu';
$labels['compose_help_example_3'] = 'Järjestän tapaamisen Margyn kanssa ke klo 15.00, valmistelen esityksen tekoälyn käytön eduista markkinointikampanjoissamme vakuuttaakseni hänet.';
$labels['compose_help_example_4'] = 'päätimme lisätä tuen tekoälyn tuottamalle sisällölle, selitä lyhyesti, mitä se on.';
$labels['compose_help_example_5'] = 'viime viikon työtehtävän seuranta, tarvitsen sen huomenna.';
$labels['compose_help_example_6'] = 'Pyydän palkankorotusta, en saanut sitä yli vuoteen.';
$labels['compose_help_example_7'] = 'muistuttakaa, että raportti on toimitettava huomenna, tai joukkue joutuu vaikeuksiin Margyn kanssa, joka on stressaantunut.';
$labels['compose_help_example_8'] = 'kirjoittaa asiakkaille 20% alennus kaikista tuotteista tiistaihin asti, myymme vaatteita';
$labels['compose_help_example_9'] = 'pyytää kirjoittamaan Lauralle ja pyytämään häntä järjestämään tapaamisen Kevinin syntymäpäivänä tänä viikonloppuna.';
$labels['compose_help_example_10'] = 'kysy uuden verkkosivuston edistymisestä, ota mukaan 5 ideaa etusivun markkinointisloganiksi, teemme autojen korjauksia.';
$labels['compose_help_example_11'] = 'uutiskirje uusista vpn-ominaisuuksista, joita lisäsimme: wireguard-protokolla, portin välitys, kill switch, oma IP; selitä, mitä ne ovat.';
$labels['compose_help_example_12'] = 'uutiskirje uudesta kanta-asiakasohjelmasta, kortti maksaa aluksi 10 dollaria ja sen jälkeen jopa 20 prosentin alennukset useimmista tuotteista vuoden ajan.';
$labels['compose_help_example_13'] = 'verkostoitumissähköposti henkilölle, jota ihailen ja johon haluan olla yhteydessä ammatillisesti.';
$labels['compose_help_example_14'] = 'ammattimainen kiitos avusta eilisessä projektissa';
$labels['compose_help_example_15'] = 'keksiä 10 ideaa strategioista, joiden avulla voi laatia tehokkaan sähköpostiviestin sijoittajille, hän työstää sitä huomenna.';
$labels['compose_help_example_16'] = 'ammattimainen irtisanoutumissähköposti, jossa ilmoitetaan 1 kuukauden irtisanomisaika sopimuksen mukaisesti, yrityksen nimi on ACME.';
$labels['compose_help_example_17'] = 'pakottava sähköpostiviesti, jossa pyydän ylennystä ja vihjailen, että saatan irtisanoutua, jos en saa sitä, koska on muitakin mahdollisuuksia.';
$labels['compose_help_example_18'] = 'oli mukava tavata sinut eilisessä konferenssissa, nautin keskustelustamme, pysytään yhteyksissä';

$labels['compose_help_use_example'] = '(Käytä tätä)';

// summaries
$labels['creating_summary'] = 'Yhteenvedon luominen...';
$labels['show_summary'] = 'Näytä yhteenveto';
$labels['hide_summary'] = 'Piilota yhteenveto';
$labels['recreate_summary'] = 'Luo yhteenveto uudelleen';
$labels['email_has_no_text'] = 'Tässä sähköpostissa ei ole tekstiä.';

// settings
$labels['setting_compose_style'] = 'Tyyli';
$labels['setting_compose_length'] = 'Pituus';
$labels['setting_compose_language'] = 'Kieli';
$labels['setting_compose_creativity'] = 'Luovuus';
$labels['setting_language'] = 'Kieli';

$labels['summarizing_messages'] = 'Yhteenveto viesteistä';
$labels['setting_show_summary_on_mail_view'] = 'Näytä tekoälyn yhteenvedot sähköposteja katseltaessa';
$labels['setting_show_summary_on_list_hover'] = 'Näytä tekoälytiivistelmät, kun viet hiiren kurssi viedään sähköpostilistan päälle.';
$labels['setting_list_summary_placement'] = 'Luettelon yhteenvedon ponnahdusikkunan sijoittaminen';

$labels['above'] = 'Edellä';
$labels['below'] = 'Alla';
$labels['left'] = 'Vasen';
$labels['right'] = 'Oikea';

$labels['ai_disclaimer'] = 'Tekoälyn tuotos voi joskus olla epätäydellinen tai virheellinen. Tarkista olennaiset tiedot aina kahdesti.';

// errors
$labels['api_error'] = 'AI-palvelimeen muodostettaessa yhteyttä tapahtui virhe. Ota yhteyttä järjestelmänvalvojaan.';
