<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'KI-Assistent';
$labels['ai_assistant'] = 'KI-Assistent';
$labels['ai_assistant_settings'] = 'AI-Assistent-Einstellungen';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Nachricht mit AI verfassen';

$labels['compose_label_to'] = 'Name des Empfängers';
$labels['compose_label_from'] = 'Name des Absenders';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Länge';
$labels['compose_label_language'] = 'Sprache';
$labels['compose_label_creativity'] = 'Kreativität';
$labels['compose_label_instructions'] = 'Anweisungen';
$labels['compose_no_change'] = 'Keine Änderung';
$labels['compose_instructions_placeholder'] = 'Beispiel: Bitten Sie darum, den Termin auf nächste Woche zu verschieben.';

$labels['compose_style_assertive'] = 'Durchsetzungsfähig';
$labels['compose_style_casual'] = 'Lässig';
$labels['compose_style_enthusiastic'] = 'Enthusiastisch';
$labels['compose_style_funny'] = 'Lustig';
$labels['compose_style_informational'] = 'Informativ';
$labels['compose_style_professional'] = 'Professionell';
$labels['compose_style_urgent'] = 'Dringend';
$labels['compose_style_witty'] = 'Witzig';

$labels['compose_length_short'] = 'Kurz';
$labels['compose_length_medium'] = 'Mittel';
$labels['compose_length_long'] = 'Lang';

$labels['compose_creativity_low'] = 'Niedrig';
$labels['compose_creativity_medium'] = 'Mittel';
$labels['compose_creativity_high'] = 'Hoch';

$labels['compose_insert_email'] = 'E-Mail einfügen';
$labels['compose_generate_email'] = 'E-Mail generieren';
$labels['compose_generate_again'] = 'Erneut generieren';
$labels['compose_stop_generating'] = 'Generierung stoppen';
$labels['compose_error_help_button'] = 'Hilfe';

$labels['compose_no_instructions'] = 'Geben Sie die Anweisungen ein.';
$labels['compose_instructions_too_long'] = 'Ihre Anweisungen sind zu lang; sie dürfen höchstens $n Zeichen lang sein.';
$labels['compose_empty_response'] = 'Der Server hat eine leere Antwort zurückgegeben.';

$labels['compose_tip_to'] = 'Der Name des Empfängers der neuen E-Mail. Sie können mehr als einen Namen angeben oder Zeichenfolgen wie "alle", "Team" usw. verwenden.';
$labels['compose_tip_from'] = 'Die neue E-Mail wird mit diesem Namen signiert.';
$labels['compose_tip_style'] = 'Der bevorzugte Schreibstil für die neue E-Mail.';
$labels['compose_tip_length'] = 'Die bevorzugte Länge der neuen E-Mail.';
$labels['compose_tip_language'] = 'Die Sprache, in der die neue E-Mail verfasst werden soll. Geben Sie "Keine Änderung" an, um in der Sprache zu schreiben, in der die Anweisungen gegeben werden.';
$labels['compose_tip_creativity'] = 'Eine höhere Kreativität führt zu unvorhersehbareren und vielfältigeren E-Mails; eine geringere Kreativität führt zu E-Mails, die eher sachlich und auf den Punkt gebracht sind.';
$labels['compose_tip_instructions'] = 'Anweisungen, was die neue E-Mail enthalten soll. Klicken Sie auf die Schaltfläche Anweisungsbeispiele, um einige Beispiele zu sehen.';

$labels['compose_help_button_show'] = 'Beispiele für Anleitungen';
$labels['compose_help_button_hide'] = 'Zurück';
$labels['compose_help_title'] = 'Hilfe und Beispiele';
$labels['compose_help_info'] = 'Beschreiben Sie der KI die E-Mail, die Sie schreiben möchten. Geben Sie Einzelheiten an, z. B. Namen, Daten, Orte, Gründe usw. Sie können auch um kreative Ideen oder Erklärungen bitten. Nachdem die E-Mail erstellt wurde, können Sie sie bearbeiten und in die Verfasserseite einfügen. Hier finden Sie einige Beispielanweisungen, die Ihnen den Einstieg erleichtern:';

$labels['compose_help_example_1'] = 'Guten Tag sagen';
$labels['compose_help_example_2'] = 'Das heutige Treffen wurde abgesagt.';
$labels['compose_help_example_3'] = 'Ich habe für Mittwoch, 15 Uhr, ein Treffen mit Margy vereinbart und eine Präsentation über die Vorteile des Einsatzes von KI in unseren Marketingkampagnen vorbereitet, um sie zu überzeugen.';
$labels['compose_help_example_4'] = 'wir haben beschlossen, Unterstützung für KI-generierte Inhalte hinzuzufügen, und erklären kurz, was das ist';
$labels['compose_help_example_5'] = 'Nachbereitung des Arbeitsauftrags von letzter Woche, ich brauche ihn morgen';
$labels['compose_help_example_6'] = 'um eine Gehaltserhöhung bitten, ich habe über ein Jahr lang keine bekommen';
$labels['compose_help_example_7'] = 'erinnern Sie daran, den Bericht morgen einzureichen, sonst bekommt das Team Ärger mit Margy, die gestresst ist';
$labels['compose_help_example_8'] = 'Schreiben Sie die Kunden über die 20% Rabatt auf alle Produkte bis Dienstag, wir verkaufen Kleidung';
$labels['compose_help_example_9'] = 'Laura zu schreiben und sie zu bitten, ein Treffen für Kevins Geburtstag an diesem Wochenende zu organisieren';
$labels['compose_help_example_10'] = 'Erkundigen Sie sich nach den Fortschritten bei der neuen Website, fügen Sie 5 Ideen für Marketing-Slogans für die Titelseite hinzu, wir machen Autoreparaturen';
$labels['compose_help_example_11'] = 'Newsletter über die neuen vpn-Funktionen, die wir hinzugefügt haben: wireguard-Protokoll, Port-Weiterleitung, Kill-Switch, dedizierte IP; erklären Sie, was sie sind';
$labels['compose_help_example_12'] = 'Newsletter über ein neues Kundenbindungsprogramm, das zunächst 10 $ für die Karte kostet und dann ein Jahr lang bis zu 20 % Rabatt auf die meisten Produkte gewährt';
$labels['compose_help_example_13'] = 'Networking-E-Mail an jemanden, den ich bewundere und mit dem ich beruflich in Kontakt treten möchte';
$labels['compose_help_example_14'] = 'ein professionelles Dankeschön für die Hilfe bei dem gestrigen Projekt';
$labels['compose_help_example_15'] = '10 Ideen für Strategien zur Gestaltung eines effektiven E-Mail-Pitches an die Investoren, sie wird morgen daran arbeiten';
$labels['compose_help_example_16'] = 'eine professionelle Kündigungs-E-Mail mit einer 1-monatigen Kündigungsfrist gemäß Vertrag; das Unternehmen heißt ACME';
$labels['compose_help_example_17'] = 'eine zwingende E-Mail, in der ich um eine Beförderung bitte und andeute, dass ich kündigen könnte, wenn ich sie nicht bekomme, weil es andere Möglichkeiten gibt';
$labels['compose_help_example_18'] = 'es war schön, Sie gestern auf der Konferenz kennenzulernen, ich habe unser Gespräch genossen, lassen Sie uns in Kontakt bleiben';

$labels['compose_help_use_example'] = '(Verwenden Sie dies)';

// summaries
$labels['creating_summary'] = 'Zusammenfassung erstellen...';
$labels['show_summary'] = 'Zusammenfassung anzeigen';
$labels['hide_summary'] = 'Zusammenfassung ausblenden';
$labels['recreate_summary'] = 'Zusammenfassung neu erstellen';
$labels['email_has_no_text'] = 'Diese E-Mail enthält keinen Text.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Länge';
$labels['setting_compose_language'] = 'Sprache';
$labels['setting_compose_creativity'] = 'Kreativität';
$labels['setting_language'] = 'Sprache';

$labels['summarizing_messages'] = 'Nachrichten zusammenfassen';
$labels['setting_show_summary_on_mail_view'] = 'Anzeigen von AI-Zusammenfassungen beim Betrachten von E-Mails';
$labels['setting_show_summary_on_list_hover'] = 'AI-Zusammenfassungen anzeigen, wenn Sie den Mauszeiger über die E-Mail-Liste bewegen';
$labels['setting_list_summary_placement'] = 'Platzierung des Popup-Fensters für die Listenzusammenfassung';

$labels['above'] = 'Über';
$labels['below'] = 'Unter';
$labels['left'] = 'Links';
$labels['right'] = 'Rechts';

$labels['ai_disclaimer'] = 'AI-Ausgaben können manchmal unvollständig oder falsch sein. Überprüfen Sie wichtige Informationen immer doppelt.';

// errors
$labels['api_error'] = 'Bei der Verbindung mit dem AI-Server ist ein Fehler aufgetreten. Bitte wenden Sie sich an den Administrator.';
