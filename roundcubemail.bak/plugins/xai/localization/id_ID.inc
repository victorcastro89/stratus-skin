<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

$labels = [];
$labels['app_menu_title'] = 'Asisten AI';
$labels['ai_assistant'] = 'Asisten AI';
$labels['ai_assistant_settings'] = 'Pengaturan Asisten AI';

// compose page
$labels['compose_dialog_button_text'] = 'AI';
$labels['compose_dialog_button_title'] = 'Menulis Pesan dengan AI';

$labels['compose_label_to'] = 'Nama penerima';
$labels['compose_label_from'] = 'Nama pengirim';
$labels['compose_label_style'] = 'Gaya';
$labels['compose_label_length'] = 'Panjang';
$labels['compose_label_language'] = 'Bahasa';
$labels['compose_label_creativity'] = 'Kreativitas';
$labels['compose_label_instructions'] = 'Petunjuk';
$labels['compose_no_change'] = 'Tidak ada perubahan';
$labels['compose_instructions_placeholder'] = 'Contoh: meminta untuk mengubah janji temu untuk minggu depan';

$labels['compose_style_assertive'] = 'Asertif';
$labels['compose_style_casual'] = 'Santai';
$labels['compose_style_enthusiastic'] = 'Antusias';
$labels['compose_style_funny'] = 'Lucu.';
$labels['compose_style_informational'] = 'Informasi';
$labels['compose_style_professional'] = 'Profesional';
$labels['compose_style_urgent'] = 'Mendesak';
$labels['compose_style_witty'] = 'Witty';

$labels['compose_length_short'] = 'Pendek';
$labels['compose_length_medium'] = 'Sedang';
$labels['compose_length_long'] = 'Panjang';

$labels['compose_creativity_low'] = 'Rendah';
$labels['compose_creativity_medium'] = 'Sedang';
$labels['compose_creativity_high'] = 'Tinggi';

$labels['compose_insert_email'] = 'Masukkan Email';
$labels['compose_generate_email'] = 'Hasilkan Email';
$labels['compose_generate_again'] = 'Hasilkan Lagi';
$labels['compose_stop_generating'] = 'Berhenti Menghasilkan';
$labels['compose_error_help_button'] = 'Bantuan';

$labels['compose_no_instructions'] = 'Ketik instruksi.';
$labels['compose_instructions_too_long'] = 'Instruksi Anda terlalu panjang; paling banyak terdiri dari $n karakter.';
$labels['compose_empty_response'] = 'Server mengembalikan respons kosong.';

$labels['compose_tip_to'] = 'Nama penerima email baru. Anda dapat menentukan lebih dari satu nama, atau menggunakan string seperti "semua orang", "tim", dll.';
$labels['compose_tip_from'] = 'Email baru akan ditandatangani dengan nama ini.';
$labels['compose_tip_style'] = 'Gaya penulisan yang disukai untuk email baru.';
$labels['compose_tip_length'] = 'Panjang email baru yang diinginkan.';
$labels['compose_tip_language'] = 'Bahasa yang akan digunakan untuk menulis email baru. Tentukan "Tidak ada perubahan" untuk menulis dalam bahasa yang digunakan untuk memberikan instruksi.';
$labels['compose_tip_creativity'] = 'Kreativitas yang lebih tinggi akan menghasilkan email yang lebih tak terduga dan beragam; kreativitas yang lebih rendah akan menghasilkan email yang lebih faktual dan langsung pada intinya.';
$labels['compose_tip_instructions'] = 'Instruksi tentang apa yang harus disertakan dalam email baru. Klik tombol Contoh Instruksi untuk melihat beberapa contoh.';

$labels['compose_help_button_show'] = 'Contoh Instruksi';
$labels['compose_help_button_hide'] = 'Kembali';
$labels['compose_help_title'] = 'Bantuan & Contoh';
$labels['compose_help_info'] = 'Beri tahu AI tentang email yang ingin Anda tulis. Sertakan informasi spesifik, seperti nama, tanggal, lokasi, alasan, dll. Anda juga dapat meminta ide atau penjelasan kreatif. Setelah email dibuat, Anda dapat mengubahnya dan memasukkannya ke halaman penulisan. Berikut adalah beberapa contoh instruksi untuk membantu Anda memulai:';

$labels['compose_help_example_1'] = 'menyapa';
$labels['compose_help_example_2'] = 'pertemuan hari ini telah dibatalkan';
$labels['compose_help_example_3'] = 'Saya mengatur pertemuan dengan Margy pada hari Rabu jam 3 sore, menyiapkan presentasi tentang keuntungan menggunakan AI dalam kampanye pemasaran kami untuk meyakinkannya';
$labels['compose_help_example_4'] = 'kami memutuskan untuk menambahkan dukungan untuk konten yang dihasilkan AI, jelaskan secara singkat apa itu';
$labels['compose_help_example_5'] = 'menindaklanjuti tugas dari minggu lalu, saya membutuhkannya besok';
$labels['compose_help_example_6'] = 'meminta kenaikan gaji, saya tidak mendapatkannya selama lebih dari setahun';
$labels['compose_help_example_7'] = 'mengingatkan untuk menyerahkan laporan besok atau tim akan mendapat masalah dengan Margy yang sedang stres';
$labels['compose_help_example_8'] = 'tulis pelanggan tentang diskon 20% untuk semua produk hingga Selasa, kami menjual pakaian';
$labels['compose_help_example_9'] = 'meminta untuk menyurati Laura dan menyuruhnya mengatur acara kumpul-kumpul untuk ulang tahun Kevin akhir pekan ini';
$labels['compose_help_example_10'] = 'bertanya tentang kemajuan dengan situs web baru, termasuk 5 ide untuk slogan pemasaran untuk halaman depan, kami melakukan perbaikan mobil';
$labels['compose_help_example_11'] = 'buletin tentang fitur vpn baru yang kami tambahkan: protokol wireguard, port forwarding, kill switch, IP khusus; jelaskan apa itu';
$labels['compose_help_example_12'] = 'buletin tentang program loyalitas baru, biaya awal sebesar $10 untuk kartu dan kemudian diskon hingga 20% untuk sebagian besar produk selama setahun';
$labels['compose_help_example_13'] = 'email jaringan ke seseorang yang saya kagumi dan ingin terhubung secara profesional';
$labels['compose_help_example_14'] = 'terima kasih yang profesional atas bantuannya dalam proyek kemarin';
$labels['compose_help_example_15'] = 'memberikan 10 ide strategi untuk membuat email pitching yang efektif kepada para investor, dia akan mengerjakannya besok';
$labels['compose_help_example_16'] = 'email pengunduran diri yang profesional, memberikan pemberitahuan 1 bulan sesuai kontrak, perusahaan disebut ACME';
$labels['compose_help_example_17'] = 'email yang memaksa untuk meminta promosi, mengisyaratkan bahwa saya mungkin akan mengundurkan diri jika saya tidak mendapatkannya karena ada peluang lain';
$labels['compose_help_example_18'] = 'senang bertemu dengan Anda di konferensi kemarin, saya menikmati obrolan kita, mari kita tetap berhubungan';

$labels['compose_help_use_example'] = '(Gunakan ini)';

// summaries
$labels['creating_summary'] = 'Membuat ringkasan...';
$labels['show_summary'] = 'Tampilkan ringkasan';
$labels['hide_summary'] = 'Sembunyikan ringkasan';
$labels['recreate_summary'] = 'Buat ulang ringkasan';
$labels['email_has_no_text'] = 'Email ini tidak memiliki teks.';

// settings
$labels['setting_compose_style'] = 'Gaya';
$labels['setting_compose_length'] = 'Panjang';
$labels['setting_compose_language'] = 'Bahasa';
$labels['setting_compose_creativity'] = 'Kreativitas';
$labels['setting_language'] = 'Bahasa';

$labels['summarizing_messages'] = 'Meringkas Pesan';
$labels['setting_show_summary_on_mail_view'] = 'Menampilkan ringkasan AI saat melihat email';
$labels['setting_show_summary_on_list_hover'] = 'Menampilkan ringkasan AI saat mengarahkan kursor ke daftar email';
$labels['setting_list_summary_placement'] = 'Penempatan popup ringkasan daftar';

$labels['above'] = 'Di atas';
$labels['below'] = 'Di bawah';
$labels['left'] = 'Kiri';
$labels['right'] = 'Benar.';

$labels['ai_disclaimer'] = 'Keluaran AI terkadang tidak lengkap atau salah. Selalu periksa ulang informasi penting.';

// errors
$labels['api_error'] = 'Terjadi kesalahan saat menyambung ke server AI. Silakan hubungi administrator.';
