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
$labels['ai_assistant_settings'] = 'Configurações do Assistente de IA';

// compose page
$labels['compose_dialog_button_text'] = 'IA';
$labels['compose_dialog_button_title'] = 'Compor mensagem com IA';

$labels['compose_label_to'] = 'Nome do destinatário';
$labels['compose_label_from'] = 'Nome do remetente';
$labels['compose_label_style'] = 'Estilo';
$labels['compose_label_length'] = 'Comprimento';
$labels['compose_label_language'] = 'Idioma';
$labels['compose_label_creativity'] = 'Criatividade';
$labels['compose_label_instructions'] = 'Instruções';
$labels['compose_no_change'] = 'Nenhuma alteração';
$labels['compose_instructions_placeholder'] = 'Exemplo: pedir para mudar o compromisso para a próxima semana';

$labels['compose_style_assertive'] = 'Assertivo';
$labels['compose_style_casual'] = 'Casual';
$labels['compose_style_enthusiastic'] = 'Entusiasmado';
$labels['compose_style_funny'] = 'Engraçado';
$labels['compose_style_informational'] = 'Informativo';
$labels['compose_style_professional'] = 'Profissional';
$labels['compose_style_urgent'] = 'Urgente';
$labels['compose_style_witty'] = 'Inteligente';

$labels['compose_length_short'] = 'Curto';
$labels['compose_length_medium'] = 'Médio';
$labels['compose_length_long'] = 'Longo';

$labels['compose_creativity_low'] = 'Baixa';
$labels['compose_creativity_medium'] = 'Médio';
$labels['compose_creativity_high'] = 'Alta';

$labels['compose_insert_email'] = 'Inserir e-mail';
$labels['compose_generate_email'] = 'Gerar e-mail';
$labels['compose_generate_again'] = 'Gerar novamente';
$labels['compose_stop_generating'] = 'Parar de gerar';
$labels['compose_error_help_button'] = 'Ajuda';

$labels['compose_no_instructions'] = 'Digite as instruções.';
$labels['compose_instructions_too_long'] = 'Suas instruções são muito longas; elas podem ter no máximo $n caracteres.';
$labels['compose_empty_response'] = 'O servidor retornou uma resposta vazia.';

$labels['compose_tip_to'] = 'O nome do destinatário do novo e-mail. É possível especificar mais de um nome ou usar cadeias de caracteres como "todos", "equipe" etc.';
$labels['compose_tip_from'] = 'O novo e-mail será assinado com esse nome.';
$labels['compose_tip_style'] = 'O estilo de escrita preferido para o novo e-mail.';
$labels['compose_tip_length'] = 'O tamanho preferido do novo e-mail.';
$labels['compose_tip_language'] = 'O idioma no qual o novo e-mail deve ser escrito. Especifique "Sem alteração" para escrever no idioma em que as instruções são fornecidas.';
$labels['compose_tip_creativity'] = 'Uma criatividade maior gerará e-mails mais imprevisíveis e diversificados; uma criatividade menor gerará e-mails mais factuais e diretos.';
$labels['compose_tip_instructions'] = 'Instruções sobre o que incluir no novo e-mail. Clique no botão Exemplos de instruções para ver alguns exemplos.';

$labels['compose_help_button_show'] = 'Exemplos de instruções';
$labels['compose_help_button_hide'] = 'Voltar';
$labels['compose_help_title'] = 'Ajuda e exemplos';
$labels['compose_help_info'] = 'Informe a IA sobre o e-mail que você gostaria de escrever. Inclua detalhes específicos, como nomes, datas, locais, motivos etc. Você também pode pedir ideias criativas ou explicações. Depois que o e-mail for gerado, você poderá ajustá-lo e inseri-lo na página de composição. Aqui estão alguns exemplos de instruções para você começar:';

$labels['compose_help_example_1'] = 'cumprimentar';
$labels['compose_help_example_2'] = 'a reunião de hoje foi cancelada';
$labels['compose_help_example_3'] = 'Marquei uma reunião com Margy na quarta-feira, às 15 horas, e preparei uma apresentação sobre as vantagens de usar IA em nossas campanhas de marketing para convencê-la';
$labels['compose_help_example_4'] = 'decidimos adicionar suporte para conteúdo gerado por IA, explicar brevemente o que é';
$labels['compose_help_example_5'] = 'acompanhamento da tarefa de trabalho da semana passada, preciso dela amanhã';
$labels['compose_help_example_6'] = 'pedir um aumento, não recebi nenhum por mais de um ano';
$labels['compose_help_example_7'] = 'lembre-se de enviar o relatório amanhã ou a equipe terá problemas com Margy, que está estressada';
$labels['compose_help_example_8'] = 'escreva aos clientes sobre o desconto de 20% em todos os produtos até terça-feira, nós vendemos roupas';
$labels['compose_help_example_9'] = 'pedir para escrever para Laura e dizer a ela para organizar uma reunião para o aniversário de Kevin neste fim de semana';
$labels['compose_help_example_10'] = 'pergunte sobre o progresso do novo site, inclua 5 ideias de slogans de marketing para a primeira página, fazemos reparos em carros';
$labels['compose_help_example_11'] = 'boletim informativo sobre os novos recursos de vpn que adicionamos: protocolo wireguard, encaminhamento de porta, kill switch, IP dedicado; explique o que eles são';
$labels['compose_help_example_12'] = 'boletim informativo sobre um novo programa de fidelidade, com custo inicial de US$ 10 para o cartão e descontos de até 20% na maioria dos produtos durante um ano';
$labels['compose_help_example_13'] = 'e-mail de rede para alguém que eu admiro e com quem quero me relacionar profissionalmente';
$labels['compose_help_example_14'] = 'um agradecimento profissional pela ajuda com o projeto de ontem';
$labels['compose_help_example_15'] = 'apresentou 10 ideias de estratégias para a elaboração de uma apresentação eficaz por e-mail aos investidores; ela trabalhará nisso amanhã';
$labels['compose_help_example_16'] = 'um e-mail de demissão profissional, avisando com um mês de antecedência, conforme o contrato, a empresa se chama ACME';
$labels['compose_help_example_17'] = 'e-mail convincente para solicitar uma promoção, dando a entender que eu poderia me demitir se não conseguisse, porque há outras oportunidades';
$labels['compose_help_example_18'] = 'Foi bom conhecê-lo na conferência de ontem, gostei do nosso bate-papo, vamos manter contato';

$labels['compose_help_use_example'] = '(Use isto)';

// summaries
$labels['creating_summary'] = 'Criação de resumo...';
$labels['show_summary'] = 'Mostrar resumo';
$labels['hide_summary'] = 'Ocultar resumo';
$labels['recreate_summary'] = 'Recriar resumo';
$labels['email_has_no_text'] = 'Esse e-mail não tem texto.';

// settings
$labels['setting_compose_style'] = 'Estilo';
$labels['setting_compose_length'] = 'Comprimento';
$labels['setting_compose_language'] = 'Idioma';
$labels['setting_compose_creativity'] = 'Criatividade';
$labels['setting_language'] = 'Idioma';

$labels['summarizing_messages'] = 'Resumindo mensagens';
$labels['setting_show_summary_on_mail_view'] = 'Mostrar resumos de IA ao visualizar e-mails';
$labels['setting_show_summary_on_list_hover'] = 'Mostrar resumos de IA ao passar o mouse sobre a lista de e-mails';
$labels['setting_list_summary_placement'] = 'Posicionamento do pop-up de resumo da lista';

$labels['above'] = 'Acima';
$labels['below'] = 'Abaixo';
$labels['left'] = 'Esquerda';
$labels['right'] = 'Certo';

$labels['ai_disclaimer'] = 'Às vezes, o resultado da IA pode estar incompleto ou incorreto. Sempre verifique novamente as informações essenciais.';

// errors
$labels['api_error'] = 'Ocorreu um erro durante a conexão com o servidor AI. Entre em contato com o administrador.';
