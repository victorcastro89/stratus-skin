<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'AI 어시스턴트';
$labels['ai_assistant'] = 'AI 어시스턴트';
$labels['ai_assistant_settings'] = 'AI 어시스턴트 설정';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'AI로 메시지 작성';

$labels['compose_label_to'] = '수신자 이름';
$labels['compose_label_from'] = '발신자 이름';
$labels['compose_label_style'] = '스타일';
$labels['compose_label_length'] = '길이';
$labels['compose_label_language'] = '언어';
$labels['compose_label_creativity'] = '창의성';
$labels['compose_label_instructions'] = '지침';
$labels['compose_no_change'] = '변경 사항 없음';
$labels['compose_instructions_placeholder'] = '예: 다음 주 약속 변경 요청하기';

$labels['compose_style_assertive'] = '어설픈';
$labels['compose_style_casual'] = '캐주얼';
$labels['compose_style_enthusiastic'] = '열정적인';
$labels['compose_style_funny'] = 'Funny';
$labels['compose_style_informational'] = '정보 제공';
$labels['compose_style_professional'] = '전문가';
$labels['compose_style_urgent'] = '긴급';
$labels['compose_style_witty'] = '위트';

$labels['compose_length_short'] = '짧은';
$labels['compose_length_medium'] = 'Medium';
$labels['compose_length_long'] = 'Long';

$labels['compose_creativity_low'] = '낮음';
$labels['compose_creativity_medium'] = 'Medium';
$labels['compose_creativity_high'] = '높음';

$labels['compose_insert_email'] = '이메일 삽입';
$labels['compose_generate_email'] = '이메일 생성';
$labels['compose_generate_again'] = '다시 생성';
$labels['compose_stop_generating'] = '생성 중지';
$labels['compose_error_help_button'] = '도움말';

$labels['compose_no_instructions'] = '지침을 입력합니다.';
$labels['compose_instructions_too_long'] = '지침이 너무 길어서 최대 $n자까지만 입력할 수 있습니다.';
$labels['compose_empty_response'] = '서버가 빈 응답을 반환했습니다.';

$labels['compose_tip_to'] = '새 이메일을 받는 사람의 이름입니다. 둘 이상의 이름을 지정하거나 \'모두\', \'팀\' 등과 같은 문자열을 사용할 수 있습니다.';
$labels['compose_tip_from'] = '새 이메일은 이 이름으로 서명됩니다.';
$labels['compose_tip_style'] = '새 이메일에 선호하는 글쓰기 스타일입니다.';
$labels['compose_tip_length'] = '새 이메일의 기본 길이입니다.';
$labels['compose_tip_language'] = '새 이메일을 작성할 언어입니다. 지침이 제공되는 언어로 작성하려면 "변경 없음"을 지정합니다.';
$labels['compose_tip_creativity'] = '창의성이 높을수록 예측 불가능하고 다양한 이메일이 생성되고, 창의성이 낮을수록 사실적이고 핵심을 찌르는 이메일이 생성됩니다.';
$labels['compose_tip_instructions'] = '새 이메일에 포함할 내용에 대한 지침입니다. 지침 예시 버튼을 클릭하면 몇 가지 예시를 볼 수 있습니다.';

$labels['compose_help_button_show'] = '지침 예시';
$labels['compose_help_button_hide'] = '뒤로';
$labels['compose_help_title'] = '도움말 및 예제';
$labels['compose_help_info'] = '작성하려는 이메일에 대해 AI에게 알려주세요. 이름, 날짜, 위치, 이유 등 구체적인 내용을 포함하세요. 창의적인 아이디어나 설명을 요청할 수도 있습니다. 이메일이 생성된 후에는 이를 수정하여 작성 페이지에 삽입할 수 있습니다. 다음은 시작하는 데 도움이 되는 몇 가지 샘플 지침입니다:';

$labels['compose_help_example_1'] = '인사하기';
$labels['compose_help_example_2'] = '오늘 회의가 취소되었습니다.';
$labels['compose_help_example_3'] = '수요일 오후 3시에 Margy와 미팅을 예약하고 마케팅 캠페인에서 AI를 사용할 때의 이점에 대한 프레젠테이션을 준비하여 그녀를 설득합니다.';
$labels['compose_help_example_4'] = 'AI 생성 콘텐츠에 대한 지원을 추가하기로 결정했으며, 이것이 무엇인지 간단히 설명합니다.';
$labels['compose_help_example_5'] = '지난주 작업 과제에 대한 후속 조치, 내일 필요합니다.';
$labels['compose_help_example_6'] = '인상을 요청했는데 1년이 넘도록 인상을 받지 못했습니다.';
$labels['compose_help_example_7'] = '내일 보고서를 제출하라고 상기시키지 않으면 스트레스를 받는 마기에게 문제가 생길 것입니다.';
$labels['compose_help_example_8'] = '화요일까지 모든 제품에 대한 20 % 할인에 대해 고객에게 쓰고, 우리는 옷을 판매합니다.';
$labels['compose_help_example_9'] = '로라에게 편지를 써서 이번 주말 케빈의 생일을 위한 모임을 준비해달라고 부탁합니다.';
$labels['compose_help_example_10'] = '새 웹 사이트의 진행 상황을 묻고, 첫 페이지의 마케팅 슬로건에 대한 5 가지 아이디어를 포함하고, 우리는 자동차 수리를합니다.';
$labels['compose_help_example_11'] = '뉴스레터를 통해 와이어가드 프로토콜, 포트 포워딩, 킬 스위치, 전용 IP 등 새로 추가된 VPN 기능에 대해 설명합니다.';
$labels['compose_help_example_12'] = '새로운 로열티 프로그램에 대한 뉴스레터, 초기 카드 비용은 10달러이며, 이후 1년 동안 대부분의 제품에 대해 최대 20% 할인을 제공합니다.';
$labels['compose_help_example_13'] = '존경하고 전문적으로 연결하고 싶은 사람에게 네트워킹 이메일 보내기';
$labels['compose_help_example_14'] = '어제 프로젝트에 도움을 주신 전문가에게 감사드립니다.';
$labels['compose_help_example_15'] = '투자자에게 효과적인 이메일 프레젠테이션을 만들기 위한 10가지 전략 아이디어를 생각해 내면 내일 작업할 것입니다.';
$labels['compose_help_example_16'] = '계약에 따라 1 개월 전에 통지하는 전문 사직 이메일, 회사는 ACME라고합니다.';
$labels['compose_help_example_17'] = '승진을 요청하는 강압적인 이메일, 다른 기회가 있기 때문에 승진을 하지 못하면 사직할 수도 있다는 암시';
$labels['compose_help_example_18'] = '어제 컨퍼런스에서 만나서 반가웠어요, 대화 즐거웠어요, 계속 연락합시다.';

$labels['compose_help_use_example'] = '(이것을 사용하세요)';

// summaries
$labels['creating_summary'] = '요약 만들기...';
$labels['show_summary'] = '요약 표시';
$labels['hide_summary'] = '요약 숨기기';
$labels['recreate_summary'] = '요약 다시 만들기';
$labels['email_has_no_text'] = '이 이메일에는 텍스트가 없습니다.';

// settings
$labels['setting_compose_style'] = '스타일';
$labels['setting_compose_length'] = '길이';
$labels['setting_compose_language'] = '언어';
$labels['setting_compose_creativity'] = '창의성';
$labels['setting_language'] = '언어';

$labels['summarizing_messages'] = '메시지 요약';
$labels['setting_show_summary_on_mail_view'] = '이메일을 볼 때 AI 요약 표시';
$labels['setting_show_summary_on_list_hover'] = '이메일 목록 위에 마우스를 올리면 AI 요약 표시';
$labels['setting_list_summary_placement'] = '목록 요약 팝업 배치';

$labels['above'] = '위';
$labels['below'] = '아래';
$labels['left'] = '왼쪽';
$labels['right'] = '오른쪽';

$labels['ai_disclaimer'] = 'AI 출력은 때때로 불완전하거나 부정확할 수 있습니다. 항상 필수 정보를 다시 확인하세요.';

// errors
$labels['api_error'] = 'AI 서버에 연결하는 동안 오류가 발생했습니다. 관리자에게 문의하세요.';
