<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Assistant IA';
$labels['ai_assistant'] = 'Assistant IA';
$labels['ai_assistant_settings'] = 'Paramètres de l\'assistant AI';

// compose page
$labels['compose_dialog_button_text'] = 'IA';
$labels['compose_dialog_button_title'] = 'Composer un message avec l\'IA';

$labels['compose_label_to'] = 'Nom du bénéficiaire';
$labels['compose_label_from'] = 'Nom de l\'expéditeur';
$labels['compose_label_style'] = 'Style';
$labels['compose_label_length'] = 'Longueur';
$labels['compose_label_language'] = 'Langue';
$labels['compose_label_creativity'] = 'Créativité';
$labels['compose_label_instructions'] = 'Instructions';
$labels['compose_no_change'] = 'Pas de changement';
$labels['compose_instructions_placeholder'] = 'Exemple : demander à changer le rendez-vous pour la semaine prochaine';

$labels['compose_style_assertive'] = 'Assertive';
$labels['compose_style_casual'] = 'Décontracté';
$labels['compose_style_enthusiastic'] = 'Enthousiaste';
$labels['compose_style_funny'] = 'Amusant';
$labels['compose_style_informational'] = 'Information';
$labels['compose_style_professional'] = 'Professionnel';
$labels['compose_style_urgent'] = 'Urgent';
$labels['compose_style_witty'] = 'Plein d\'esprit';

$labels['compose_length_short'] = 'Court';
$labels['compose_length_medium'] = 'Moyen';
$labels['compose_length_long'] = 'Longues';

$labels['compose_creativity_low'] = 'Faible';
$labels['compose_creativity_medium'] = 'Moyen';
$labels['compose_creativity_high'] = 'Haut';

$labels['compose_insert_email'] = 'Insérer l\'e-mail';
$labels['compose_generate_email'] = 'Générer des courriels';
$labels['compose_generate_again'] = 'Générer à nouveau';
$labels['compose_stop_generating'] = 'Arrêter de produire';
$labels['compose_error_help_button'] = 'Aide';

$labels['compose_no_instructions'] = 'Tapez les instructions.';
$labels['compose_instructions_too_long'] = 'Vos instructions sont trop longues ; elles peuvent comporter au maximum $n caractères.';
$labels['compose_empty_response'] = 'Le serveur a renvoyé une réponse vide.';

$labels['compose_tip_to'] = 'Le nom du destinataire du nouvel e-mail. Vous pouvez spécifier plusieurs noms ou utiliser des chaînes de caractères telles que "tout le monde", "équipe", etc.';
$labels['compose_tip_from'] = 'Le nouvel e-mail sera signé avec ce nom.';
$labels['compose_tip_style'] = 'Le style d\'écriture préféré pour le nouveau courriel.';
$labels['compose_tip_length'] = 'Longueur préférée du nouvel e-mail.';
$labels['compose_tip_language'] = 'La langue dans laquelle le nouveau courriel doit être rédigé. Spécifiez "Pas de changement" pour écrire dans la langue dans laquelle les instructions sont données.';
$labels['compose_tip_creativity'] = 'Une plus grande créativité générera des courriels plus imprévisibles et plus diversifiés ; une plus faible créativité générera des courriels plus factuels et plus concrets.';
$labels['compose_tip_instructions'] = 'Instructions sur ce qu\'il faut inclure dans le nouvel e-mail. Cliquez sur le bouton Exemples d\'instructions pour voir quelques exemples.';

$labels['compose_help_button_show'] = 'Exemples d\'instruction';
$labels['compose_help_button_hide'] = 'Retour';
$labels['compose_help_title'] = 'Aide et exemples';
$labels['compose_help_info'] = 'Parlez à l\'IA de l\'e-mail que vous souhaitez écrire. Indiquez des détails précis, comme des noms, des dates, des lieux, des raisons, etc. Vous pouvez également demander des idées créatives ou des explications. Une fois le message généré, vous pourrez le modifier et l\'insérer dans la page de composition. Voici quelques exemples d\'instructions pour vous aider à démarrer :';

$labels['compose_help_example_1'] = 'dire bonjour';
$labels['compose_help_example_2'] = 'La réunion d\'aujourd\'hui a été annulée';
$labels['compose_help_example_3'] = 'J\'organise un rendez-vous avec Margy le mercredi à 15 heures, je prépare une présentation sur les avantages de l\'utilisation de l\'IA dans nos campagnes de marketing pour la convaincre.';
$labels['compose_help_example_4'] = 'nous avons décidé d\'ajouter la prise en charge du contenu généré par l\'IA, expliquons brièvement de quoi il s\'agit';
$labels['compose_help_example_5'] = 'suivi du travail de la semaine dernière, j\'en ai besoin demain';
$labels['compose_help_example_6'] = 'demander une augmentation, je n\'en ai pas eu pendant plus d\'un an';
$labels['compose_help_example_7'] = 'rappeler de remettre le rapport demain ou l\'équipe aura des problèmes avec Margy qui est stressée';
$labels['compose_help_example_8'] = 'informer les clients de la remise de 20% sur tous les produits jusqu\'à mardi, nous vendons des vêtements';
$labels['compose_help_example_9'] = 'demander d\'écrire à Laura pour lui dire d\'organiser une réunion pour l\'anniversaire de Kevin ce week-end';
$labels['compose_help_example_10'] = 's\'informer sur l\'état d\'avancement du nouveau site web, inclure 5 idées de slogans marketing pour la première page, nous faisons des réparations de voitures';
$labels['compose_help_example_11'] = 'newsletter sur les nouvelles fonctionnalités vpn ajoutées : wireguard protocol, port forwarding, kill switch, dedicated IP ; expliquer ce qu\'elles sont';
$labels['compose_help_example_12'] = 'bulletin d\'information sur un nouveau programme de fidélisation, dont le coût initial est de 10 $ pour la carte et qui permet ensuite d\'obtenir jusqu\'à 20 % de réduction sur la plupart des produits pendant un an.';
$labels['compose_help_example_13'] = 'courriel de réseautage à une personne que j\'admire et avec laquelle je souhaite entrer en contact sur le plan professionnel';
$labels['compose_help_example_14'] = 'un remerciement professionnel pour l\'aide apportée au projet hier';
$labels['compose_help_example_15'] = 'proposer 10 idées de stratégies pour rédiger un email de présentation efficace aux investisseurs, elle y travaillera demain';
$labels['compose_help_example_16'] = 'un courriel de démission professionnel, donnant un préavis d\'un mois conformément au contrat, l\'entreprise s\'appelle ACME';
$labels['compose_help_example_17'] = 'un courrier électronique contraignant pour demander une promotion, laissant entendre que je pourrais démissionner si je ne l\'obtiens pas parce qu\'il y a d\'autres opportunités';
$labels['compose_help_example_18'] = 'J\'ai été ravie de vous rencontrer à la conférence d\'hier, j\'ai apprécié notre conversation, restons en contact.';

$labels['compose_help_use_example'] = '(Utiliser ceci)';

// summaries
$labels['creating_summary'] = 'Créer un résumé...';
$labels['show_summary'] = 'Afficher le résumé';
$labels['hide_summary'] = 'Cacher le résumé';
$labels['recreate_summary'] = 'Recréer le résumé';
$labels['email_has_no_text'] = 'Cet e-mail ne contient pas de texte.';

// settings
$labels['setting_compose_style'] = 'Style';
$labels['setting_compose_length'] = 'Longueur';
$labels['setting_compose_language'] = 'Langue';
$labels['setting_compose_creativity'] = 'Créativité';
$labels['setting_language'] = 'Langue';

$labels['summarizing_messages'] = 'Résumé des messages';
$labels['setting_show_summary_on_mail_view'] = 'Afficher les résumés de l\'IA lors de la consultation des courriels';
$labels['setting_show_summary_on_list_hover'] = 'Afficher les résumés de l\'IA lorsque l\'on survole la liste d\'adresses électroniques';
$labels['setting_list_summary_placement'] = 'Placement de la fenêtre contextuelle du résumé de la liste';

$labels['above'] = 'Au-dessus';
$labels['below'] = 'En dessous';
$labels['left'] = 'Gauche';
$labels['right'] = 'Droit';

$labels['ai_disclaimer'] = 'Les données de l\'IA peuvent parfois être incomplètes ou incorrectes. Vérifiez toujours les informations essentielles.';

// errors
$labels['api_error'] = 'Une erreur s\'est produite lors de la connexion au serveur AI. Veuillez contacter l\'administrateur.';
