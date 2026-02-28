<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI-assistent';
$labels['ai_assistant'] = 'AI-assistent';
$labels['ai_assistant_settings'] = 'Innstillinger for AI-assistent';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Skriv en melding med AI';

$labels['compose_label_to'] = 'Mottakerens navn';
$labels['compose_label_from'] = 'Avsenderens navn';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Lengde';
$labels['compose_label_language'] = 'Språk';
$labels['compose_label_creativity'] = 'Kreativitet';
$labels['compose_label_instructions'] = 'Instruksjoner';
$labels['compose_no_change'] = 'Ingen endring';
$labels['compose_instructions_placeholder'] = 'Eksempel: be om å endre avtalen til neste uke.';

$labels['compose_style_assertive'] = 'Selvsikker';
$labels['compose_style_casual'] = 'Uformelt';
$labels['compose_style_enthusiastic'] = 'Entusiastisk';
$labels['compose_style_funny'] = 'Morsomt';
$labels['compose_style_informational'] = 'Informasjon';
$labels['compose_style_professional'] = 'Profesjonell';
$labels['compose_style_urgent'] = 'Haster';
$labels['compose_style_witty'] = 'Vittig';

$labels['compose_length_short'] = 'Kort';
$labels['compose_length_medium'] = 'Middels';
$labels['compose_length_long'] = 'Lang';

$labels['compose_creativity_low'] = 'Lav';
$labels['compose_creativity_medium'] = 'Middels';
$labels['compose_creativity_high'] = 'Høyt';

$labels['compose_insert_email'] = 'Sett inn e-post';
$labels['compose_generate_email'] = 'Generer e-post';
$labels['compose_generate_again'] = 'Generere igjen';
$labels['compose_stop_generating'] = 'Slutt å generere';
$labels['compose_error_help_button'] = 'Hjelp';

$labels['compose_no_instructions'] = 'Skriv inn instruksjonene.';
$labels['compose_instructions_too_long'] = 'Instruksjonene dine er for lange; de kan maksimalt være på $n tegn.';
$labels['compose_empty_response'] = 'Serveren returnerte et tomt svar.';

$labels['compose_tip_to'] = 'Navnet på mottakeren av den nye e-posten. Du kan angi mer enn ett navn, eller bruke strenger som "alle", "team" osv.';
$labels['compose_tip_from'] = 'Den nye e-posten vil bli signert med dette navnet.';
$labels['compose_tip_style'] = 'Den foretrukne skrivestilen for den nye e-posten.';
$labels['compose_tip_length'] = 'Den foretrukne lengden på den nye e-posten.';
$labels['compose_tip_language'] = 'Språket den nye e-posten skal skrives på. Angi "Ingen endring" for å skrive på det språket instruksjonene er gitt på.';
$labels['compose_tip_creativity'] = 'Høyere kreativitet vil generere mer uforutsigbare og varierte e-poster; lavere kreativitet vil generere e-poster som er mer saklige og poengterte.';
$labels['compose_tip_instructions'] = 'Instruksjoner om hva som skal inkluderes i den nye e-posten. Klikk på knappen Instruksjonseksempler for å se noen eksempler.';

$labels['compose_help_button_show'] = 'Eksempler på instruksjoner';
$labels['compose_help_button_hide'] = 'Tilbake';
$labels['compose_help_title'] = 'Hjelp og eksempler';
$labels['compose_help_info'] = 'Fortell AI-en om e-posten du ønsker å skrive. Ta med detaljer, som navn, datoer, steder, grunner osv. Du kan også be om kreative ideer eller forklaringer. Etter at e-posten er generert, kan du finjustere den og sette den inn på komponeringssiden. Her er noen eksempler på instruksjoner for å komme i gang:';

$labels['compose_help_example_1'] = 'si hei';
$labels['compose_help_example_2'] = 'dagens møte er avlyst';
$labels['compose_help_example_3'] = 'Jeg avtalte et møte med Margy onsdag kl. 15.00 og forberedte en presentasjon om fordelene ved å bruke AI i markedsføringskampanjene våre for å overbevise henne.';
$labels['compose_help_example_4'] = 'vi bestemte oss for å legge til støtte for AI-generert innhold, forklar kort hva det er';
$labels['compose_help_example_5'] = 'Oppfølging av arbeidsoppgaven fra forrige uke, jeg trenger den i morgen.';
$labels['compose_help_example_6'] = 'be om lønnsforhøyelse, jeg har ikke fått det på over ett år.';
$labels['compose_help_example_7'] = 'minne om å levere rapporten i morgen, ellers får teamet problemer med Margy som er stresset.';
$labels['compose_help_example_8'] = 'skriv kundene om 20% rabatt på alle produkter til tirsdag, vi selger klær';
$labels['compose_help_example_9'] = 'be om å skrive til Laura og be henne om å organisere en sammenkomst for Kevins bursdag denne helgen.';
$labels['compose_help_example_10'] = 'spør om fremdriften med det nye nettstedet, ta med 5 ideer til markedsføringsslagord for forsiden, vi gjør bilreparasjoner';
$labels['compose_help_example_11'] = 'nyhetsbrev om de nye vpn-funksjonene vi har lagt til: wireguard-protokoll, port videresending, kill switch, dedikert IP; forklar hva de er';
$labels['compose_help_example_12'] = 'nyhetsbrev om et nytt lojalitetsprogram, innledende kostnad er $ 10 for kortet og deretter opptil 20% rabatt på de fleste produkter i et år';
$labels['compose_help_example_13'] = 'nettverks-e-post til noen jeg beundrer og ønsker å komme i kontakt med i jobbsammenheng';
$labels['compose_help_example_14'] = 'en profesjonell takk for hjelpen med prosjektet i går';
$labels['compose_help_example_15'] = 'komme opp med 10 ideer til strategier for å lage en effektiv e-post-pitch til investorene, vil hun jobbe med det i morgen.';
$labels['compose_help_example_16'] = 'en profesjonell oppsigelses-e-post, med 1 måneds varsel i henhold til kontrakten, selskapet heter ACME';
$labels['compose_help_example_17'] = 'overbevisende e-post for å be om en forfremmelse, med hint om at jeg kan si opp hvis jeg ikke får den fordi det er andre muligheter';
$labels['compose_help_example_18'] = 'det var hyggelig å møte deg på konferansen i går, jeg likte praten vår, la oss holde kontakten';

$labels['compose_help_use_example'] = '(Bruk denne)';

// summaries
$labels['creating_summary'] = 'Skaper sammendrag...';
$labels['show_summary'] = 'Vis sammendrag';
$labels['hide_summary'] = 'Skjul sammendrag';
$labels['recreate_summary'] = 'Gjenopprett sammendraget';
$labels['email_has_no_text'] = 'Denne e-posten har ingen tekst.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Lengde';
$labels['setting_compose_language'] = 'Språk';
$labels['setting_compose_creativity'] = 'Kreativitet';
$labels['setting_language'] = 'Språk';

$labels['summarizing_messages'] = 'Oppsummering av meldinger';
$labels['setting_show_summary_on_mail_view'] = 'Vis AI-sammendrag når du viser e-post';
$labels['setting_show_summary_on_list_hover'] = 'Vis AI-sammendrag når du holder musepekeren over e-postlisten';
$labels['setting_list_summary_placement'] = 'Plassering av popup-vindu for listeoversikt';

$labels['above'] = 'Ovenfor';
$labels['below'] = 'Nedenfor';
$labels['left'] = 'Venstre';
$labels['right'] = 'Høyre';

$labels['ai_disclaimer'] = 'AI-utdata kan noen ganger være ufullstendige eller feil. Dobbeltsjekk alltid viktig informasjon.';

// errors
$labels['api_error'] = 'Det oppstod en feil under tilkobling til AI-serveren. Vennligst kontakt administratoren.';
