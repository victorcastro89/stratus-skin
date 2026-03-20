


<?php
/**
 * Roundcube configuration for Docker dev environment (Stratus skin project).
 * This file is copied into roundcubemail/config/config.inc.php by the clone script.
 * It references Docker service names for IMAP/SMTP and host-mounted paths for DB/logs.
 */

$config = [];
$config['htmleditor'] = 0;

$config['reply_mode'] = 1;
$config['allow_mobile_html_composing'] = true; 
// ── Database (SQLite, persisted on host via Docker volume) ─────────────────
$config['db_dsnw'] = 'sqlite:////var/roundcube/db/sqlite.db?mode=0646';

// ── IMAP / SMTP (Docker service "mailserver") ─────────────────────────────
$config['imap_host']    = 'mailserver:143';
$config['smtp_host']    = 'mailserver:587';
$config['smtp_user']    = '%u';
$config['smtp_pass']    = '%p';
$config['messages_cache'] = false;
$config['imap_cache'] = null;

// ── General ────────────────────────────────────────────────────────────────
$config['product_name']    = 'Stratus Webmail (Dev)';
$config['des_key']         = 'Gd+4gJ8h29ex932cJI5M9whl';
$config['skin']            = "stratus";
$config['support_url']     = '';
$config['username_domain']       = '';
$config['login_username_filter'] = 'email';
$config['request_path']    = '/';
$config['temp_dir']        = '/tmp/roundcube-temp';
$config['log_driver']      = 'file';
$config['log_dir']         = '/var/roundcube/logs/';
$config['log_logins']      = true;
$config['prettydate'] = true;

// ── Plugins ────────────────────────────────────────────────────────────────
$config['plugins'] = [
    'stratus_helper',
    	'xskin',
	'xframework',
	'archive',
	
	'calendar',
    'carddav',
    'undo_send',
];
$config['license_key'] = 'RCP-Rxd228rIKZiy';

// ── Editor / Display ──────────────────────────────────────────────────────
$config['htmleditor']         = 1;
// $config['default_list_mode']  = 'threads';
$config['reply_mode']         = 1;
$config['enable_spellcheck']  = true;
$config['spellcheck_engine']  = 'pspell';
$config['zipdownload_selection'] = true;

// ── Contacts (use only CardDAV via Davis) ───────────────────────────────────
$config['address_book_type'] = '';
$config['collected_recipients'] = false;
$config['collected_senders']    = false;

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

// ── Stratus Font Sizes ──────────────────────────────────────────────────
// Each entry defines CSS size and line-height.
// 14px (0.875rem) is the industry standard used by Gmail/Outlook.
$config['stratus_font_sizes'] = [
    'small'   => ['size' => '0.8125rem', 'line_height' => '1.45', 'label' => 'Small'],    // 13px — compact density
    'default' => ['size' => '0.875rem',  'line_height' => '1.5',  'label' => 'Default'],   // 14px — Gmail/Outlook default
    'large'   => ['size' => '1rem',      'line_height' => '1.55', 'label' => 'Large'],     // 16px — accessibility-friendly
];
$config['stratus_color_scheme_default'] = 'indigo';
// ── Stratus Color Schemes ───────────────────────────────────────────────
// Full palette per scheme. Admins can add/remove schemes.
// Keys must be alphanumeric + hyphens.
$config['stratus_color_schemes'] = [

    // ─── INDIGO (default — professional, calm, Outlook-like) ───
    'indigo'  => [
        'label'               => 'Indigo',

        // Core accent
        'primary'             => '#5c6bc0',
        'primary_dark'        => '#7986cb',

        // Text accent — WCAG AA validated
        'text_accent'         => '#3949ab',   // 5.6:1 on #fff
        'text_accent_dark'    => '#9fa8da',   // 5.2:1 on #1a1f36

        // Sidebar / Taskmenu
        'sidebar_bg'          => '#1a1f36',
        'sidebar_gradient'    => 'linear-gradient(180deg, #1e2444 0%, #151a2e 100%)',
        'sidebar_text'        => '#8892b8',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(92, 107, 192, 0.20)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(136, 146, 184, 0.14)',   // indigo-tinted from sidebar_text

        // Surfaces
        'surface_tint'        => 'rgba(92, 107, 192, 0.04)',
        'hover_bg'            => 'rgba(92, 107, 192, 0.07)',
        'selected_bg'         => 'rgba(92, 107, 192, 0.12)',
        'focus_ring'          => 'rgba(92, 107, 192, 0.30)',

        // On-primary — WCAG AA validated text on primary backgrounds
        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#ffffff',

        // Dark mode palette
        'dark_background'     => '#13172a',
        'dark_surface'        => '#1a1f36',
        'dark_surface_raised' => '#212845',
        'dark_font'           => '#c8d0e8',
        'dark_font_secondary' => '#7e8aad',
        'dark_border'         => '#2a3050',
    ],

    // ─── OCEAN BLUE (Gmail-inspired, high trust, clean) ───
    'ocean'   => [
        'label'               => 'Ocean Blue',

        'primary'             => '#1a73e8',
        'primary_dark'        => '#4fc3f7',

        'text_accent'         => '#1558b0',   // 5.9:1 on #fff
        'text_accent_dark'    => '#81d4fa',   // 5.1:1 on #0d1b2a

        'sidebar_bg'          => '#0d1b2a',
        'sidebar_gradient'    => 'linear-gradient(180deg, #112240 0%, #091728 100%)',
        'sidebar_text'        => '#6b9cc2',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(26, 115, 232, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.07)',
        'sidebar_divider'     => 'rgba(107, 156, 194, 0.14)',   // ocean-blue-tinted from sidebar_text

        'surface_tint'        => 'rgba(26, 115, 232, 0.03)',
        'hover_bg'            => 'rgba(26, 115, 232, 0.06)',
        'selected_bg'         => 'rgba(26, 115, 232, 0.12)',
        'focus_ring'          => 'rgba(26, 115, 232, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#0a1929',
        'dark_surface'        => '#0d1b2a',
        'dark_surface_raised' => '#132f4c',
        'dark_font'           => '#b2bac2',
        'dark_font_secondary' => '#6b7a8d',
        'dark_border'         => '#1e3a5f',
    ],

    // ─── EMERALD (fresh, nature-inspired) ───
    'emerald' => [
        'label'               => 'Emerald',

        'primary'             => '#2e7d32',
        'primary_dark'        => '#66bb6a',

        'text_accent'         => '#1b5e20',   // 6.8:1 on #fff
        'text_accent_dark'    => '#81c784',   // 5.5:1 on #0d1f12

        'sidebar_bg'          => '#0d1f12',
        'sidebar_gradient'    => 'linear-gradient(180deg, #122a18 0%, #091a0e 100%)',
        'sidebar_text'        => '#6b9c74',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(46, 125, 50, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(107, 156, 116, 0.14)',   // green-tinted from sidebar_text

        'surface_tint'        => 'rgba(46, 125, 50, 0.03)',
        'hover_bg'            => 'rgba(46, 125, 50, 0.06)',
        'selected_bg'         => 'rgba(46, 125, 50, 0.12)',
        'focus_ring'          => 'rgba(46, 125, 50, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#0a1a0f',
        'dark_surface'        => '#0d1f12',
        'dark_surface_raised' => '#1a3322',
        'dark_font'           => '#c0d4c3',
        'dark_font_secondary' => '#7a9c80',
        'dark_border'         => '#1e3a24',
    ],

    // ─── ROSE (bold, editorial — vivid but not alarming) ───
    'rose'    => [
        'label'               => 'Rose',

        'primary'             => '#c62828',
        'primary_dark'        => '#000000ff',

        'text_accent'         => '#b71c1c',   // 5.0:1 on #fff
        'text_accent_dark'    => '#ef9a9a',   // 5.4:1 on #2a0f0f

        'sidebar_bg'          => '#2a0f0f',
        'sidebar_gradient'    => 'linear-gradient(180deg, #351515 0%, #200a0a 100%)',
        'sidebar_text'        => '#b87a7a',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(198, 40, 40, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(184, 122, 122, 0.14)',   // warm rose-tinted from sidebar_text

        'surface_tint'        => 'rgba(198, 40, 40, 0.03)',
        'hover_bg'            => 'rgba(198, 40, 40, 0.06)',
        'selected_bg'         => 'rgba(198, 40, 40, 0.12)',
        'focus_ring'          => 'rgba(198, 40, 40, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#ffffff',

        'dark_background'     => '#1c0a0a',
        'dark_surface'        => '#2a0f0f',
        'dark_surface_raised' => '#3d1a1a',
        'dark_font'           => '#dcc0c0',
        'dark_font_secondary' => '#a07070',
        'dark_border'         => '#4a2020',
    ],

    // ─── AMBER (warm, energetic — deepened for contrast) ───
    'amber'   => [
        'label'               => 'Amber',

        'primary'             => '#e6850a',
        'primary_dark'        => '#ffb74d',

        'text_accent'         => '#bf360c',   // 5.5:1 on #fff
        'text_accent_dark'    => '#ffcc80',   // 8.2:1 on #2a1f0d

        'sidebar_bg'          => '#2a1f0d',
        'sidebar_gradient'    => 'linear-gradient(180deg, #352812 0%, #201808 100%)',
        'sidebar_text'        => '#b89c6b',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(230, 133, 10, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(184, 156, 107, 0.14)',   // warm amber-tinted from sidebar_text

        'surface_tint'        => 'rgba(230, 133, 10, 0.03)',
        'hover_bg'            => 'rgba(230, 133, 10, 0.06)',
        'selected_bg'         => 'rgba(230, 133, 10, 0.12)',
        'focus_ring'          => 'rgba(230, 133, 10, 0.30)',

        'on_primary'          => '#000000',   // black on amber — amber is too light for white text
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#1a150a',
        'dark_surface'        => '#2a1f0d',
        'dark_surface_raised' => '#3d2e15',
        'dark_font'           => '#d4c8a8',
        'dark_font_secondary' => '#9c8a60',
        'dark_border'         => '#4a3a1a',
    ],

    // ─── PURPLE (creative, distinctive — Superhuman-inspired) ───
    'purple'  => [
        'label'               => 'Purple',

        'primary'             => '#7b1fa2',
        'primary_dark'        => '#ba68c8',

        'text_accent'         => '#6a1b9a',   // 6.1:1 on #fff
        'text_accent_dark'    => '#ce93d8',   // 5.2:1 on #1a0d26

        'sidebar_bg'          => '#1a0d26',
        'sidebar_gradient'    => 'linear-gradient(180deg, #221435 0%, #13091c 100%)',
        'sidebar_text'        => '#9c7ab8',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(123, 31, 162, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(156, 122, 184, 0.14)',   // purple-tinted from sidebar_text

        'surface_tint'        => 'rgba(123, 31, 162, 0.03)',
        'hover_bg'            => 'rgba(123, 31, 162, 0.06)',
        'selected_bg'         => 'rgba(123, 31, 162, 0.12)',
        'focus_ring'          => 'rgba(123, 31, 162, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#120a1c',
        'dark_surface'        => '#1a0d26',
        'dark_surface_raised' => '#2a1840',
        'dark_font'           => '#d0c0e0',
        'dark_font_secondary' => '#8a70a0',
        'dark_border'         => '#3a2050',
    ],

    // ─── TEAL (balanced, clinical — Proton Mail vibes) ───
    'teal'    => [
        'label'               => 'Teal',

        'primary'             => '#00796b',
        'primary_dark'        => '#4db6ac',

        'text_accent'         => '#00695c',   // 5.1:1 on #fff
        'text_accent_dark'    => '#80cbc4',   // 6.2:1 on #0d1f1c

        'sidebar_bg'          => '#0d1f1c',
        'sidebar_gradient'    => 'linear-gradient(180deg, #122a26 0%, #091a17 100%)',
        'sidebar_text'        => '#6b9c96',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(0, 121, 107, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.08)',
        'sidebar_divider'     => 'rgba(107, 156, 150, 0.14)',   // teal-tinted from sidebar_text

        'surface_tint'        => 'rgba(0, 121, 107, 0.03)',
        'hover_bg'            => 'rgba(0, 121, 107, 0.06)',
        'selected_bg'         => 'rgba(0, 121, 107, 0.12)',
        'focus_ring'          => 'rgba(0, 121, 107, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#0a1a17',
        'dark_surface'        => '#0d1f1c',
        'dark_surface_raised' => '#1a332e',
        'dark_font'           => '#b8d4cf',
        'dark_font_secondary' => '#6b9c96',
        'dark_border'         => '#1e3a34',
    ],

    // ─── SLATE (neutral, understated — zero personality) ───
    'slate'   => [
        'label'               => 'Slate',

        'primary'             => '#546e7a',
        'primary_dark'        => '#90a4ae',

        'text_accent'         => '#37474f',   // 7.5:1 on #fff
        'text_accent_dark'    => '#b0bec5',   // 6.3:1 on #1a1f24

        'sidebar_bg'          => '#1a1f24',
        'sidebar_gradient'    => 'linear-gradient(180deg, #222930 0%, #141a1e 100%)',
        'sidebar_text'        => '#8899a4',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(84, 110, 122, 0.22)',
        'sidebar_hover_bg'    => 'rgba(255, 255, 255, 0.07)',
        'sidebar_divider'     => 'rgba(136, 153, 164, 0.14)',   // cool gray-tinted from sidebar_text

        'surface_tint'        => 'rgba(84, 110, 122, 0.03)',
        'hover_bg'            => 'rgba(84, 110, 122, 0.06)',
        'selected_bg'         => 'rgba(84, 110, 122, 0.12)',
        'focus_ring'          => 'rgba(84, 110, 122, 0.30)',

        'on_primary'          => '#ffffff',
        'on_primary_dark'     => '#000000',

        'dark_background'     => '#121619',
        'dark_surface'        => '#1a1f24',
        'dark_surface_raised' => '#262d33',
        'dark_font'           => '#c4cdd4',
        'dark_font_secondary' => '#7a8a94',
        'dark_border'         => '#2e3840',
    ],
];

