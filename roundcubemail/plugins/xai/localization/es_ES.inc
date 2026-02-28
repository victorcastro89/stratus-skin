<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Asistente AI';
$labels['ai_assistant'] = 'Asistente AI';
$labels['ai_assistant_settings'] = 'Ajustes del Asistente AI';

// compose page
$labels['compose_dialog_button_text'] = 'IA';
$labels['compose_dialog_button_title'] = 'Redactar un mensaje con IA';

$labels['compose_label_to'] = 'Nombre del destinatario';
$labels['compose_label_from'] = 'Nombre del remitente';
$labels['compose_label_style'] = 'Estilo';
$labels['compose_label_length'] = 'Longitud';
$labels['compose_label_language'] = 'Idioma';
$labels['compose_label_creativity'] = 'Creatividad';
$labels['compose_label_instructions'] = 'Instrucciones';
$labels['compose_no_change'] = 'Sin cambios';
$labels['compose_instructions_placeholder'] = 'Ejemplo: pedir cambiar la cita para la semana que viene';

$labels['compose_style_assertive'] = 'Asertivo';
$labels['compose_style_casual'] = 'Informal';
$labels['compose_style_enthusiastic'] = 'Entusiasta';
$labels['compose_style_funny'] = 'Divertido';
$labels['compose_style_informational'] = 'Informativo';
$labels['compose_style_professional'] = 'Profesional';
$labels['compose_style_urgent'] = 'Urgente';
$labels['compose_style_witty'] = 'Ingenioso';

$labels['compose_length_short'] = 'Corto';
$labels['compose_length_medium'] = 'Medio';
$labels['compose_length_long'] = 'Largo';

$labels['compose_creativity_low'] = 'Bajo';
$labels['compose_creativity_medium'] = 'Medio';
$labels['compose_creativity_high'] = 'Alto';

$labels['compose_insert_email'] = 'Insertar correo electrónico';
$labels['compose_generate_email'] = 'Generar correo electrónico';
$labels['compose_generate_again'] = 'Generar de nuevo';
$labels['compose_stop_generating'] = 'Dejar de generar';
$labels['compose_error_help_button'] = 'Ayuda';

$labels['compose_no_instructions'] = 'Escriba las instrucciones.';
$labels['compose_instructions_too_long'] = 'Tus instrucciones son demasiado largas; pueden tener como máximo $n caracteres.';
$labels['compose_empty_response'] = 'El servidor ha devuelto una respuesta vacía.';

$labels['compose_tip_to'] = 'El nombre del destinatario del nuevo correo electrónico. Puede especificar más de un nombre o utilizar cadenas como "todos", "equipo", etc.';
$labels['compose_tip_from'] = 'El nuevo correo electrónico se firmará con este nombre.';
$labels['compose_tip_style'] = 'El estilo de escritura preferido para el nuevo correo electrónico.';
$labels['compose_tip_length'] = 'La longitud preferida del nuevo correo electrónico.';
$labels['compose_tip_language'] = 'El idioma en el que debe escribirse el nuevo correo electrónico. Especifique "Sin cambios" para escribir en el idioma en el que se dan las instrucciones.';
$labels['compose_tip_creativity'] = 'Una mayor creatividad generará correos electrónicos más imprevisibles y diversos; una menor creatividad generará correos electrónicos más objetivos y directos.';
$labels['compose_tip_instructions'] = 'Instrucciones sobre qué incluir en el nuevo correo electrónico. Haga clic en el botón Ejemplos de instrucciones para ver algunos ejemplos.';

$labels['compose_help_button_show'] = 'Ejemplos de instrucciones';
$labels['compose_help_button_hide'] = 'Volver';
$labels['compose_help_title'] = 'Ayuda y ejemplos';
$labels['compose_help_info'] = 'Explícale a la IA el mensaje que te gustaría escribir. Incluye datos concretos, como nombres, fechas, lugares, motivos, etc. También puedes pedirle ideas creativas o explicaciones. Una vez generado el mensaje, podrás modificarlo e insertarlo en la página de redacción. Aquí tienes algunos ejemplos de instrucciones para empezar:';

$labels['compose_help_example_1'] = 'saluda';
$labels['compose_help_example_2'] = 'la reunión de hoy ha sido cancelada';
$labels['compose_help_example_3'] = 'He concertado una reunión con Margy el miércoles a las 3 de la tarde y he preparado una presentación sobre las ventajas de utilizar la IA en nuestras campañas de marketing para convencerla.';
$labels['compose_help_example_4'] = 'hemos decidido añadir soporte para contenidos generados por IA, explique brevemente en qué consiste';
$labels['compose_help_example_5'] = 'seguimiento de la tarea de la semana pasada, lo necesito mañana';
$labels['compose_help_example_6'] = 'pedir un aumento, no me lo dieron por más de un año';
$labels['compose_help_example_7'] = 'recuerde presentar el informe mañana o el equipo tendrá problemas con Margy que está estresada';
$labels['compose_help_example_8'] = 'escribir a los clientes sobre el 20% de descuento en todos los productos hasta el martes, vendemos ropa';
$labels['compose_help_example_9'] = 'pedirle que escriba a Laura y le diga que organice una reunión para el cumpleaños de Kevin este fin de semana.';
$labels['compose_help_example_10'] = 'pregunte sobre el progreso con el nuevo sitio web, incluya 5 ideas para eslóganes de marketing para la portada, hacemos reparaciones de coches';
$labels['compose_help_example_11'] = 'boletín informativo sobre las nuevas funciones de vpn que hemos añadido: protocolo wireguard, reenvío de puertos, kill switch, IP dedicada; explique en qué consisten';
$labels['compose_help_example_12'] = 'boletín informativo sobre un nuevo programa de fidelidad, el coste inicial de la tarjeta es de 10 $ y luego descuentos de hasta el 20% en la mayoría de los productos durante un año.';
$labels['compose_help_example_13'] = 'correo electrónico de networking a alguien a quien admiro y con quien quiero conectar profesionalmente';
$labels['compose_help_example_14'] = 'un profesional gracias por la ayuda con el proyecto de ayer';
$labels['compose_help_example_15'] = 'proponer 10 ideas de estrategias para elaborar una presentación eficaz por correo electrónico a los inversores, trabajará en ello mañana';
$labels['compose_help_example_16'] = 'un correo electrónico de dimisión profesional, dando 1 mes de preaviso según contrato, la empresa se llama ACME';
$labels['compose_help_example_17'] = 'correo electrónico apremiante para solicitar un ascenso, insinuando que podría dimitir si no lo consigo porque hay otras oportunidades';
$labels['compose_help_example_18'] = 'Fue un placer conocerte ayer en la conferencia, disfruté de nuestra charla, sigamos en contacto';

$labels['compose_help_use_example'] = '(Use esto)';

// summaries
$labels['creating_summary'] = 'Crear resumen...';
$labels['show_summary'] = 'Mostrar resumen';
$labels['hide_summary'] = 'Ocultar resumen';
$labels['recreate_summary'] = 'Volver a crear el resumen';
$labels['email_has_no_text'] = 'Este correo electrónico no tiene texto.';

// settings
$labels['setting_compose_style'] = 'Estilo';
$labels['setting_compose_length'] = 'Longitud';
$labels['setting_compose_language'] = 'Idioma';
$labels['setting_compose_creativity'] = 'Creatividad';
$labels['setting_language'] = 'Idioma';

$labels['summarizing_messages'] = 'Resumir mensajes';
$labels['setting_show_summary_on_mail_view'] = 'Mostrar resúmenes de IA al visualizar correos electrónicos';
$labels['setting_show_summary_on_list_hover'] = 'Mostrar resúmenes de IA al pasar el ratón por encima de la lista de correo electrónico';
$labels['setting_list_summary_placement'] = 'Colocación de la ventana emergente de resumen de lista';

$labels['above'] = 'Por encima de';
$labels['below'] = 'Abajo';
$labels['left'] = 'Izquierda';
$labels['right'] = 'Derecha';

$labels['ai_disclaimer'] = 'En ocasiones, los resultados de la IA pueden ser incompletos o incorrectos. Compruebe siempre la información esencial.';

// errors
$labels['api_error'] = 'Se ha producido un error al conectar con el servidor AI. Póngase en contacto con el administrador.';
