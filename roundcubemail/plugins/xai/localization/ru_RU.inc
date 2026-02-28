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
$labels['ai_assistant_settings'] = 'Настройки помощника ИИ';

// compose page
$labels['compose_dialog_button_text'] = 'ИИ';
$labels['compose_dialog_button_title'] = 'Составьте сообщение с помощью искусственного интеллекта';

$labels['compose_label_to'] = 'Имя получателя';
$labels['compose_label_from'] = 'Имя отправителя';
$labels['compose_label_style'] = 'Стиль';
$labels['compose_label_length'] = 'Длина';
$labels['compose_label_language'] = 'Язык';
$labels['compose_label_creativity'] = 'Творчество';
$labels['compose_label_instructions'] = 'Инструкции';
$labels['compose_no_change'] = 'Без изменений';
$labels['compose_instructions_placeholder'] = 'Пример: попросить перенести встречу на следующую неделю';

$labels['compose_style_assertive'] = 'Ассертивный';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Энтузиаст';
$labels['compose_style_funny'] = 'Смешно';
$labels['compose_style_informational'] = 'Информационный';
$labels['compose_style_professional'] = 'Профессионал';
$labels['compose_style_urgent'] = 'Срочно';
$labels['compose_style_witty'] = 'Остроумный';

$labels['compose_length_short'] = 'Коротко';
$labels['compose_length_medium'] = 'Средний';
$labels['compose_length_long'] = 'Длинный';

$labels['compose_creativity_low'] = 'Низкий';
$labels['compose_creativity_medium'] = 'Средний';
$labels['compose_creativity_high'] = 'Высокий';

$labels['compose_insert_email'] = 'Вставьте электронную почту';
$labels['compose_generate_email'] = 'Создать электронную почту';
$labels['compose_generate_again'] = 'Генерировать снова';
$labels['compose_stop_generating'] = 'Остановить генерацию';
$labels['compose_error_help_button'] = 'Помощь';

$labels['compose_no_instructions'] = 'Введите инструкции.';
$labels['compose_instructions_too_long'] = 'Ваши инструкции слишком длинные; они могут содержать не более $n символов.';
$labels['compose_empty_response'] = 'Сервер вернул пустой ответ.';

$labels['compose_tip_to'] = 'Имя получателя нового сообщения электронной почты. Вы можете указать несколько имен или использовать строки типа "все", "команда" и т.д.';
$labels['compose_tip_from'] = 'Новое письмо будет подписано этим именем.';
$labels['compose_tip_style'] = 'Предпочтительный стиль написания нового электронного письма.';
$labels['compose_tip_length'] = 'Предпочтительная длина нового сообщения электронной почты.';
$labels['compose_tip_language'] = 'Язык, на котором должно быть написано новое письмо. Укажите "Без изменений", чтобы писать на том языке, на котором даны инструкции.';
$labels['compose_tip_creativity'] = 'При более высокой креативности письма будут более непредсказуемыми и разнообразными; при более низкой креативности письма будут более фактичными и точными.';
$labels['compose_tip_instructions'] = 'Инструкции о том, что следует включить в новое письмо. Нажмите кнопку Примеры инструкций, чтобы посмотреть некоторые примеры.';

$labels['compose_help_button_show'] = 'Примеры инструкций';
$labels['compose_help_button_hide'] = 'Назад';
$labels['compose_help_title'] = 'Помощь и примеры';
$labels['compose_help_info'] = 'Расскажите ИИ о письме, которое вы хотели бы написать. Укажите конкретные детали, например, имена, даты, места, причины и т.д. Вы также можете попросить креативные идеи или пояснения. После того как письмо будет сгенерировано, вы сможете подправить его и вставить на страницу составления. Вот несколько примеров инструкций для начала работы:';

$labels['compose_help_example_1'] = 'поздороваться';
$labels['compose_help_example_2'] = 'сегодняшнее собрание было отменено';
$labels['compose_help_example_3'] = 'Я назначил встречу с Марджи на 3 часа дня в среду, подготовил презентацию о преимуществах использования искусственного интеллекта в наших маркетинговых кампаниях, чтобы убедить ее.';
$labels['compose_help_example_4'] = 'мы решили добавить поддержку контента, созданного искусственным интеллектом, кратко объясним, что это такое';
$labels['compose_help_example_5'] = 'продолжение работы над заданием с прошлой недели, оно нужно мне завтра';
$labels['compose_help_example_6'] = 'попросить прибавки, я не получал ее больше года.';
$labels['compose_help_example_7'] = 'напомнить о необходимости представить отчет завтра, иначе у команды будут неприятности с Марджи, которая находится в стрессовом состоянии';
$labels['compose_help_example_8'] = 'напишите клиентам о 20% скидке на все товары до вторника, мы продаем одежду';
$labels['compose_help_example_9'] = 'попросить написать Лоре и попросить ее организовать встречу на день рождения Кевина в эти выходные';
$labels['compose_help_example_10'] = 'спросить о ходе работы над новым веб-сайтом, включить 5 идей маркетинговых слоганов для первой страницы, мы занимаемся ремонтом автомобилей';
$labels['compose_help_example_11'] = 'рассылка о новых функциях vpn, которые мы добавили: протокол wireguard, перенаправление портов, kill switch, выделенный IP; объясните, что это такое';
$labels['compose_help_example_12'] = 'рассылка о новой программе лояльности, начальная стоимость - $10 за карту, а затем до 20% скидки на большинство товаров в течение года';
$labels['compose_help_example_13'] = 'сетевое письмо человеку, которым я восхищаюсь и с которым хочу наладить профессиональные связи';
$labels['compose_help_example_14'] = 'профессиональная благодарность за помощь с проектом вчера';
$labels['compose_help_example_15'] = 'придумать 10 идей стратегий для составления эффективного электронного письма инвесторам, она будет работать над этим завтра';
$labels['compose_help_example_16'] = 'профессиональное письмо об увольнении с уведомлением за 1 месяц в соответствии с контрактом, компания называется ACME';
$labels['compose_help_example_17'] = 'убедительное письмо с просьбой о повышении, намекающее на то, что я могу уволиться, если не получу его, потому что есть другие возможности';
$labels['compose_help_example_18'] = 'рад был встретиться с вами на конференции вчера, мне понравилось наше общение, давайте оставаться на связи';

$labels['compose_help_use_example'] = '(Используйте это)';

// summaries
$labels['creating_summary'] = 'Создание резюме...';
$labels['show_summary'] = 'Показать краткое содержание';
$labels['hide_summary'] = 'Скрыть резюме';
$labels['recreate_summary'] = 'Создайте резюме заново';
$labels['email_has_no_text'] = 'Это письмо не содержит текста.';

// settings
$labels['setting_compose_style'] = 'Стиль';
$labels['setting_compose_length'] = 'Длина';
$labels['setting_compose_language'] = 'Язык';
$labels['setting_compose_creativity'] = 'Творчество';
$labels['setting_language'] = 'Язык';

$labels['summarizing_messages'] = 'Обобщение сообщений';
$labels['setting_show_summary_on_mail_view'] = 'Показывайте сводки ИИ при просмотре электронных писем';
$labels['setting_show_summary_on_list_hover'] = 'Показывайте сводки ИИ при наведении курсора на список адресов электронной почты';
$labels['setting_list_summary_placement'] = 'Размещение всплывающего окна сводки списка';

$labels['above'] = 'Выше';
$labels['below'] = 'Ниже';
$labels['left'] = 'Слева';
$labels['right'] = 'Справа';

$labels['ai_disclaimer'] = 'Выходные данные AI могут быть неполными или неправильными. Всегда перепроверяйте важную информацию.';

// errors
$labels['api_error'] = 'При подключении к серверу AI произошла ошибка. Пожалуйста, свяжитесь с администратором.';
