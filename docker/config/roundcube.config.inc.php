<?php
/**
 * Roundcube configuration for Docker dev environment (Stratus skin project).
 * This file is copied into roundcubemail/config/config.inc.php by the clone script.
 * It references Docker service names for IMAP/SMTP and host-mounted paths for DB/logs.
 */

$config = [];

// ── Database (SQLite, persisted on host via Docker volume) ─────────────────
$config['db_dsnw'] = 'sqlite:////var/roundcube/db/sqlite.db?mode=0646';

// ── IMAP / SMTP (Docker service "mailserver") ─────────────────────────────
$config['imap_host']    = 'mailserver:143';
$config['smtp_host']    = 'mailserver:587';
$config['smtp_user']    = '%u';
$config['smtp_pass']    = '%p';

// ── General ────────────────────────────────────────────────────────────────
$config['product_name']    = 'Stratus Webmail (Dev)';
$config['des_key']         = 'Gd+4gJ8h29ex932cJI5M9whl';
$config['skin']            = 'stratus';
$config['support_url']     = '';
$config['username_domain']  = '';
$config['request_path']    = '/';
$config['temp_dir']        = '/tmp/roundcube-temp';
$config['log_driver']      = 'file';
$config['log_dir']         = '/var/roundcube/logs/';
$config['log_logins']      = true;

// ── Plugins ────────────────────────────────────────────────────────────────
$config['plugins'] = [
	'archive',
	'stratus_helper',
	'calendar',
    'conversation_mode'

];

// ── Editor / Display ──────────────────────────────────────────────────────
$config['htmleditor']         = 1;
$config['default_list_mode']  = 'threads';
$config['reply_mode']         = 1;
$config['enable_spellcheck']  = true;
$config['spellcheck_engine']  = 'pspell';
$config['zipdownload_selection'] = true;

// ── Archive ────────────────────────────────────────────────────────────────
$config['archive_mbox'] = 'Archive';

// ── Calendar plugin ────────────────────────────────────────────────────────
$config['calendar_driver']          = 'database';
$config['calendar_default_view']    = 'agendaWeek';
$config['calendar_timeslots']       = 2;
$config['calendar_first_day']       = 1;
$config['calendar_first_hour']      = 6;
$config['calendar_work_start']      = 6;
$config['calendar_work_end']        = 18;
$config['calendar_event_coloring']  = 0;
$config['calendar_contact_birthdays'] = false;
$config['calendar_time_indicator']  = true;
$config['calendar_show_weekno']     = 0;
$config['calendar_default_alarm_type']   = '';
$config['calendar_default_alarm_offset'] = '-15M';
$config['calendar_itip_send_option']     = 3;
$config['calendar_itip_after_action']    = 0;
$config['calendar_freebusy_trigger']     = false;
$config['calendar_include_freebusy_data'] = 1;
$config['calendar_agenda_range']    = 60;
$config['calendar_categories'] = [
    'Personal' => 'c0c0c0',
    'Work'     => 'ff0000',
    'Family'   => '00ff00',
    'Holiday'  => 'ff6600',
];
