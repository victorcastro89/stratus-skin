<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI 助理';
$labels['ai_assistant'] = 'AI 助理';
$labels['ai_assistant_settings'] = 'AI 助理設定';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = '使用 AI 寫訊息';

$labels['compose_label_to'] = '收件人姓名';
$labels['compose_label_from'] = '寄件者姓名';
$labels['compose_label_style'] = '風格';
$labels['compose_label_length'] = '長度';
$labels['compose_label_language'] = '語言';
$labels['compose_label_creativity'] = '創意';
$labels['compose_label_instructions'] = '說明';
$labels['compose_no_change'] = '無變更';
$labels['compose_instructions_placeholder'] = '例如：要求更改下週的預約';

$labels['compose_style_assertive'] = '自信';
$labels['compose_style_casual'] = '休閒';
$labels['compose_style_enthusiastic'] = '熱情';
$labels['compose_style_funny'] = '有趣';
$labels['compose_style_informational'] = '資訊';
$labels['compose_style_professional'] = '專業';
$labels['compose_style_urgent'] = '緊急';
$labels['compose_style_witty'] = '機智';

$labels['compose_length_short'] = '短';
$labels['compose_length_medium'] = '中型';
$labels['compose_length_long'] = '長';

$labels['compose_creativity_low'] = '低';
$labels['compose_creativity_medium'] = '中型';
$labels['compose_creativity_high'] = '高';

$labels['compose_insert_email'] = '插入電子郵件';
$labels['compose_generate_email'] = '產生電子郵件';
$labels['compose_generate_again'] = '再次產生';
$labels['compose_stop_generating'] = '停止產生';
$labels['compose_error_help_button'] = '說明';

$labels['compose_no_instructions'] = '輸入說明。';
$labels['compose_instructions_too_long'] = '您的指令太長，最多只能有 $n 個字元。';
$labels['compose_empty_response'] = '伺服器傳回一個空的回應。';

$labels['compose_tip_to'] = '新電子郵件收件人的姓名。您可以指定多個名稱，或使用「所有人」、「團隊」等字串。';
$labels['compose_tip_from'] = '新電子郵件將以此名稱簽署。';
$labels['compose_tip_style'] = '新電子郵件的首選書寫方式。';
$labels['compose_tip_length'] = '新電子郵件的首選長度。';
$labels['compose_tip_language'] = '新電子郵件的撰寫語言。指定 "No change"（無變更），以指示所使用的語言撰寫。';
$labels['compose_tip_creativity'] = '創意越高，產生的電子郵件就越不可預測、越多樣化；創意越低，產生的電子郵件就越實事求是、越切中要害。';
$labels['compose_tip_instructions'] = '說明新電子郵件中應包含的內容。按一下指示範例按鈕，查看一些範例。';

$labels['compose_help_button_show'] = '指示範例';
$labels['compose_help_button_hide'] = '返回';
$labels['compose_help_title'] = '說明與範例';
$labels['compose_help_info'] = '告訴 AI 您想寫的電子郵件。包括具體內容，例如姓名、日期、地點、原因等。您也可以要求有創意的想法或解釋。電子郵件產生後，您就可以對其進行調整，並將其插入撰寫頁面。以下是一些範例說明，讓您可以開始使用：';

$labels['compose_help_example_1'] = '打招呼';
$labels['compose_help_example_2'] = '今天的會議已取消';
$labels['compose_help_example_3'] = '我和 Margy 約好在星期三下午三點見面，準備一個關於在我們的行銷活動中使用 AI 的優勢的簡報，以說服她';
$labels['compose_help_example_4'] = '我們決定新增對 AI 產生內容的支援，請簡短說明它是什麼';
$labels['compose_help_example_5'] = '跟進上週的工作任務，我明天就需要';
$labels['compose_help_example_6'] = '要求加薪，我一年多沒加薪了';
$labels['compose_help_example_7'] = '提醒明天要提交報告，否則團隊會與壓力很大的 Margy 發生爭執';
$labels['compose_help_example_8'] = '寫給客戶關於所有產品的 20% 折扣直到星期二，我們賣衣服';
$labels['compose_help_example_9'] = '要求寫信給 Laura，告訴她本週末為 Kevin 的生日組織一次聚會';
$labels['compose_help_example_10'] = '詢問新網站的進度，包括 5 個關於頭版行銷口號的想法，我們提供汽車維修服務';
$labels['compose_help_example_11'] = '簡報我們新增的 vpn 功能：wireguard 通訊協定、連接埠轉寄、關閉切換器、專屬 IP；解釋它們是什麼。';
$labels['compose_help_example_12'] = '有關新的忠誠計劃的通訊，初始成本為 10 美元的卡，然後在一年內對大多數產品提供高達 20% 的折扣';
$labels['compose_help_example_13'] = '發電子郵件給我仰慕並想與之建立專業聯繫的人，建立聯繫網絡';
$labels['compose_help_example_14'] = '非常感謝您昨天對專案的協助';
$labels['compose_help_example_15'] = '她想出了 10 個策略點子，用來撰寫有效的電子郵件投稿給投資人，她明天就會開始工作';
$labels['compose_help_example_16'] = '一封專業的辭職電子郵件，根據合約給予 1 個月的通知，公司名為 ACME';
$labels['compose_help_example_17'] = '蠱惑的電子郵件，要求升職，暗示如果我沒有得到升職，我可能會辭職，因為還有其他機會';
$labels['compose_help_example_18'] = '很高興昨天在會議上見到你，我很享受我們的聊天，讓我們保持聯繫。';

$labels['compose_help_use_example'] = '(使用這個)';

// summaries
$labels['creating_summary'] = '建立摘要...';
$labels['show_summary'] = '顯示摘要';
$labels['hide_summary'] = '隱藏摘要';
$labels['recreate_summary'] = '重新建立摘要';
$labels['email_has_no_text'] = '此電子郵件沒有文字。';

// settings
$labels['setting_compose_style'] = '風格';
$labels['setting_compose_length'] = '長度';
$labels['setting_compose_language'] = '語言';
$labels['setting_compose_creativity'] = '創意';
$labels['setting_language'] = '語言';

$labels['summarizing_messages'] = '總結訊息';
$labels['setting_show_summary_on_mail_view'] = '檢視電子郵件時顯示 AI 摘要';
$labels['setting_show_summary_on_list_hover'] = '將滑鼠停留在電子郵件清單上時，顯示 AI 摘要';
$labels['setting_list_summary_placement'] = '清單摘要彈出式顯示位置';

$labels['above'] = '以上';
$labels['below'] = '下圖';
$labels['left'] = '左邊';
$labels['right'] = '對';

$labels['ai_disclaimer'] = 'AI 輸出有時可能不完整或不正確。請務必仔細檢查重要資訊。';

// errors
$labels['api_error'] = '連接 AI 伺服器時發生錯誤。請聯絡管理員。';
