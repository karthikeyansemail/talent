<?php
/**
 * Bootstrap — include in every portal page as first thing
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : 0);

// Session
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
if (APP_ENV === 'production' && SESSION_SECURE) {
    ini_set('session.cookie_secure', '1');
}
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
