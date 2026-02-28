<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Асистент штучного інтелекту';
$labels['ai_assistant'] = 'Асистент штучного інтелекту';
$labels['ai_assistant_settings'] = 'Налаштування штучного інтелекту';

// compose page
$labels['compose_dialog_button_text'] = 'ШІ';
$labels['compose_dialog_button_title'] = 'Напишіть повідомлення за допомогою ШІ';

$labels['compose_label_to'] = 'Ім\'я одержувача';
$labels['compose_label_from'] = 'Ім\'я відправника';
$labels['compose_label_style'] = 'Стиль';
$labels['compose_label_length'] = 'Довжина';
$labels['compose_label_language'] = 'Мова';
$labels['compose_label_creativity'] = 'Творчість';
$labels['compose_label_instructions'] = 'Інструкція';
$labels['compose_no_change'] = 'Без змін';
$labels['compose_instructions_placeholder'] = 'Приклад: попросіть перенести зустріч на наступний тиждень';

$labels['compose_style_assertive'] = 'Напористий.';
$labels['compose_style_casual'] = 'Випадковість';
$labels['compose_style_enthusiastic'] = 'З ентузіазмом';
$labels['compose_style_funny'] = 'Смішно.';
$labels['compose_style_informational'] = 'Інформаційний';
$labels['compose_style_professional'] = 'Професіонал';
$labels['compose_style_urgent'] = 'Терміново';
$labels['compose_style_witty'] = 'Дотепно.';

$labels['compose_length_short'] = 'Коротко';
$labels['compose_length_medium'] = 'Середній';
$labels['compose_length_long'] = 'Довгий';

$labels['compose_creativity_low'] = 'Низький';
$labels['compose_creativity_medium'] = 'Середній';
$labels['compose_creativity_high'] = 'Високий';

$labels['compose_insert_email'] = 'Вставити електронну пошту';
$labels['compose_generate_email'] = 'Створити електронну пошту';
$labels['compose_generate_again'] = 'Згенерувати ще раз';
$labels['compose_stop_generating'] = 'Припинити генерування';
$labels['compose_error_help_button'] = 'Допоможіть!';

$labels['compose_no_instructions'] = 'Введіть інструкції.';
$labels['compose_instructions_too_long'] = 'Ваші інструкції занадто довгі; вони можуть містити не більше $n символів.';
$labels['compose_empty_response'] = 'Сервер повернув порожню відповідь.';

$labels['compose_tip_to'] = 'Ім\'я отримувача нового імейлу. Ви можете вказати кілька імен або використовувати рядки типу "всі", "команда" тощо.';
$labels['compose_tip_from'] = 'Новий імейл буде підписаний цим ім\'ям.';
$labels['compose_tip_style'] = 'Стиль, якому надається перевага в новому листі.';
$labels['compose_tip_length'] = 'Бажана довжина нового листа.';
$labels['compose_tip_language'] = 'Мова, якою має бути написаний новий лист. Вкажіть "Без змін", щоб писати мовою, якою надаються інструкції.';
$labels['compose_tip_creativity'] = 'Високий рівень креативності створить більш непередбачувані та різноманітні листи; низький рівень креативності створить листи, які будуть більш фактологічними та по суті.';
$labels['compose_tip_instructions'] = 'Інструкції щодо того, що потрібно включити до нового імейлу. Натисніть кнопку Приклади інструкцій, щоб переглянути приклади.';

$labels['compose_help_button_show'] = 'Приклади інструкцій';
$labels['compose_help_button_hide'] = 'Назад';
$labels['compose_help_title'] = 'Довідка та приклади';
$labels['compose_help_info'] = 'Розкажіть штучному інтелекту про лист, який ви хочете написати. Вкажіть конкретні дані, як-от імена, дати, місця, причини тощо. Ви також можете попросити про креативні ідеї або пояснення. Після того, як лист буде створено, ви зможете відредагувати його та вставити на сторінку створення. Ось кілька прикладів інструкцій, які допоможуть вам розпочати роботу:';

$labels['compose_help_example_1'] = 'привітайся.';
$labels['compose_help_example_2'] = 'сьогоднішню зустріч скасовано';
$labels['compose_help_example_3'] = 'Я призначаю зустріч з Марджі на середу о 15:00, готую презентацію про переваги використання штучного інтелекту в наших маркетингових кампаніях, щоб переконати її';
$labels['compose_help_example_4'] = 'ми вирішили додати підтримку контенту, згенерованого штучним інтелектом, коротко пояснимо, що це таке';
$labels['compose_help_example_5'] = 'перевірка робочого завдання з минулого тижня, мені потрібно це завтра';
$labels['compose_help_example_6'] = 'просити про підвищення зарплати, я не отримував її більше року';
$labels['compose_help_example_7'] = 'нагадайте, що звіт потрібно здати завтра, інакше у команди будуть проблеми з Марджі, яка перебуває у стресовому стані';
$labels['compose_help_example_8'] = 'напишіть клієнтам про знижку 20% на всі товари до вівторка, ми розпродаємо одяг';
$labels['compose_help_example_9'] = 'попросити написати Лору і сказати їй, щоб вона організувала зустріч на день народження Кевіна на цих вихідних';
$labels['compose_help_example_10'] = 'запитати про хід роботи над новим сайтом, запропонувати 5 ідей маркетингових слоганів для головної сторінки, ми займаємося ремонтом автомобілів';
$labels['compose_help_example_11'] = 'розсилка про нові функції vpn, які ми додали: протокол wireguard, переадресація портів, kill switch, виділений IP; пояснюємо, що це таке';
$labels['compose_help_example_12'] = 'розсилка про нову програму лояльності, початкова вартість - $10 за картку, а потім до 20% знижок на більшість товарів протягом року';
$labels['compose_help_example_13'] = 'нетворкінг-електронну пошту комусь, ким я захоплююся і хочу професійно спілкуватися';
$labels['compose_help_example_14'] = 'професійна подяка за вчорашню допомогу з проектом';
$labels['compose_help_example_15'] = 'придумати 10 ідей стратегій для створення ефективного email-пітчу для інвесторів, вона буде працювати над цим завтра';
$labels['compose_help_example_16'] = 'професійний електронний лист про відставку з попередженням за 1 місяць згідно з контрактом, компанія називається ACME';
$labels['compose_help_example_17'] = 'переконливий електронний лист з проханням про підвищення, натякаючи, що я можу звільнитися, якщо не отримаю його, тому що є інші можливості';
$labels['compose_help_example_18'] = 'було приємно познайомитися з вами на конференції вчора, мені сподобалося наше спілкування, давайте залишатися на зв\'язку';

$labels['compose_help_use_example'] = '(Використовуйте це)';

// summaries
$labels['creating_summary'] = 'Створення зведення...';
$labels['show_summary'] = 'Показати резюме';
$labels['hide_summary'] = 'Приховати резюме';
$labels['recreate_summary'] = 'Повторно створити резюме';
$labels['email_has_no_text'] = 'Цей лист не містить тексту.';

// settings
$labels['setting_compose_style'] = 'Стиль';
$labels['setting_compose_length'] = 'Довжина';
$labels['setting_compose_language'] = 'Мова';
$labels['setting_compose_creativity'] = 'Творчість';
$labels['setting_language'] = 'Мова';

$labels['summarizing_messages'] = 'Підсумкові повідомлення';
$labels['setting_show_summary_on_mail_view'] = 'Показувати резюме ШІ під час перегляду листів';
$labels['setting_show_summary_on_list_hover'] = 'Показувати резюме ШІ при наведенні на список імейлів';
$labels['setting_list_summary_placement'] = 'Розміщення спливаючого вікна зі списком';

$labels['above'] = 'Вище';
$labels['below'] = 'Нижче';
$labels['left'] = 'Ліворуч.';
$labels['right'] = 'Так.';

$labels['ai_disclaimer'] = 'Результати роботи ШІ іноді можуть бути неповними або неправильними. Завжди перевіряйте важливу інформацію.';

// errors
$labels['api_error'] = 'Виникла помилка під час підключення до сервера ШІ. Будь ласка, зверніться до адміністратора.';
