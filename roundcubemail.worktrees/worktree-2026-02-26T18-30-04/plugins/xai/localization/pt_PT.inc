<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Assistente de IA';
$labels['ai_assistant'] = 'Assistente de IA';
$labels['ai_assistant_settings'] = 'Definições do Assistente de IA';

// compose page
$labels['compose_dialog_button_text'] = 'IA';
$labels['compose_dialog_button_title'] = 'Escrever mensagem com IA';

$labels['compose_label_to'] = 'Nome do destinatário';
$labels['compose_label_from'] = 'Nome do remetente';
$labels['compose_label_style'] = 'Estilo';
$labels['compose_label_length'] = 'Comprimento';
$labels['compose_label_language'] = 'Língua';
$labels['compose_label_creativity'] = 'Criatividade';
$labels['compose_label_instructions'] = 'Instruções';
$labels['compose_no_change'] = 'Sem alterações';
$labels['compose_instructions_placeholder'] = 'Exemplo: pedir para alterar a marcação para a próxima semana';

$labels['compose_style_assertive'] = 'Assertivo';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entusiasta';
$labels['compose_style_funny'] = 'Engraçado';
$labels['compose_style_informational'] = 'Informativo';
$labels['compose_style_professional'] = 'Profissional';
$labels['compose_style_urgent'] = 'Urgente';
$labels['compose_style_witty'] = 'espirituoso';

$labels['compose_length_short'] = 'Curto';
$labels['compose_length_medium'] = 'Médio';
$labels['compose_length_long'] = 'Longo';

$labels['compose_creativity_low'] = 'Baixa';
$labels['compose_creativity_medium'] = 'Médio';
$labels['compose_creativity_high'] = 'Elevado';

$labels['compose_insert_email'] = 'Inserir e-mail';
$labels['compose_generate_email'] = 'Gerar e-mail';
$labels['compose_generate_again'] = 'Gerar novamente';
$labels['compose_stop_generating'] = 'Parar de gerar';
$labels['compose_error_help_button'] = 'Ajuda';

$labels['compose_no_instructions'] = 'Escreva as instruções.';
$labels['compose_instructions_too_long'] = 'As suas instruções são demasiado longas; podem ter, no máximo, $n caracteres.';
$labels['compose_empty_response'] = 'O servidor devolveu uma resposta vazia.';

$labels['compose_tip_to'] = 'O nome do destinatário da nova mensagem de correio eletrónico. Pode especificar mais do que um nome ou utilizar cadeias de caracteres como "todos", "equipa", etc.';
$labels['compose_tip_from'] = 'A nova mensagem de correio eletrónico será assinada com este nome.';
$labels['compose_tip_style'] = 'O estilo de escrita preferido para o novo correio eletrónico.';
$labels['compose_tip_length'] = 'O comprimento preferido da nova mensagem de correio eletrónico.';
$labels['compose_tip_language'] = 'A língua em que a nova mensagem de correio eletrónico deve ser escrita. Especifique "Sem alterações" para escrever na língua em que as instruções são dadas.';
$labels['compose_tip_creativity'] = 'Uma maior criatividade dará origem a mensagens de correio eletrónico mais imprevisíveis e diversificadas; uma menor criatividade dará origem a mensagens de correio eletrónico mais factuais e diretas.';
$labels['compose_tip_instructions'] = 'Instruções sobre o que incluir na nova mensagem de correio eletrónico. Clique no botão Exemplos de instruções para ver alguns exemplos.';

$labels['compose_help_button_show'] = 'Exemplos de instruções';
$labels['compose_help_button_hide'] = 'Voltar';
$labels['compose_help_title'] = 'Ajuda e exemplos';
$labels['compose_help_info'] = 'Informe a IA sobre a mensagem de correio eletrónico que gostaria de escrever. Inclua pormenores, como nomes, datas, locais, motivos, etc. Também pode pedir ideias criativas ou explicações. Depois de o e-mail ser gerado, poderá ajustá-lo e inseri-lo na página de composição. Seguem-se alguns exemplos de instruções para o ajudar a começar:';

$labels['compose_help_example_1'] = 'dizer olá';
$labels['compose_help_example_2'] = 'a reunião de hoje foi cancelada';
$labels['compose_help_example_3'] = 'Marquei uma reunião com a Margy na quarta-feira às 15 horas, preparei uma apresentação sobre as vantagens da utilização da IA nas nossas campanhas de marketing para a convencer';
$labels['compose_help_example_4'] = 'decidimos adicionar suporte para conteúdo gerado por IA, explicar brevemente o que é';
$labels['compose_help_example_5'] = 'seguimento do trabalho da semana passada, preciso dele amanhã';
$labels['compose_help_example_6'] = 'pedir um aumento, não recebi nenhum durante mais de um ano';
$labels['compose_help_example_7'] = 'lembre-se de entregar o relatório amanhã ou a equipa terá problemas com Margy, que está stressada';
$labels['compose_help_example_8'] = 'escrever aos clientes sobre o desconto de 20% em todos os produtos até terça-feira, vendemos roupa';
$labels['compose_help_example_9'] = 'pedir para escrever à Laura e dizer-lhe para organizar um encontro para o aniversário do Kevin neste fim de semana';
$labels['compose_help_example_10'] = 'perguntar sobre o progresso do novo sítio Web, incluir 5 ideias de slogans de marketing para a primeira página, fazemos reparações de automóveis';
$labels['compose_help_example_11'] = 'newsletter sobre as novas funcionalidades vpn que adicionámos: protocolo wireguard, reencaminhamento de portas, kill switch, IP dedicado; explique o que são';
$labels['compose_help_example_12'] = 'newsletter sobre um novo programa de fidelização, o custo inicial é de 10 dólares para o cartão e depois até 20% de descontos na maioria dos produtos durante um ano';
$labels['compose_help_example_13'] = 'correio eletrónico de rede para alguém que admiro e com quem quero estabelecer uma ligação profissional';
$labels['compose_help_example_14'] = 'um agradecimento profissional pela ajuda com o projeto de ontem';
$labels['compose_help_example_15'] = 'apresentar 10 ideias de estratégias para elaborar um discurso eficaz por correio eletrónico para os investidores, que ela irá trabalhar amanhã';
$labels['compose_help_example_16'] = 'um e-mail de demissão profissional, com aviso prévio de 1 mês, conforme o contrato, a empresa chama-se ACME';
$labels['compose_help_example_17'] = 'e-mail convincente para pedir uma promoção, dando a entender que me posso demitir se não a conseguir porque há outras oportunidades';
$labels['compose_help_example_18'] = 'Foi um prazer conhecê-lo na conferência de ontem, gostei da nossa conversa, vamos manter-nos em contacto';

$labels['compose_help_use_example'] = '(Utilizar isto)';

// summaries
$labels['creating_summary'] = 'Criar um resumo...';
$labels['show_summary'] = 'Mostrar resumo';
$labels['hide_summary'] = 'Ocultar resumo';
$labels['recreate_summary'] = 'Recriar resumo';
$labels['email_has_no_text'] = 'Esta mensagem de correio eletrónico não tem texto.';

// settings
$labels['setting_compose_style'] = 'Estilo';
$labels['setting_compose_length'] = 'Comprimento';
$labels['setting_compose_language'] = 'Língua';
$labels['setting_compose_creativity'] = 'Criatividade';
$labels['setting_language'] = 'Língua';

$labels['summarizing_messages'] = 'Resumir mensagens';
$labels['setting_show_summary_on_mail_view'] = 'Mostrar resumos de IA ao visualizar mensagens de correio eletrónico';
$labels['setting_show_summary_on_list_hover'] = 'Mostrar resumos de IA quando se passa o rato sobre a lista de correio eletrónico';
$labels['setting_list_summary_placement'] = 'Colocação da janela pop-up de resumo da lista';

$labels['above'] = 'Acima';
$labels['below'] = 'Abaixo';
$labels['left'] = 'Esquerda';
$labels['right'] = 'Certo';

$labels['ai_disclaimer'] = 'Por vezes, os resultados da IA podem estar incompletos ou incorrectos. Verifique sempre duas vezes as informações essenciais.';

// errors
$labels['api_error'] = 'Ocorreu um erro durante a ligação ao servidor AI. Contacte o administrador.';
