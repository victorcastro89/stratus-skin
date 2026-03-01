<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Yapay Zeka Asistanı';
$labels['ai_assistant'] = 'Yapay Zeka Asistanı';
$labels['ai_assistant_settings'] = 'AI Asistan ayarları';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Yapay Zeka ile Mesaj Oluşturma';

$labels['compose_label_to'] = 'Alıcının adı';
$labels['compose_label_from'] = 'Gönderenin adı';
$labels['compose_label_style'] = 'Stil';
$labels['compose_label_length'] = 'Uzunluk';
$labels['compose_label_language'] = 'Dil';
$labels['compose_label_creativity'] = 'Yaratıcılık';
$labels['compose_label_instructions'] = 'Talimatlar';
$labels['compose_no_change'] = 'Değişiklik yok';
$labels['compose_instructions_placeholder'] = 'Örnek: randevuyu gelecek hafta için değiştirmeyi isteyin';

$labels['compose_style_assertive'] = 'İddialı';
$labels['compose_style_casual'] = 'Gündelik';
$labels['compose_style_enthusiastic'] = 'Hevesli';
$labels['compose_style_funny'] = 'Komik';
$labels['compose_style_informational'] = 'Bilgilendirici';
$labels['compose_style_professional'] = 'Profesyonel';
$labels['compose_style_urgent'] = 'Acil';
$labels['compose_style_witty'] = 'Esprili';

$labels['compose_length_short'] = 'Kısa';
$labels['compose_length_medium'] = 'Orta';
$labels['compose_length_long'] = 'Uzun';

$labels['compose_creativity_low'] = 'Düşük';
$labels['compose_creativity_medium'] = 'Orta';
$labels['compose_creativity_high'] = 'Yüksek';

$labels['compose_insert_email'] = 'E-posta Ekle';
$labels['compose_generate_email'] = 'E-posta Oluştur';
$labels['compose_generate_again'] = 'Tekrar Üret';
$labels['compose_stop_generating'] = 'Üretmeyi Durdur';
$labels['compose_error_help_button'] = 'Yardım';

$labels['compose_no_instructions'] = 'Talimatları yazın.';
$labels['compose_instructions_too_long'] = 'Talimatlarınız çok uzun; en fazla $n karakter olabilirler.';
$labels['compose_empty_response'] = 'Sunucu boş bir yanıt döndürdü.';

$labels['compose_tip_to'] = 'Yeni e-postanın alıcısının adı. Birden fazla ad belirtebilir veya "herkes", "ekip" vb. gibi dizeler kullanabilirsiniz.';
$labels['compose_tip_from'] = 'Yeni e-posta bu adla imzalanacaktır.';
$labels['compose_tip_style'] = 'Yeni e-posta için tercih edilen yazım stili.';
$labels['compose_tip_length'] = 'Yeni e-postanın tercih edilen uzunluğu.';
$labels['compose_tip_language'] = 'Yeni e-postanın hangi dilde yazılması gerektiği. Talimatların verildiği dilde yazmak için "Değişiklik yok" seçeneğini belirtin.';
$labels['compose_tip_creativity'] = 'Daha yüksek yaratıcılık daha öngörülemez ve çeşitli e-postalar üretecektir; daha düşük yaratıcılık ise daha gerçekçi ve isabetli e-postalar üretecektir.';
$labels['compose_tip_instructions'] = 'Yeni e-postaya nelerin ekleneceğine ilişkin talimatlar. Bazı örnekleri görmek için Talimat Örnekleri düğmesine tıklayın.';

$labels['compose_help_button_show'] = 'Talimat Örnekleri';
$labels['compose_help_button_hide'] = 'Geri';
$labels['compose_help_title'] = 'Yardım ve Örnekler';
$labels['compose_help_info'] = 'YZ\'ye yazmak istediğiniz e-posta hakkında bilgi verin. İsimler, tarihler, konumlar, nedenler vb. gibi ayrıntıları ekleyin. Ayrıca yaratıcı fikirler veya açıklamalar da isteyebilirsiniz. E-posta oluşturulduktan sonra üzerinde değişiklik yapabilir ve oluşturma sayfasına ekleyebilirsiniz. İşte başlamanız için birkaç örnek talimat:';

$labels['compose_help_example_1'] = 'merhaba de';
$labels['compose_help_example_2'] = 'bugünkü toplanti i̇ptal edi̇lmi̇şti̇r';
$labels['compose_help_example_3'] = 'Margy ile Çarşamba günü saat 15:00\'te bir toplantı ayarladım ve onu ikna etmek için pazarlama kampanyalarımızda yapay zeka kullanmanın avantajları üzerine bir sunum hazırladım.';
$labels['compose_help_example_4'] = 'Yapay zeka tarafından oluşturulan içerik için destek eklemeye karar verdik, bunun ne olduğunu kısaca açıklayalım';
$labels['compose_help_example_5'] = 'Geçen haftaki ödevin takibi, yarın bana lazım';
$labels['compose_help_example_6'] = 'zam istedim, bir yıldan fazla zam alamadım.';
$labels['compose_help_example_7'] = 'Raporu yarın teslim etmeyi hatırlatın yoksa ekibin başı stresli Margy ile derde girecek';
$labels['compose_help_example_8'] = 'Müşterilerimize Salı gününe kadar tüm ürünlerde %20 indirim hakkında yazın, kıyafet satıyoruz';
$labels['compose_help_example_9'] = 'Laura\'ya yazıp bu hafta sonu Kevin\'in doğum günü için bir buluşma organize etmesini istemek';
$labels['compose_help_example_10'] = 'yeni web sitesi ile ilgili gelişmeleri sorun, ön sayfa için pazarlama sloganları için 5 fikir ekleyin, araba tamiri yapıyoruz';
$labels['compose_help_example_11'] = 'Eklediğimiz yeni vpn özellikleri hakkında haber bülteni: wireguard protokolü, port yönlendirme, kill switch, özel IP; bunların ne olduğunu açıklayın';
$labels['compose_help_example_12'] = 'Yeni bir sadakat programı hakkında haber bülteni, ilk maliyet kart için 10 dolar ve ardından bir yıl boyunca çoğu üründe %20\'ye varan indirimler';
$labels['compose_help_example_13'] = 'Hayran olduğum ve profesyonel olarak bağlantı kurmak istediğim birine ağ oluşturma e-postası';
$labels['compose_help_example_14'] = 'dünkü projedeki yardımları için profesyonel bir teşekkür';
$labels['compose_help_example_15'] = 'Yatırımcılara etkili bir e-posta sunumu hazırlamak için 10 strateji fikri geliştirdi, yarın üzerinde çalışacak';
$labels['compose_help_example_16'] = 'sözleşme uyarınca 1 ay önceden haber veren profesyonel bir istifa e-postası, şirketin adı ACME';
$labels['compose_help_example_17'] = 'Terfi talebinde bulunmak için zorlayıcı e-posta, terfi alamazsam istifa edebileceğimi çünkü başka fırsatlar olduğunu ima etmek';
$labels['compose_help_example_18'] = 'Dünkü konferansta sizinle tanışmak güzeldi, sohbetimizden keyif aldım, iletişimde kalalım';

$labels['compose_help_use_example'] = '(Bunu kullanın)';

// summaries
$labels['creating_summary'] = 'Özet oluşturma...';
$labels['show_summary'] = 'Özet göster';
$labels['hide_summary'] = 'Özeti gizle';
$labels['recreate_summary'] = 'Özeti yeniden oluşturun';
$labels['email_has_no_text'] = 'Bu e-postada metin yok.';

// settings
$labels['setting_compose_style'] = 'Stil';
$labels['setting_compose_length'] = 'Uzunluk';
$labels['setting_compose_language'] = 'Dil';
$labels['setting_compose_creativity'] = 'Yaratıcılık';
$labels['setting_language'] = 'Dil';

$labels['summarizing_messages'] = 'Mesajları Özetleme';
$labels['setting_show_summary_on_mail_view'] = 'E-postaları görüntülerken yapay zeka özetlerini gösterme';
$labels['setting_show_summary_on_list_hover'] = 'E-posta listesinin üzerine gelindiğinde yapay zeka özetlerini gösterme';
$labels['setting_list_summary_placement'] = 'Liste özeti açılır penceresi yerleşimi';

$labels['above'] = 'Yukarıda';
$labels['below'] = 'Aşağıda';
$labels['left'] = 'Sol';
$labels['right'] = 'Doğru.';

$labels['ai_disclaimer'] = 'AI çıktısı bazen eksik veya yanlış olabilir. Temel bilgileri her zaman iki kez kontrol edin.';

// errors
$labels['api_error'] = 'AI sunucusuna bağlanırken bir hata oluştu. Lütfen yöneticiyle iletişime geçin.';
