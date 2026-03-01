<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Βοηθός AI';
$labels['ai_assistant'] = 'Βοηθός AI';
$labels['ai_assistant_settings'] = 'Ρυθμίσεις AI Assistant';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Γράψτε μήνυμα με AI';

$labels['compose_label_to'] = 'Όνομα παραλήπτη';
$labels['compose_label_from'] = 'Όνομα αποστολέα';
$labels['compose_label_style'] = 'Στυλ';
$labels['compose_label_length'] = 'Μήκος';
$labels['compose_label_language'] = 'Γλώσσα';
$labels['compose_label_creativity'] = 'Δημιουργικότητα';
$labels['compose_label_instructions'] = 'Οδηγίες';
$labels['compose_no_change'] = 'Καμία αλλαγή';
$labels['compose_instructions_placeholder'] = 'Παράδειγμα: ζητήστε να αλλάξετε το ραντεβού για την επόμενη εβδομάδα';

$labels['compose_style_assertive'] = 'Επιβλητικό';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Ενθουσιώδης';
$labels['compose_style_funny'] = 'Αστείο';
$labels['compose_style_informational'] = 'Ενημερωτικό';
$labels['compose_style_professional'] = 'Επαγγελματικό';
$labels['compose_style_urgent'] = 'Επείγον';
$labels['compose_style_witty'] = 'Πνευματώδης';

$labels['compose_length_short'] = 'Σύντομο';
$labels['compose_length_medium'] = 'Μεσαίο';
$labels['compose_length_long'] = 'Μακρύ';

$labels['compose_creativity_low'] = 'Χαμηλή';
$labels['compose_creativity_medium'] = 'Μεσαίο';
$labels['compose_creativity_high'] = 'Υψηλή';

$labels['compose_insert_email'] = 'Εισαγωγή Email';
$labels['compose_generate_email'] = 'Δημιουργία email';
$labels['compose_generate_again'] = 'Δημιουργήστε ξανά';
$labels['compose_stop_generating'] = 'Σταματήστε την παραγωγή';
$labels['compose_error_help_button'] = 'Βοήθεια';

$labels['compose_no_instructions'] = 'Πληκτρολογήστε τις οδηγίες.';
$labels['compose_instructions_too_long'] = 'Οι οδηγίες σας είναι πολύ μεγάλες- μπορούν να είναι το πολύ $n χαρακτήρες.';
$labels['compose_empty_response'] = 'Ο διακομιστής επέστρεψε μια κενή απάντηση.';

$labels['compose_tip_to'] = 'Το όνομα του παραλήπτη του νέου email. Μπορείτε να καθορίσετε περισσότερα από ένα ονόματα ή να χρησιμοποιήσετε συμβολοσειρές όπως "όλοι", "ομάδα" κ.λπ.';
$labels['compose_tip_from'] = 'Το νέο email θα υπογράφεται με αυτό το όνομα.';
$labels['compose_tip_style'] = 'Το προτιμώμενο στυλ γραφής για το νέο email.';
$labels['compose_tip_length'] = 'Το προτιμώμενο μήκος του νέου email.';
$labels['compose_tip_language'] = 'Η γλώσσα στην οποία θα πρέπει να γραφτεί το νέο email. Καθορίστε "Καμία αλλαγή" για να γράψετε στη γλώσσα στην οποία δίνονται οι οδηγίες.';
$labels['compose_tip_creativity'] = 'Η υψηλότερη δημιουργικότητα θα δημιουργήσει πιο απρόβλεπτα και ποικίλα μηνύματα ηλεκτρονικού ταχυδρομείου- η χαμηλότερη δημιουργικότητα θα δημιουργήσει μηνύματα ηλεκτρονικού ταχυδρομείου που θα είναι πιο αντικειμενικά και επί της ουσίας.';
$labels['compose_tip_instructions'] = 'Οδηγίες σχετικά με το τι πρέπει να συμπεριλάβετε στο νέο μήνυμα ηλεκτρονικού ταχυδρομείου. Κάντε κλικ στο κουμπί Παραδείγματα οδηγιών για να δείτε μερικά παραδείγματα.';

$labels['compose_help_button_show'] = 'Παραδείγματα εντολών';
$labels['compose_help_button_hide'] = 'Πίσω';
$labels['compose_help_title'] = 'Βοήθεια & Παραδείγματα';
$labels['compose_help_info'] = 'Πείτε στην AI για το email που θα θέλατε να γράψετε. Συμπεριλάβετε λεπτομέρειες, όπως ονόματα, ημερομηνίες, τοποθεσίες, λόγους κ.λπ. Μπορείτε επίσης να ζητήσετε δημιουργικές ιδέες ή εξηγήσεις. Αφού δημιουργηθεί το μήνυμα ηλεκτρονικού ταχυδρομείου, θα μπορείτε να το τροποποιήσετε και να το εισάγετε στη σελίδα σύνταξης. Ακολουθούν μερικά δείγματα οδηγιών για να ξεκινήσετε:';

$labels['compose_help_example_1'] = 'πείτε ένα γεια';
$labels['compose_help_example_2'] = 'η σημερινή συνάντηση ακυρώθηκε';
$labels['compose_help_example_3'] = 'Κανόνισα μια συνάντηση με τη Margy στις 3 μ.μ. της Τετάρτης, ετοιμάζω μια παρουσίαση σχετικά με τα πλεονεκτήματα της χρήσης της ΤΝ στις εκστρατείες μάρκετινγκ μας για να την πείσω.';
$labels['compose_help_example_4'] = 'αποφασίσαμε να προσθέσουμε υποστήριξη για περιεχόμενο που παράγεται από τεχνητή νοημοσύνη, εξηγήστε εν συντομία τι είναι αυτό';
$labels['compose_help_example_5'] = 'παρακολούθηση της ανάθεσης εργασίας της προηγούμενης εβδομάδας, τη χρειάζομαι αύριο';
$labels['compose_help_example_6'] = 'να ζητήσω αύξηση, δεν πήρα για πάνω από ένα χρόνο.';
$labels['compose_help_example_7'] = 'να υπενθυμίσω να υποβάλουν την έκθεση αύριο, αλλιώς η ομάδα θα έχει πρόβλημα με τη Μάργκι που είναι αγχωμένη';
$labels['compose_help_example_8'] = 'γράψτε στους πελάτες για την έκπτωση 20% σε όλα τα προϊόντα μέχρι την Τρίτη, πουλάμε ρούχα';
$labels['compose_help_example_9'] = 'να ζητήσει να γράψει στη Λάουρα και να της πει να οργανώσει μια συγκέντρωση για τα γενέθλια του Κέβιν αυτό το Σαββατοκύριακο';
$labels['compose_help_example_10'] = 'ρωτήστε για την πρόοδο της νέας ιστοσελίδας, συμπεριλάβετε 5 ιδέες για σλόγκαν μάρκετινγκ για την πρώτη σελίδα, κάνουμε επισκευές αυτοκινήτων';
$labels['compose_help_example_11'] = 'ενημερωτικό δελτίο για τα νέα χαρακτηριστικά vpn που προσθέσαμε: πρωτόκολλο wireguard, προώθηση θύρας, kill switch, αποκλειστική IP; εξηγήστε τι είναι αυτά.';
$labels['compose_help_example_12'] = 'ενημερωτικό δελτίο για ένα νέο πρόγραμμα πιστότητας, το αρχικό κόστος είναι 10 δολάρια για την κάρτα και στη συνέχεια έως και 20% εκπτώσεις στα περισσότερα προϊόντα για ένα χρόνο';
$labels['compose_help_example_13'] = 'e-mail δικτύωσης σε κάποιον που θαυμάζω και θέλω να συνδεθώ επαγγελματικά';
$labels['compose_help_example_14'] = 'ένα επαγγελματικό ευχαριστώ για τη βοήθεια με το έργο χθες';
$labels['compose_help_example_15'] = 'κατέληξε σε 10 ιδέες στρατηγικών για τη σύνταξη ενός αποτελεσματικού ηλεκτρονικού μηνύματος προς τους επενδυτές, θα το δουλέψει αύριο';
$labels['compose_help_example_16'] = 'ένα επαγγελματικό email παραίτησης, με προειδοποίηση 1 μήνα σύμφωνα με τη σύμβαση, η εταιρεία ονομάζεται ACME';
$labels['compose_help_example_17'] = 'επιτακτικό ηλεκτρονικό μήνυμα για να ζητήσω προαγωγή, υπονοώντας ότι μπορεί να παραιτηθώ αν δεν την πάρω, επειδή υπάρχουν άλλες ευκαιρίες';
$labels['compose_help_example_18'] = 'Χάρηκα που σας γνώρισα στο συνέδριο χθες, απόλαυσα την κουβέντα μας, ας μείνουμε σε επαφή';

$labels['compose_help_use_example'] = '(Χρησιμοποιήστε αυτό)';

// summaries
$labels['creating_summary'] = 'Δημιουργία περίληψης...';
$labels['show_summary'] = 'Εμφάνιση περίληψης';
$labels['hide_summary'] = 'Απόκρυψη περίληψης';
$labels['recreate_summary'] = 'Επαναδημιουργία περίληψης';
$labels['email_has_no_text'] = 'Αυτό το email δεν έχει κείμενο.';

// settings
$labels['setting_compose_style'] = 'Στυλ';
$labels['setting_compose_length'] = 'Μήκος';
$labels['setting_compose_language'] = 'Γλώσσα';
$labels['setting_compose_creativity'] = 'Δημιουργικότητα';
$labels['setting_language'] = 'Γλώσσα';

$labels['summarizing_messages'] = 'Σύνοψη μηνυμάτων';
$labels['setting_show_summary_on_mail_view'] = 'Εμφάνιση περιλήψεων AI κατά την προβολή μηνυμάτων ηλεκτρονικού ταχυδρομείου';
$labels['setting_show_summary_on_list_hover'] = 'Εμφάνιση περιλήψεων AI όταν περνάτε με το ποντίκι πάνω από τη λίστα email';
$labels['setting_list_summary_placement'] = 'Τοποθέτηση αναδυόμενου παραθύρου σύνοψης λίστας';

$labels['above'] = 'Πάνω από το';
$labels['below'] = 'Κάτω από';
$labels['left'] = 'Αριστερά';
$labels['right'] = 'Δεξιά';

$labels['ai_disclaimer'] = 'Η έξοδος AI μπορεί μερικές φορές να είναι ελλιπής ή εσφαλμένη. Πάντα να ελέγχετε διπλά τις βασικές πληροφορίες.';

// errors
$labels['api_error'] = 'Προέκυψε σφάλμα κατά τη σύνδεση με τον διακομιστή AI. Επικοινωνήστε με τον διαχειριστή.';
