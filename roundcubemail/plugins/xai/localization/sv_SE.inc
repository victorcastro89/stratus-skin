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
$labels['ai_assistant_settings'] = 'Inställningar för AI-assistent';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Skriv meddelande med AI';

$labels['compose_label_to'] = 'Mottagarens namn';
$labels['compose_label_from'] = 'Avsändarens namn';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Längd';
$labels['compose_label_language'] = 'Språk';
$labels['compose_label_creativity'] = 'Kreativitet';
$labels['compose_label_instructions'] = 'Instruktioner';
$labels['compose_no_change'] = 'Ingen förändring';
$labels['compose_instructions_placeholder'] = 'Exempel: be att få byta tid till nästa vecka';

$labels['compose_style_assertive'] = 'Påstridig';
$labels['compose_style_casual'] = 'Avslappnad';
$labels['compose_style_enthusiastic'] = 'Entusiastisk';
$labels['compose_style_funny'] = 'Roligt';
$labels['compose_style_informational'] = 'Information';
$labels['compose_style_professional'] = 'Professionell';
$labels['compose_style_urgent'] = 'Brådskande';
$labels['compose_style_witty'] = 'Vitsigt';

$labels['compose_length_short'] = 'Kort';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Lång';

$labels['compose_creativity_low'] = 'Låg';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = 'Hög';

$labels['compose_insert_email'] = 'Infoga e-post';
$labels['compose_generate_email'] = 'Generera e-post';
$labels['compose_generate_again'] = 'Generera igen';
$labels['compose_stop_generating'] = 'Sluta generera';
$labels['compose_error_help_button'] = 'Hjälp';

$labels['compose_no_instructions'] = 'Skriv instruktionerna.';
$labels['compose_instructions_too_long'] = 'Dina instruktioner är för långa; de kan vara högst $n tecken.';
$labels['compose_empty_response'] = 'Servern returnerade ett tomt svar.';

$labels['compose_tip_to'] = 'Namnet på mottagaren av det nya e-postmeddelandet. Du kan ange mer än ett namn eller använda strängar som "alla", "team" etc.';
$labels['compose_tip_from'] = 'Det nya e-postmeddelandet kommer att undertecknas med detta namn.';
$labels['compose_tip_style'] = 'Den föredragna skrivstilen för det nya e-postmeddelandet.';
$labels['compose_tip_length'] = 'Den önskade längden på det nya e-postmeddelandet.';
$labels['compose_tip_language'] = 'Det språk som det nya e-postmeddelandet ska skrivas på. Ange "Ingen ändring" för att skriva på det språk som instruktionerna ges på.';
$labels['compose_tip_creativity'] = 'Högre kreativitet genererar mer oförutsägbara och varierande e-postmeddelanden, medan lägre kreativitet genererar e-postmeddelanden som är mer sakliga och rakt på sak.';
$labels['compose_tip_instructions'] = 'Instruktioner om vad som ska ingå i det nya e-postmeddelandet. Klicka på knappen Instruktionsexempel för att se några exempel.';

$labels['compose_help_button_show'] = 'Instruktionsexempel';
$labels['compose_help_button_hide'] = 'Tillbaka';
$labels['compose_help_title'] = 'Hjälp & exempel';
$labels['compose_help_info'] = 'Berätta för AI om det e-postmeddelande du vill skriva. Inkludera detaljer, som namn, datum, platser, anledningar etc. Du kan också be om kreativa idéer eller förklaringar. När e-postmeddelandet har genererats kan du justera det och infoga det på skrivsidan. Här är några exempel på instruktioner som hjälper dig att komma igång:';

$labels['compose_help_example_1'] = 'säga hej';
$labels['compose_help_example_2'] = 'dagens möte har ställts in';
$labels['compose_help_example_3'] = 'Jag bokar in ett möte med Margy på onsdag kl. 15.00, förbereder en presentation om fördelarna med att använda AI i våra marknadsföringskampanjer för att övertyga henne';
$labels['compose_help_example_4'] = 'vi beslutade att lägga till stöd för AI-genererat innehåll, förklara kort vad det är';
$labels['compose_help_example_5'] = 'uppföljning av arbetsuppgiften från förra veckan, jag behöver den i morgon';
$labels['compose_help_example_6'] = 'be om löneförhöjning, jag fick ingen på över ett år';
$labels['compose_help_example_7'] = 'påminna om att lämna in rapporten i morgon annars kommer laget att få problem med Margy som är stressad';
$labels['compose_help_example_8'] = 'skriva till kunderna om 20% rabatt på alla varor till och med tisdag, vi säljer kläder';
$labels['compose_help_example_9'] = 'be att få skriva till Laura och be henne ordna en sammankomst för Kevins födelsedag i helgen';
$labels['compose_help_example_10'] = 'fråga om framstegen med den nya webbplatsen, inkludera 5 idéer för marknadsföringsslogans för framsidan, vi gör bilreparationer';
$labels['compose_help_example_11'] = 'nyhetsbrev om de nya vpn-funktioner vi lagt till: wireguard-protokoll, port forwarding, kill switch, dedikerad IP; förklara vad de är';
$labels['compose_help_example_12'] = 'nyhetsbrev om ett nytt lojalitetsprogram, första kostnaden är $10 för kortet och sedan upp till 20% rabatt på de flesta produkter under ett år';
$labels['compose_help_example_13'] = 'nätverksmejl till någon jag beundrar och vill ha en professionell kontakt med';
$labels['compose_help_example_14'] = 'ett professionellt tack för hjälpen med projektet igår';
$labels['compose_help_example_15'] = 'komma med 10 idéer om strategier för att utforma en effektiv e-postpitch till investerarna, hon kommer att arbeta med det i morgon';
$labels['compose_help_example_16'] = 'ett professionellt uppsägningsmail med 1 månads uppsägningstid enligt avtal, företaget heter ACME';
$labels['compose_help_example_17'] = 'tvingande e-post för att begära en befordran, med antydan om att jag kanske säger upp mig om jag inte får den eftersom det finns andra möjligheter';
$labels['compose_help_example_18'] = 'det var trevligt att träffa dig på konferensen igår, jag tyckte om vår chatt, låt oss hålla kontakten';

$labels['compose_help_use_example'] = '(Använd denna)';

// summaries
$labels['creating_summary'] = 'Skapa en sammanfattning...';
$labels['show_summary'] = 'Visa sammanfattning';
$labels['hide_summary'] = 'Dölj sammanfattning';
$labels['recreate_summary'] = 'Återskapa sammanfattning';
$labels['email_has_no_text'] = 'Detta e-postmeddelande har ingen text.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Längd';
$labels['setting_compose_language'] = 'Språk';
$labels['setting_compose_creativity'] = 'Kreativitet';
$labels['setting_language'] = 'Språk';

$labels['summarizing_messages'] = 'Sammanfatta meddelanden';
$labels['setting_show_summary_on_mail_view'] = 'Visa AI-sammanfattningar när du tittar på e-postmeddelanden';
$labels['setting_show_summary_on_list_hover'] = 'Visa AI-sammanfattningar när du håller muspekaren över e-postlistan';
$labels['setting_list_summary_placement'] = 'Placering av popup-fönster för listöversikt';

$labels['above'] = 'Ovan';
$labels['below'] = 'Nedan';
$labels['left'] = 'Vänster';
$labels['right'] = 'Rätt';

$labels['ai_disclaimer'] = 'AI-utdata kan ibland vara ofullständiga eller felaktiga. Dubbelkolla alltid väsentlig information.';

// errors
$labels['api_error'] = 'Ett fel inträffade vid anslutningen till AI-servern. Vänligen kontakta administratören.';
