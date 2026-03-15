<?php
// rcmcarddav preset — auto-discover addressbooks from Davis (CardDAV)

$prefs['_GLOBAL']['loglevel'] = \Psr\Log\LogLevel::DEBUG;
$prefs['_GLOBAL']['loglevel_http'] = \Psr\Log\LogLevel::DEBUG;

$prefs['Davis'] = [
    'accountname'  => 'Davis Contacts',
    'username'     => '%u',
    'password'     => '%p',
    'discovery_url'=> 'http://davis-nginx/dav/',
    'name'         => '%N',
    'active'       => true,
    'readonly'     => false,
    'refresh_time' => '00:05:00',
    'fixed'        => ['discovery_url', 'username', 'password'],
    'hide'         => false,
    'preemptive_basic_auth' => true,
];
