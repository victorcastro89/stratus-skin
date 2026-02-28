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
$labels['ai_assistant_settings'] = 'Настройки на AI Assistant';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Напишете съобщение с AI';

$labels['compose_label_to'] = 'Име на получателя';
$labels['compose_label_from'] = 'Име на подателя';
$labels['compose_label_style'] = 'Стил';
$labels['compose_label_length'] = 'Дължина';
$labels['compose_label_language'] = 'Език';
$labels['compose_label_creativity'] = 'Творчество';
$labels['compose_label_instructions'] = 'Инструкции';
$labels['compose_no_change'] = 'Няма промяна';
$labels['compose_instructions_placeholder'] = 'Пример: поискайте да промените срещата за следващата седмица';

$labels['compose_style_assertive'] = 'Асертивен';
$labels['compose_style_casual'] = 'Случайно';
$labels['compose_style_enthusiastic'] = 'Ентусиазиран';
$labels['compose_style_funny'] = 'Смешен';
$labels['compose_style_informational'] = 'Информационен';
$labels['compose_style_professional'] = 'Професионален';
$labels['compose_style_urgent'] = 'Спешно';
$labels['compose_style_witty'] = 'Остроумен';

$labels['compose_length_short'] = 'Кратък';
$labels['compose_length_medium'] = 'Среден';
$labels['compose_length_long'] = 'Дълъг';

$labels['compose_creativity_low'] = 'Нисък';
$labels['compose_creativity_medium'] = 'Среден';
$labels['compose_creativity_high'] = 'Висока';

$labels['compose_insert_email'] = 'Вмъкнете имейл';
$labels['compose_generate_email'] = 'Генериране на имейл';
$labels['compose_generate_again'] = 'Генерирайте отново';
$labels['compose_stop_generating'] = 'Спиране на генерирането';
$labels['compose_error_help_button'] = 'Помощ';

$labels['compose_no_instructions'] = 'Въведете инструкциите.';
$labels['compose_instructions_too_long'] = 'Инструкциите ви са твърде дълги; те могат да бъдат най-много $n символа.';
$labels['compose_empty_response'] = 'Сървърът върна празен отговор.';

$labels['compose_tip_to'] = 'Името на получателя на новия имейл. Можете да посочите повече от едно име или да използвате низове като "всички", "екип" и т.н.';
$labels['compose_tip_from'] = 'Новият имейл ще бъде подписан с това име.';
$labels['compose_tip_style'] = 'Предпочитаният стил на писане за новия имейл.';
$labels['compose_tip_length'] = 'Предпочитаната дължина на новия имейл.';
$labels['compose_tip_language'] = 'Езикът, на който трябва да бъде написан новият имейл. Посочете "Без промяна", за да пишете на езика, на който са дадени инструкциите.';
$labels['compose_tip_creativity'] = 'По-високата креативност ще доведе до по-непредсказуеми и разнообразни имейли; по-ниската креативност ще доведе до имейли, които са по-фактологични и точни.';
$labels['compose_tip_instructions'] = 'Инструкции за това какво да включите в новия имейл. Щракнете върху бутона Примери за инструкции, за да видите някои примери.';

$labels['compose_help_button_show'] = 'Примери за инструкции';
$labels['compose_help_button_hide'] = 'Обратно';
$labels['compose_help_title'] = 'Помощ и примери';
$labels['compose_help_info'] = 'Разкажете на AI за имейла, който искате да напишете. Включете конкретни данни, като имена, дати, места, причини и т.н. Можете също така да поискате творчески идеи или обяснения. След като имейлът бъде генериран, ще можете да го коригирате и да го вмъкнете в страницата за съставяне. Ето няколко примерни инструкции, които да ви помогнат да започнете:';

$labels['compose_help_example_1'] = 'поздрави';
$labels['compose_help_example_2'] = 'днешната среща е отменена';
$labels['compose_help_example_3'] = 'Уредих среща с Марджи в сряда в 15 ч., подготвих презентация за предимствата на използването на изкуствен интелект в маркетинговите ни кампании, за да я убедя.';
$labels['compose_help_example_4'] = 'решихме да добавим поддръжка за съдържание, генерирано от изкуствен интелект, да обясним накратко какво представлява то';
$labels['compose_help_example_5'] = 'продължаване на работата по задачата от миналата седмица, трябва ми утре';
$labels['compose_help_example_6'] = 'да поискам повишение, не получих такова повече от година';
$labels['compose_help_example_7'] = 'напомня да се представи докладът утре, иначе екипът ще има проблеми с Марджи, която е стресирана.';
$labels['compose_help_example_8'] = 'пишете на клиентите за 20% отстъпка от всички продукти до вторник, продаваме дрехи';
$labels['compose_help_example_9'] = 'помоли да пишеш на Лора и да й кажеш да организираме среща за рождения ден на Кевин този уикенд';
$labels['compose_help_example_10'] = 'попитайте за напредъка на новия уебсайт, включете 5 идеи за маркетингови слогани за заглавната страница, ремонтираме автомобили';
$labels['compose_help_example_11'] = 'информационен бюлетин за новите функции на VPN, които добавихме: протокол wireguard, пренасочване на портове, kill switch, специален IP адрес; обяснете какви са те';
$labels['compose_help_example_12'] = 'бюлетин за нова програма за лоялност, първоначалната цена е 10 долара за картата, а след това до 20% отстъпки за повечето продукти за една година';
$labels['compose_help_example_13'] = 'имейл за създаване на мрежа до човек, на когото се възхищавам и с когото искам да се свържа професионално.';
$labels['compose_help_example_14'] = 'професионална благодарност за помощта за проекта вчера';
$labels['compose_help_example_15'] = 'измисли 10 идеи за стратегии за изготвяне на ефективен имейл до инвеститорите, по които ще работи утре.';
$labels['compose_help_example_16'] = 'професионален имейл за оставка, в който се дава 1-месечно предизвестие съгласно договора, компанията се нарича ACME';
$labels['compose_help_example_17'] = 'убедителен имейл с молба за повишение, в който се намеква, че може да си подам оставката, ако не я получа, защото има и други възможности';
$labels['compose_help_example_18'] = 'Беше ми приятно да се срещна с теб на конференцията вчера, хареса ми разговорът ни, нека останем в контакт';

$labels['compose_help_use_example'] = '(Използвайте това)';

// summaries
$labels['creating_summary'] = 'Създаване на резюме...';
$labels['show_summary'] = 'Покажете резюме';
$labels['hide_summary'] = 'Скриване на резюмето';
$labels['recreate_summary'] = 'Повторно създаване на резюме';
$labels['email_has_no_text'] = 'Този имейл няма текст.';

// settings
$labels['setting_compose_style'] = 'Стил';
$labels['setting_compose_length'] = 'Дължина';
$labels['setting_compose_language'] = 'Език';
$labels['setting_compose_creativity'] = 'Творчество';
$labels['setting_language'] = 'Език';

$labels['summarizing_messages'] = 'Обобщаване на съобщенията';
$labels['setting_show_summary_on_mail_view'] = 'Показване на резюмета на AI при преглед на имейли';
$labels['setting_show_summary_on_list_hover'] = 'Показване на резюмета на изкуствения интелект при преминаване с мишката над списъка с имейли';
$labels['setting_list_summary_placement'] = 'Поставяне на изскачащ прозорец с резюме на списъка';

$labels['above'] = 'Над';
$labels['below'] = 'Под';
$labels['left'] = 'Вляво';
$labels['right'] = 'Вдясно';

$labels['ai_disclaimer'] = 'Изходът на AI може понякога да бъде непълен или неправилен. Винаги проверявайте два пъти основната информация.';

// errors
$labels['api_error'] = 'Възникна грешка при свързването със сървъра на AI. Моля, свържете се с администратора.';
