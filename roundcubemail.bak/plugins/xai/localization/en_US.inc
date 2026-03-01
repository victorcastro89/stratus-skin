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
$labels['ai_assistant_settings'] = 'AI Assistant settings';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Write Message with AI';

$labels['compose_label_to'] = 'Recipient\'s name';
$labels['compose_label_from'] = 'Sender\'s name';
$labels['compose_label_style'] = 'Style';
$labels['compose_label_length'] = 'Length';
$labels['compose_label_language'] = 'Language';
$labels['compose_label_creativity'] = 'Creativity';
$labels['compose_label_instructions'] = 'Instructions';
$labels['compose_no_change'] = 'No change';
$labels['compose_instructions_placeholder'] = 'Example: ask to change the appointment for next week';

$labels['compose_style_assertive'] = 'Assertive';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Enthusiastic';
$labels['compose_style_funny'] = 'Funny';
$labels['compose_style_informational'] = 'Informational';
$labels['compose_style_professional'] = 'Professional';
$labels['compose_style_urgent'] = 'Urgent';
$labels['compose_style_witty'] = 'Witty';

$labels['compose_length_short'] = 'Short';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Long';

$labels['compose_creativity_low'] = 'Low';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = 'High';

$labels['compose_insert_email'] = 'Insert Email';
$labels['compose_generate_email'] = 'Generate Email';
$labels['compose_generate_again'] = 'Generate Again';
$labels['compose_stop_generating'] = 'Stop Generating';
$labels['compose_error_help_button'] = 'Help';

$labels['compose_no_instructions'] = 'Type the instructions.';
$labels['compose_instructions_too_long'] = 'Your instructions are too long; they can be at the most $n characters.';
$labels['compose_empty_response'] = 'The server returned an empty response.';

$labels['compose_tip_to'] = 'The name of the recipient of the new email. You can specify more than one name, or use strings like "everybody," "team," etc.';
$labels['compose_tip_from'] = 'The new email will be signed with this name.';
$labels['compose_tip_style'] = 'The preferred writing style for the new email.';
$labels['compose_tip_length'] = 'The preferred length of the new email.';
$labels['compose_tip_language'] = 'The language in which the new email should be written. Specify "No change" to write in the language in which the instructions are given.';
$labels['compose_tip_creativity'] = 'Higher creativity will generate more unpredictable and diverse emails; lower creativity will generate emails that are more factual and to the point.';
$labels['compose_tip_instructions'] = 'Instructions on what to include in the new email. Click the Instruction Examples button to see some examples.';

$labels['compose_help_button_show'] = 'Instruction Examples';
$labels['compose_help_button_hide'] = 'Back';
$labels['compose_help_title'] = 'Help & Examples';
$labels['compose_help_info'] = 'Tell the AI about the email you\'d like to write. Include specifics, like names, dates, locations, reasons, etc. You can also ask for creative ideas or explanations. After the email is generated, you will be able to tweak it and insert it into the compose page. Here are a few sample instructions to get you started:';

$labels['compose_help_example_1'] = 'say hello';
$labels['compose_help_example_2'] = 'today\' meeting has been canceled';
$labels['compose_help_example_3'] = 'I set up a meeting with Margy on Wed 3 P.M., prepare a presentation on advantages of using AI in our marketing campaigns to convince her';
$labels['compose_help_example_4'] = 'we decided to add support for AI generated content, explain shortly what it is';
$labels['compose_help_example_5'] = 'follow-up on the work assignment from last week, I need it tomorrow';
$labels['compose_help_example_6'] = 'ask for a raise, I didn\'t get one for over a year';
$labels['compose_help_example_7'] = 'remind to submit the report tomorrow or the team will get in trouble with Margy who is stressed';
$labels['compose_help_example_8'] = 'write the customers about the 20% discount on all products till Tuesday, we sell clothes';
$labels['compose_help_example_9'] = 'ask to write Laura and tell her to organize a get-together for Kevin\'s birthday this weekend';
$labels['compose_help_example_10'] = 'ask about the progress with the new website, include 5 ideas for marketing slogans for the front page, we do car repairs';
$labels['compose_help_example_11'] = 'newsletter about the new vpn features we added: wireguard protocol, port forwarding, kill switch, dedicated IP; explain what they are';
$labels['compose_help_example_12'] = 'newsletter about a new loyalty program, initial cost is $10 for the card and then up to 20% discounts on most products for a year';
$labels['compose_help_example_13'] = 'networking email to someone I admire and want to connect with professionally';
$labels['compose_help_example_14'] = 'a professional thank you for the help with the project yesterday';
$labels['compose_help_example_15'] = 'come up with 10 ideas of strategies for crafting an effective email pitch to the investors, she\'ll be working on it tomorrow';
$labels['compose_help_example_16'] = 'a professional resignation email, giving 1 month notice as per contract, the company is called ACME';
$labels['compose_help_example_17'] = 'compelling email to request a promotion, hinting that I might resign if I don\'t get it because there are other opportunities';
$labels['compose_help_example_18'] = 'it was good to meet you on the conference yesterday, I enjoyed our chat, let\'s stay in touch';

$labels['compose_help_use_example'] = '(Use this)';

// summaries
$labels['creating_summary'] = 'Creating summary...';
$labels['show_summary'] = 'Show summary';
$labels['hide_summary'] = 'Hide summary';
$labels['recreate_summary'] = 'Re-create summary';
$labels['email_has_no_text'] = 'This email has no text.';

// settings
$labels['setting_compose_style'] = 'Style';
$labels['setting_compose_length'] = 'Length';
$labels['setting_compose_language'] = 'Language';
$labels['setting_compose_creativity'] = 'Creativity';
$labels['setting_language'] = 'Language';

$labels['summarizing_messages'] = 'Summarizing Messages';
$labels['setting_show_summary_on_mail_view'] = 'Show AI summaries when viewing emails';
$labels['setting_show_summary_on_list_hover'] = 'Show AI summaries when hovering over the email list';
$labels['setting_list_summary_placement'] = 'List summary popup placement';

$labels['above'] = 'Above';
$labels['below'] = 'Below';
$labels['left'] = 'Left';
$labels['right'] = 'Right';

$labels['ai_disclaimer'] = 'AI output may sometimes be incomplete or incorrect. Always double-check essential information.';

// errors
$labels['api_error'] = 'An error occurred while connecting to the AI server. Please contact the administrator.';