<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Asistent AI';
$labels['ai_assistant'] = 'Asistent AI';
$labels['ai_assistant_settings'] = 'Setări asistent AI';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Compuneți un mesaj cu AI';

$labels['compose_label_to'] = 'Numele destinatarului';
$labels['compose_label_from'] = 'Numele expeditorului';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Lungime';
$labels['compose_label_language'] = 'Limba';
$labels['compose_label_creativity'] = 'Creativitate';
$labels['compose_label_instructions'] = 'Instrucțiuni';
$labels['compose_no_change'] = 'Nici o schimbare';
$labels['compose_instructions_placeholder'] = 'Exemplu: cereți să schimbați întâlnirea pentru săptămâna viitoare';

$labels['compose_style_assertive'] = 'Asertiv';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entuziast';
$labels['compose_style_funny'] = 'Amuzant';
$labels['compose_style_informational'] = 'Informativ';
$labels['compose_style_professional'] = 'Profesional';
$labels['compose_style_urgent'] = 'Urgent';
$labels['compose_style_witty'] = 'Witty';

$labels['compose_length_short'] = 'Scurt';
$labels['compose_length_medium'] = 'Mediu';
$labels['compose_length_long'] = 'Lung';

$labels['compose_creativity_low'] = 'Scăzut';
$labels['compose_creativity_medium'] = 'Mediu';
$labels['compose_creativity_high'] = 'Mare';

$labels['compose_insert_email'] = 'Introduceți e-mailul';
$labels['compose_generate_email'] = 'Generare e-mail';
$labels['compose_generate_again'] = 'Generați din nou';
$labels['compose_stop_generating'] = 'Opriți generarea';
$labels['compose_error_help_button'] = 'Ajutor';

$labels['compose_no_instructions'] = 'Introduceți instrucțiunile.';
$labels['compose_instructions_too_long'] = 'Instrucțiunile dvs. sunt prea lungi; acestea pot avea cel mult $n caractere.';
$labels['compose_empty_response'] = 'Serverul a returnat un răspuns gol.';

$labels['compose_tip_to'] = 'Numele destinatarului noului e-mail. Puteți specifica mai multe nume sau puteți utiliza șiruri de caractere precum "toată lumea", "echipă" etc.';
$labels['compose_tip_from'] = 'Noul e-mail va fi semnat cu acest nume.';
$labels['compose_tip_style'] = 'Stilul de scriere preferat pentru noul e-mail.';
$labels['compose_tip_length'] = 'Lungimea preferată a noului e-mail.';
$labels['compose_tip_language'] = 'Limba în care trebuie redactat noul e-mail. Specificați "Nici o schimbare" pentru a scrie în limba în care sunt date instrucțiunile.';
$labels['compose_tip_creativity'] = 'O creativitate mai mare va genera e-mailuri mai imprevizibile și mai diverse; o creativitate mai redusă va genera e-mailuri mai concrete și mai la obiect.';
$labels['compose_tip_instructions'] = 'Instrucțiuni cu privire la ce trebuie să includeți în noul e-mail. Faceți clic pe butonul "Instruction Examples" (Exemple de instrucțiuni) pentru a vedea câteva exemple.';

$labels['compose_help_button_show'] = 'Exemple de instrucțiuni';
$labels['compose_help_button_hide'] = 'Înapoi';
$labels['compose_help_title'] = 'Ajutor și exemple';
$labels['compose_help_info'] = 'Spuneți-i AI despre e-mailul pe care doriți să îl scrieți. Includeți detalii, cum ar fi nume, date, locații, motive etc. De asemenea, puteți cere idei creative sau explicații. După ce e-mailul este generat, veți putea să-l modificați și să-l inserați în pagina de redactare. Iată câteva exemple de instrucțiuni pentru a vă ajuta să începeți:';

$labels['compose_help_example_1'] = 'spuneți bună ziua';
$labels['compose_help_example_2'] = 'ședința de "astăzi" a fost anulată';
$labels['compose_help_example_3'] = 'Am stabilit o întâlnire cu Margy pentru miercuri la ora 15:00, pregătesc o prezentare despre avantajele folosirii inteligenței artificiale în campaniile noastre de marketing pentru a o convinge.';
$labels['compose_help_example_4'] = 'am decis să adăugăm suport pentru conținutul generat de AI, să explicăm pe scurt ce este acesta';
$labels['compose_help_example_5'] = 'urmărirea temei de lucru de săptămâna trecută, am nevoie de ea mâine';
$labels['compose_help_example_6'] = 'să cer o mărire de salariu, nu am primit una de peste un an.';
$labels['compose_help_example_7'] = 'amintește-le să prezinte raportul mâine, altfel echipa va avea probleme cu Margy, care este stresată.';
$labels['compose_help_example_8'] = 'scrieți clienților despre reducerea de 20% la toate produsele până marți, noi vindem haine';
$labels['compose_help_example_9'] = 'să îi scrie Laurei și să îi spună să organizeze o reuniune pentru ziua de naștere a lui Kevin în acest weekend.';
$labels['compose_help_example_10'] = 'întrebați despre progresul noului site, includeți 5 idei de sloganuri de marketing pentru prima pagină, facem reparații auto';
$labels['compose_help_example_11'] = 'buletin informativ despre noile caracteristici vpn pe care le-am adăugat: protocolul wireguard, redirecționarea porturilor, kill switch, IP dedicat; explicați ce sunt acestea.';
$labels['compose_help_example_12'] = 'newsletter despre un nou program de loialitate, costul inițial este de 10 dolari pentru card și apoi reduceri de până la 20% la majoritatea produselor timp de un an.';
$labels['compose_help_example_13'] = 'e-mail de networking către o persoană pe care o admir și cu care vreau să intru în contact din punct de vedere profesional';
$labels['compose_help_example_14'] = 'o mulțumire profesionistă pentru ajutorul acordat ieri în cadrul proiectului';
$labels['compose_help_example_15'] = 'a venit cu 10 idei de strategii pentru a elabora un e-mail eficient pentru a prezenta investitorilor, la care va lucra mâine.';
$labels['compose_help_example_16'] = 'un e-mail de demisie profesională, cu preaviz de 1 lună conform contractului, compania se numește ACME';
$labels['compose_help_example_17'] = 'e-mail convingător pentru a solicita o promovare, lăsând să se înțeleagă că aș putea demisiona dacă nu o primesc, deoarece există alte oportunități';
$labels['compose_help_example_18'] = 'mi-a făcut plăcere să vă întâlnesc la conferința de ieri, m-am bucurat de discuția noastră, să păstrăm legătura.';

$labels['compose_help_use_example'] = '(Folosiți acest lucru)';

// summaries
$labels['creating_summary'] = 'Crearea unui rezumat...';
$labels['show_summary'] = 'Arată rezumatul';
$labels['hide_summary'] = 'Ascundeți rezumatul';
$labels['recreate_summary'] = 'Re-crearea rezumatului';
$labels['email_has_no_text'] = 'Acest e-mail nu are text.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Lungime';
$labels['setting_compose_language'] = 'Limba';
$labels['setting_compose_creativity'] = 'Creativitate';
$labels['setting_language'] = 'Limba';

$labels['summarizing_messages'] = 'Rezumarea mesajelor';
$labels['setting_show_summary_on_mail_view'] = 'Afișarea rezumatelor AI la vizualizarea e-mailurilor';
$labels['setting_show_summary_on_list_hover'] = 'Afișarea rezumatelor AI atunci când treceți peste lista de e-mail';
$labels['setting_list_summary_placement'] = 'Plasarea pop-up-ului rezumat listă';

$labels['above'] = 'Deasupra';
$labels['below'] = 'Mai jos';
$labels['left'] = 'Stânga';
$labels['right'] = 'Corect';

$labels['ai_disclaimer'] = 'Rezultatele AI pot fi uneori incomplete sau incorecte. Verificați întotdeauna de două ori informațiile esențiale.';

// errors
$labels['api_error'] = 'A apărut o eroare în timpul conectării la serverul AI. Vă rugăm să contactați administratorul.';
