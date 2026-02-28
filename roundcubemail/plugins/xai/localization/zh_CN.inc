<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = '人工智能助理';
$labels['ai_assistant'] = '人工智能助理';
$labels['ai_assistant_settings'] = '人工智能助理设置';

// compose page
$labels['compose_dialog_button_text'] = '人工智能';
$labels['compose_dialog_button_title'] = '用人工智能撰写信息';

$labels['compose_label_to'] = '收件人姓名';
$labels['compose_label_from'] = '发件人姓名';
$labels['compose_label_style'] = '风格';
$labels['compose_label_length'] = '长度';
$labels['compose_label_language'] = '语言';
$labels['compose_label_creativity'] = '创造力';
$labels['compose_label_instructions'] = '说明';
$labels['compose_no_change'] = '无变化';
$labels['compose_instructions_placeholder'] = '例如：要求更改下周的预约';

$labels['compose_style_assertive'] = '自信';
$labels['compose_style_casual'] = '休闲';
$labels['compose_style_enthusiastic'] = '热情';
$labels['compose_style_funny'] = '有趣';
$labels['compose_style_informational'] = '信息';
$labels['compose_style_professional'] = '专业人员';
$labels['compose_style_urgent'] = '紧急';
$labels['compose_style_witty'] = '诙谐';

$labels['compose_length_short'] = '短';
$labels['compose_length_medium'] = '中型';
$labels['compose_length_long'] = '长';

$labels['compose_creativity_low'] = '低';
$labels['compose_creativity_medium'] = '中型';
$labels['compose_creativity_high'] = '高';

$labels['compose_insert_email'] = '插入电子邮件';
$labels['compose_generate_email'] = '生成电子邮件';
$labels['compose_generate_again'] = '再次生成';
$labels['compose_stop_generating'] = '停止生成';
$labels['compose_error_help_button'] = '帮助';

$labels['compose_no_instructions'] = '输入说明。';
$labels['compose_instructions_too_long'] = '您的指令太长，最多只能有 $n 个字符。';
$labels['compose_empty_response'] = '服务器返回空响应。';

$labels['compose_tip_to'] = '新电子邮件的收件人姓名。您可以指定多个名称，或使用 "所有人"、"团队 "等字符串。';
$labels['compose_tip_from'] = '新电子邮件将使用此名称签名。';
$labels['compose_tip_style'] = '新电子邮件的首选写作风格。';
$labels['compose_tip_length'] = '新电子邮件的首选长度。';
$labels['compose_tip_language'] = '新电子邮件应使用的语言。指定 "不变"，则使用发出指示时使用的语言。';
$labels['compose_tip_creativity'] = '创造力越高，产生的电子邮件就越难以预测，也就越多样化；创造力越低，产生的电子邮件就越实事求是，也就越开门见山。';
$labels['compose_tip_instructions'] = '关于新电子邮件内容的说明。单击 "说明示例 "按钮查看一些示例。';

$labels['compose_help_button_show'] = '教学示例';
$labels['compose_help_button_hide'] = '返回';
$labels['compose_help_title'] = '帮助与示例';
$labels['compose_help_info'] = '告诉人工智能您想写的电子邮件。包括具体内容，如姓名、日期、地点、原因等。您还可以要求提供有创意的想法或解释。电子邮件生成后，您可以对其进行调整并将其插入撰写页面。以下是一些示例说明，供您参考：';

$labels['compose_help_example_1'] = '问好';
$labels['compose_help_example_2'] = '取消今天的会议';
$labels['compose_help_example_3'] = '我约了 Margy 在周三下午 3 点见面，准备介绍在营销活动中使用人工智能的优势，说服她';
$labels['compose_help_example_4'] = '我们决定添加对人工智能生成内容的支持，请简要说明它是什么';
$labels['compose_help_example_5'] = '跟进上周的工作任务，我明天需要它';
$labels['compose_help_example_6'] = '要求加薪，我一年多没加薪了';
$labels['compose_help_example_7'] = '提醒明天提交报告，否则团队会给压力很大的 Margy 带来麻烦';
$labels['compose_help_example_8'] = '向客户介绍我们销售的服装在本周二之前所有产品均可享受 20% 的折扣。';
$labels['compose_help_example_9'] = '要求写信给劳拉，告诉她本周末为凯文的生日组织一次聚会';
$labels['compose_help_example_10'] = '询问新网站的进展情况，包括首页营销口号的 5 个想法，我们提供汽车维修服务';
$labels['compose_help_example_11'] = '关于我们新增的 VPN 功能的简讯：wireguard 协议、端口转发、杀毒开关、专用 IP；解释它们是什么';
$labels['compose_help_example_12'] = '关于一项新的忠诚度计划的通讯，初始费用为 10 美元，然后在一年内对大多数产品提供高达 20% 的折扣';
$labels['compose_help_example_13'] = '给我钦佩并希望与之建立职业联系的人发送网络电子邮件';
$labels['compose_help_example_14'] = '感谢您昨天在项目中提供的专业帮助';
$labels['compose_help_example_15'] = '她想出了向投资者发送有效电子邮件的 10 个策略点子，她明天就会开始工作';
$labels['compose_help_example_16'] = '一封专业的辞职邮件，按照合同规定提前 1 个月通知，公司名为 ACME';
$labels['compose_help_example_17'] = '写邮件要求升职，暗示如果得不到可能会辞职，因为还有其他机会';
$labels['compose_help_example_18'] = '很高兴昨天在会议上见到你，我们聊得很愉快，我们保持联系吧';

$labels['compose_help_use_example'] = '(用这个）';

// summaries
$labels['creating_summary'] = '创建摘要...';
$labels['show_summary'] = '显示摘要';
$labels['hide_summary'] = '隐藏摘要';
$labels['recreate_summary'] = '重新创建摘要';
$labels['email_has_no_text'] = '这封邮件没有文本。';

// settings
$labels['setting_compose_style'] = '风格';
$labels['setting_compose_length'] = '长度';
$labels['setting_compose_language'] = '语言';
$labels['setting_compose_creativity'] = '创造力';
$labels['setting_language'] = '语言';

$labels['summarizing_messages'] = '总结信息';
$labels['setting_show_summary_on_mail_view'] = '查看电子邮件时显示人工智能摘要';
$labels['setting_show_summary_on_list_hover'] = '悬停在电子邮件列表上时显示人工智能摘要';
$labels['setting_list_summary_placement'] = '列表摘要弹出式窗口位置';

$labels['above'] = '以上';
$labels['below'] = '下图';
$labels['left'] = '左侧';
$labels['right'] = '对';

$labels['ai_disclaimer'] = '人工智能输出有时可能不完整或不正确。请务必仔细核对重要信息。';

// errors
$labels['api_error'] = '连接 AI 服务器时发生错误。请联系管理员。';
