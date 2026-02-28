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
$labels['ai_assistant_settings'] = 'Instellingen AI-assistent';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Bericht samenstellen met AI';

$labels['compose_label_to'] = 'Naam ontvanger';
$labels['compose_label_from'] = 'Naam afzender';
$labels['compose_label_style'] = 'Stijl';
$labels['compose_label_length'] = 'Lengte';
$labels['compose_label_language'] = 'Taal';
$labels['compose_label_creativity'] = 'Creativiteit';
$labels['compose_label_instructions'] = 'Instructies';
$labels['compose_no_change'] = 'Geen verandering';
$labels['compose_instructions_placeholder'] = 'Voorbeeld: vraag om de afspraak voor volgende week te wijzigen';

$labels['compose_style_assertive'] = 'Assertief';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Enthousiast';
$labels['compose_style_funny'] = 'Grappig';
$labels['compose_style_informational'] = 'Informatief';
$labels['compose_style_professional'] = 'Professioneel';
$labels['compose_style_urgent'] = 'Dringend';
$labels['compose_style_witty'] = 'Geestig';

$labels['compose_length_short'] = 'Korte';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Lang';

$labels['compose_creativity_low'] = 'Laag';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = 'Hoog';

$labels['compose_insert_email'] = 'E-mail invoegen';
$labels['compose_generate_email'] = 'E-mail genereren';
$labels['compose_generate_again'] = 'Opnieuw genereren';
$labels['compose_stop_generating'] = 'Stop met genereren';
$labels['compose_error_help_button'] = 'Help';

$labels['compose_no_instructions'] = 'Typ de instructies.';
$labels['compose_instructions_too_long'] = 'Uw instructies zijn te lang; ze mogen maximaal $n karakters lang zijn.';
$labels['compose_empty_response'] = 'De server heeft een leeg antwoord teruggestuurd.';

$labels['compose_tip_to'] = 'De naam van de ontvanger van de nieuwe e-mail. U kunt meer dan één naam opgeven, of strings gebruiken zoals "iedereen", "team", enz.';
$labels['compose_tip_from'] = 'De nieuwe e-mail wordt ondertekend met deze naam.';
$labels['compose_tip_style'] = 'De gewenste schrijfstijl voor de nieuwe e-mail.';
$labels['compose_tip_length'] = 'De gewenste lengte van de nieuwe e-mail.';
$labels['compose_tip_language'] = 'De taal waarin de nieuwe e-mail moet worden geschreven. Specificeer "Geen verandering" om te schrijven in de taal waarin de instructies worden gegeven.';
$labels['compose_tip_creativity'] = 'Meer creativiteit levert meer onvoorspelbare en diverse e-mails op; minder creativiteit levert e-mails op die meer feitelijk en to the point zijn.';
$labels['compose_tip_instructions'] = 'Instructies over wat er in de nieuwe e-mail moet staan. Klik op de knop Instructievoorbeelden om enkele voorbeelden te zien.';

$labels['compose_help_button_show'] = 'Instructie Voorbeelden';
$labels['compose_help_button_hide'] = 'Terug';
$labels['compose_help_title'] = 'Hulp en voorbeelden';
$labels['compose_help_info'] = 'Vertel de AI over de e-mail die je wilt schrijven. Vermeld specifieke gegevens, zoals namen, data, locaties, redenen, enz. U kunt ook vragen om creatieve ideeën of uitleg. Nadat de e-mail is gegenereerd, kun je hem aanpassen en invoegen op de samenstelpagina. Hier zijn enkele voorbeeldinstructies om u op weg te helpen:';

$labels['compose_help_example_1'] = 'zeg hallo';
$labels['compose_help_example_2'] = 'de vergadering van vandaag is geannuleerd';
$labels['compose_help_example_3'] = 'Ik maak een afspraak met Margy op woensdag 15.00 uur, bereid een presentatie voor over de voordelen van AI in onze marketingcampagnes om haar te overtuigen...';
$labels['compose_help_example_4'] = 'we hebben besloten om ondersteuning voor AI-gegenereerde inhoud toe te voegen, leg kort uit wat het is';
$labels['compose_help_example_5'] = 'follow-up van de werkopdracht van vorige week, ik heb het morgen nodig';
$labels['compose_help_example_6'] = 'vraag om opslag, ik heb er een jaar lang geen gekregen.';
$labels['compose_help_example_7'] = 'herinneren om het rapport morgen in te dienen of het team komt in de problemen met Margy die gestrest is.';
$labels['compose_help_example_8'] = 'schrijf de klanten over de 20% korting op alle producten tot dinsdag, we verkopen kleding';
$labels['compose_help_example_9'] = 'Laura te schrijven en haar te vragen een bijeenkomst te organiseren voor Kevins verjaardag dit weekend.';
$labels['compose_help_example_10'] = 'vraag naar de voortgang met de nieuwe website, 5 ideeën voor marketing slogans voor de voorpagina, we doen autoreparaties';
$labels['compose_help_example_11'] = 'nieuwsbrief over de nieuwe vpn-functies die we hebben toegevoegd: wireguard protocol, port forwarding, kill switch, dedicated IP; leg uit wat ze zijn';
$labels['compose_help_example_12'] = 'nieuwsbrief over een nieuw loyaliteitsprogramma, initiële kosten zijn $10 voor de kaart en daarna tot 20% korting op de meeste producten gedurende een jaar.';
$labels['compose_help_example_13'] = 'netwerk e-mail naar iemand die ik bewonder en waarmee ik professioneel in contact wil komen';
$labels['compose_help_example_14'] = 'een professionele dank voor de hulp bij het project gisteren';
$labels['compose_help_example_15'] = 'met 10 ideeën voor strategieën om een effectieve e-mail pitch naar de investeerders te maken, daar werkt ze morgen aan.';
$labels['compose_help_example_16'] = 'een professionele ontslag e-mail, met 1 maand opzegtermijn volgens contract, het bedrijf heet ACME';
$labels['compose_help_example_17'] = 'dwingende e-mail om een promotie aan te vragen, hintend dat ik misschien ontslag neem als ik het niet krijg omdat er andere mogelijkheden zijn...';
$labels['compose_help_example_18'] = 'het was goed je te ontmoeten op de conferentie gisteren, ik heb genoten van ons gesprek, laten we contact houden...';

$labels['compose_help_use_example'] = '(Gebruik dit)';

// summaries
$labels['creating_summary'] = 'Samenvatting maken...';
$labels['show_summary'] = 'Samenvatting tonen';
$labels['hide_summary'] = 'Samenvatting verbergen';
$labels['recreate_summary'] = 'Samenvatting opnieuw maken';
$labels['email_has_no_text'] = 'Deze e-mail heeft geen tekst.';

// settings
$labels['setting_compose_style'] = 'Stijl';
$labels['setting_compose_length'] = 'Lengte';
$labels['setting_compose_language'] = 'Taal';
$labels['setting_compose_creativity'] = 'Creativiteit';
$labels['setting_language'] = 'Taal';

$labels['summarizing_messages'] = 'Berichten samenvatten';
$labels['setting_show_summary_on_mail_view'] = 'AI-samenvattingen tonen bij het bekijken van e-mails';
$labels['setting_show_summary_on_list_hover'] = 'AI-samenvattingen tonen wanneer u met de muis over de e-maillijst beweegt';
$labels['setting_list_summary_placement'] = 'Plaatsing pop-up overzichtslijst';

$labels['above'] = 'Boven';
$labels['below'] = 'Onder';
$labels['left'] = 'Links';
$labels['right'] = 'Rechts';

$labels['ai_disclaimer'] = 'AI-uitvoer kan soms onvolledig of onjuist zijn. Controleer essentiële informatie altijd dubbel.';

// errors
$labels['api_error'] = 'Er is een fout opgetreden tijdens het verbinden met de AI-server. Neem contact op met de beheerder.';
