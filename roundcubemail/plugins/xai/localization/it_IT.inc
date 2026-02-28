<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Assistente AI';
$labels['ai_assistant'] = 'Assistente AI';
$labels['ai_assistant_settings'] = 'Impostazioni dell\'assistente AI';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Comporre un messaggio con l\'intelligenza artificiale';

$labels['compose_label_to'] = 'Nome del destinatario';
$labels['compose_label_from'] = 'Nome del mittente';
$labels['compose_label_style'] = 'Stile';
$labels['compose_label_length'] = 'Lunghezza';
$labels['compose_label_language'] = 'Lingua';
$labels['compose_label_creativity'] = 'Creatività';
$labels['compose_label_instructions'] = 'Istruzioni';
$labels['compose_no_change'] = 'Nessuna variazione';
$labels['compose_instructions_placeholder'] = 'Esempio: chiedere di cambiare l\'appuntamento per la prossima settimana.';

$labels['compose_style_assertive'] = 'Assertivo';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entusiasta';
$labels['compose_style_funny'] = 'Divertente';
$labels['compose_style_informational'] = 'Informativo';
$labels['compose_style_professional'] = 'Professionale';
$labels['compose_style_urgent'] = 'Urgente';
$labels['compose_style_witty'] = 'Spiritoso';

$labels['compose_length_short'] = 'Breve';
$labels['compose_length_medium'] = 'Medio';
$labels['compose_length_long'] = 'Lungo';

$labels['compose_creativity_low'] = 'Basso';
$labels['compose_creativity_medium'] = 'Medio';
$labels['compose_creativity_high'] = 'Alto';

$labels['compose_insert_email'] = 'Inserire l\'e-mail';
$labels['compose_generate_email'] = 'Generare e-mail';
$labels['compose_generate_again'] = 'Generare di nuovo';
$labels['compose_stop_generating'] = 'Smettere di generare';
$labels['compose_error_help_button'] = 'Aiuto';

$labels['compose_no_instructions'] = 'Digitare le istruzioni.';
$labels['compose_instructions_too_long'] = 'Le istruzioni sono troppo lunghe; possono essere al massimo di $n caratteri.';
$labels['compose_empty_response'] = 'Il server ha restituito una risposta vuota.';

$labels['compose_tip_to'] = 'Il nome del destinatario della nuova e-mail. È possibile specificare più di un nome o utilizzare stringhe come "tutti", "team", ecc.';
$labels['compose_tip_from'] = 'La nuova e-mail sarà firmata con questo nome.';
$labels['compose_tip_style'] = 'Lo stile di scrittura preferito per la nuova e-mail.';
$labels['compose_tip_length'] = 'La lunghezza preferita della nuova e-mail.';
$labels['compose_tip_language'] = 'La lingua in cui deve essere scritta la nuova e-mail. Specificare "Nessuna modifica" per scrivere nella lingua in cui vengono fornite le istruzioni.';
$labels['compose_tip_creativity'] = 'Una creatività più alta genererà email più imprevedibili e diverse; una creatività più bassa genererà email più concrete e mirate.';
$labels['compose_tip_instructions'] = 'Istruzioni su cosa includere nella nuova e-mail. Fate clic sul pulsante Esempi di istruzioni per vedere alcuni esempi.';

$labels['compose_help_button_show'] = 'Esempi di istruzioni';
$labels['compose_help_button_hide'] = 'Indietro';
$labels['compose_help_title'] = 'Guida ed esempi';
$labels['compose_help_info'] = 'Parlare all\'AI dell\'e-mail che si desidera scrivere. Includete informazioni specifiche, come nomi, date, luoghi, motivi, ecc. Si possono anche chiedere idee creative o spiegazioni. Una volta generata l\'e-mail, sarà possibile modificarla e inserirla nella pagina di composizione. Ecco alcuni esempi di istruzioni per iniziare:';

$labels['compose_help_example_1'] = 'saluta';
$labels['compose_help_example_2'] = 'la riunione di oggi è stata annullata';
$labels['compose_help_example_3'] = 'Ho fissato un incontro con Margy il mercoledì alle 15.00, preparando una presentazione sui vantaggi dell\'utilizzo dell\'intelligenza artificiale nelle nostre campagne di marketing per convincerla';
$labels['compose_help_example_4'] = 'abbiamo deciso di aggiungere il supporto per i contenuti generati dall\'intelligenza artificiale, spieghiamo brevemente cos\'è';
$labels['compose_help_example_5'] = 'Seguire il lavoro assegnato la scorsa settimana, mi serve domani.';
$labels['compose_help_example_6'] = 'chiedere un aumento, non l\'ho ottenuto per più di un anno';
$labels['compose_help_example_7'] = 'Ricordate di consegnare il rapporto domani o la squadra si metterà nei guai con Margy, che è stressata.';
$labels['compose_help_example_8'] = 'scrivete ai clienti dello sconto del 20% su tutti i prodotti fino a martedì, vendiamo abbigliamento';
$labels['compose_help_example_9'] = 'chiedere di scrivere a Laura e dirle di organizzare una riunione per il compleanno di Kevin questo fine settimana';
$labels['compose_help_example_10'] = 'chiedere informazioni sullo stato di avanzamento del nuovo sito web, includere 5 idee per slogan di marketing per la prima pagina, facciamo riparazioni auto';
$labels['compose_help_example_11'] = 'newsletter sulle nuove funzionalità vpn che abbiamo aggiunto: protocollo wireguard, port forwarding, kill switch, IP dedicato; spiegare cosa sono';
$labels['compose_help_example_12'] = 'newsletter su un nuovo programma di fidelizzazione, il cui costo iniziale è di 10 dollari per la carta e poi sconti fino al 20% sulla maggior parte dei prodotti per un anno.';
$labels['compose_help_example_13'] = 'e-mail di networking a qualcuno che ammiro e con cui voglio entrare in contatto professionalmente';
$labels['compose_help_example_14'] = 'un ringraziamento professionale per l\'aiuto fornito ieri con il progetto';
$labels['compose_help_example_15'] = 'ha proposto 10 idee di strategie per la creazione di un\'efficace e-mail di presentazione agli investitori, e ci lavorerà domani.';
$labels['compose_help_example_16'] = 'una mail di dimissioni professionale, con un preavviso di 1 mese come da contratto, l\'azienda si chiama ACME';
$labels['compose_help_example_17'] = 'e-mail irresistibili per richiedere una promozione, alludendo al fatto che potrei dimettermi se non la ottengo perché ci sono altre opportunità';
$labels['compose_help_example_18'] = 'è stato un piacere conoscerti alla conferenza di ieri, mi ha fatto piacere chiacchierare, restiamo in contatto';

$labels['compose_help_use_example'] = '(Usa questo)';

// summaries
$labels['creating_summary'] = 'Creare una sintesi...';
$labels['show_summary'] = 'Mostra il sommario';
$labels['hide_summary'] = 'Nascondi riepilogo';
$labels['recreate_summary'] = 'Ricreare il riepilogo';
$labels['email_has_no_text'] = 'Questa e-mail non contiene testo.';

// settings
$labels['setting_compose_style'] = 'Stile';
$labels['setting_compose_length'] = 'Lunghezza';
$labels['setting_compose_language'] = 'Lingua';
$labels['setting_compose_creativity'] = 'Creatività';
$labels['setting_language'] = 'Lingua';

$labels['summarizing_messages'] = 'Riassumere i messaggi';
$labels['setting_show_summary_on_mail_view'] = 'Mostra i riepiloghi dell\'intelligenza artificiale quando si visualizzano le e-mail';
$labels['setting_show_summary_on_list_hover'] = 'Mostra i riepiloghi dell\'intelligenza artificiale quando si passa il mouse sull\'elenco di email';
$labels['setting_list_summary_placement'] = 'Posizionamento del popup di riepilogo dell\'elenco';

$labels['above'] = 'Sopra';
$labels['below'] = 'Sotto';
$labels['left'] = 'A sinistra';
$labels['right'] = 'Diritto';

$labels['ai_disclaimer'] = 'L\'output dell\'AI può talvolta essere incompleto o non corretto. Controllare sempre due volte le informazioni essenziali.';

// errors
$labels['api_error'] = 'Si è verificato un errore durante la connessione al server AI. Contattare l\'amministratore.';
