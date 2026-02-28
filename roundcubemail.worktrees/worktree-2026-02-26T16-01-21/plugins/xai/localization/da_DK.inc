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
$labels['ai_assistant_settings'] = 'Indstillinger for AI-assistent';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Skriv en besked med AI';

$labels['compose_label_to'] = 'Modtagerens navn';
$labels['compose_label_from'] = 'Afsenderens navn';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Længde';
$labels['compose_label_language'] = 'Sprog';
$labels['compose_label_creativity'] = 'Kreativitet';
$labels['compose_label_instructions'] = 'Instruktioner';
$labels['compose_no_change'] = 'Ingen ændring';
$labels['compose_instructions_placeholder'] = 'Eksempel: Bed om at få ændret aftalen til næste uge';

$labels['compose_style_assertive'] = 'Assertiv';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entusiastisk';
$labels['compose_style_funny'] = 'Sjovt';
$labels['compose_style_informational'] = 'Informationsmateriale';
$labels['compose_style_professional'] = 'Professionel';
$labels['compose_style_urgent'] = 'Haster';
$labels['compose_style_witty'] = 'Morsomt';

$labels['compose_length_short'] = 'Kort';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Lang';

$labels['compose_creativity_low'] = 'Lav';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = 'Høj';

$labels['compose_insert_email'] = 'Indsæt e-mail';
$labels['compose_generate_email'] = 'Generer e-mail';
$labels['compose_generate_again'] = 'Generer igen';
$labels['compose_stop_generating'] = 'Stop med at generere';
$labels['compose_error_help_button'] = 'Hjælp';

$labels['compose_no_instructions'] = 'Skriv instruktionerne.';
$labels['compose_instructions_too_long'] = 'Dine instruktioner er for lange; de kan højst være på $n tegn.';
$labels['compose_empty_response'] = 'Serveren returnerede et tomt svar.';

$labels['compose_tip_to'] = 'Navnet på modtageren af den nye e-mail. Du kan angive mere end ét navn eller bruge strenge som "alle", "team" osv.';
$labels['compose_tip_from'] = 'Den nye e-mail vil blive signeret med dette navn.';
$labels['compose_tip_style'] = 'Den foretrukne skrivestil for den nye e-mail.';
$labels['compose_tip_length'] = 'Den foretrukne længde af den nye e-mail.';
$labels['compose_tip_language'] = 'Det sprog, som den nye e-mail skal skrives på. Angiv "Ingen ændring" for at skrive på det sprog, som vejledningen er givet på.';
$labels['compose_tip_creativity'] = 'Højere kreativitet vil generere mere uforudsigelige og forskelligartede e-mails; lavere kreativitet vil generere e-mails, der er mere faktuelle og præcise.';
$labels['compose_tip_instructions'] = 'Instruktioner om, hvad der skal medtages i den nye e-mail. Klik på knappen Eksempler på instruktioner for at se nogle eksempler.';

$labels['compose_help_button_show'] = 'Eksempler på instruktioner';
$labels['compose_help_button_hide'] = 'Tilbage';
$labels['compose_help_title'] = 'Hjælp og eksempler';
$labels['compose_help_info'] = 'Fortæl AI om den e-mail, du gerne vil skrive. Medtag specifikke oplysninger, f.eks. navne, datoer, steder, årsager osv. Du kan også bede om kreative idéer eller forklaringer. Når e-mailen er genereret, kan du justere den og indsætte den på komponeringssiden. Her er et par eksempler på instruktioner, så du kan komme i gang:';

$labels['compose_help_example_1'] = 'sige goddag';
$labels['compose_help_example_2'] = 'dagens møde er aflyst';
$labels['compose_help_example_3'] = 'Jeg aftalte et møde med Margy onsdag kl. 15.00 og forberedte en præsentation om fordelene ved at bruge AI i vores markedsføringskampagner for at overbevise hende.';
$labels['compose_help_example_4'] = 'vi har besluttet at tilføje understøttelse af AI-genereret indhold, forklarer vi kort, hvad det er';
$labels['compose_help_example_5'] = 'opfølgning på arbejdsopgaven fra sidste uge, jeg skal bruge den i morgen';
$labels['compose_help_example_6'] = 'bede om lønforhøjelse, jeg fik ikke lønforhøjelse i over et år';
$labels['compose_help_example_7'] = 'Husk at aflevere rapporten i morgen, ellers får holdet problemer med Margy, der er stresset';
$labels['compose_help_example_8'] = 'skrive til kunderne om 20% rabat på alle produkter indtil tirsdag, vi sælger tøj';
$labels['compose_help_example_9'] = 'bede om at skrive til Laura og bede hende om at arrangere en sammenkomst i forbindelse med Kevins fødselsdag i weekenden';
$labels['compose_help_example_10'] = 'spørg om fremskridtene med det nye websted, medtag 5 idéer til markedsføringsslogans til forsiden, vi laver bilreparationer';
$labels['compose_help_example_11'] = 'nyhedsbrev om de nye vpn-funktioner, vi har tilføjet: wireguard-protokol, port forwarding, kill switch, dedikeret IP; forklar, hvad de er';
$labels['compose_help_example_12'] = 'nyhedsbrev om et nyt loyalitetsprogram, hvor prisen i starten er 10 $ for kortet og derefter op til 20 % rabat på de fleste produkter i et år';
$labels['compose_help_example_13'] = 'netværksmail til en person, som jeg beundrer og ønsker at komme i kontakt med professionelt';
$labels['compose_help_example_14'] = 'en professionel tak for hjælpen med projektet i går';
$labels['compose_help_example_15'] = 'komme med 10 ideer til strategier til udformning af en effektiv e-mail pitch til investorerne, hun vil arbejde på det i morgen';
$labels['compose_help_example_16'] = 'en professionel opsigelsesmail med 1 måneds opsigelse i henhold til kontrakten, virksomheden hedder ACME';
$labels['compose_help_example_17'] = 'en tvingende e-mail for at anmode om en forfremmelse med en antydning af, at jeg måske siger op, hvis jeg ikke får den, fordi der er andre muligheder';
$labels['compose_help_example_18'] = 'det var godt at møde dig på konferencen i går, jeg nød vores snak, lad os holde kontakten.';

$labels['compose_help_use_example'] = '(Brug dette)';

// summaries
$labels['creating_summary'] = 'Oprettelse af resumé...';
$labels['show_summary'] = 'Vis oversigt';
$labels['hide_summary'] = 'Skjul resumé';
$labels['recreate_summary'] = 'Genskab resuméet';
$labels['email_has_no_text'] = 'Denne e-mail har ingen tekst.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Længde';
$labels['setting_compose_language'] = 'Sprog';
$labels['setting_compose_creativity'] = 'Kreativitet';
$labels['setting_language'] = 'Sprog';

$labels['summarizing_messages'] = 'Opsummering af beskeder';
$labels['setting_show_summary_on_mail_view'] = 'Vis AI-resuméer, når du ser e-mails';
$labels['setting_show_summary_on_list_hover'] = 'Vis AI-resuméer, når du holder musen over e-mail-listen';
$labels['setting_list_summary_placement'] = 'Popup-placering af listeoversigt';

$labels['above'] = 'Ovenfor';
$labels['below'] = 'Nedenfor';
$labels['left'] = 'Til venstre';
$labels['right'] = 'Til højre';

$labels['ai_disclaimer'] = 'AI-output kan nogle gange være ufuldstændigt eller forkert. Dobbelttjek altid vigtige oplysninger.';

// errors
$labels['api_error'] = 'Der opstod en fejl under forbindelsen til AI-serveren. Kontakt venligst administratoren.';
