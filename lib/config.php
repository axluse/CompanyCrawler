<?php

/**
 * 共通設定
 */

// エラーレポート設定
error_reporting(0);

// DSN設定
define('DSN', 'mysqlt://root@localhost/public_companys_db');

// Debug
define('DEBUG_MODE', 'true');

// タイムアウト設定
set_time_limit(0);

// charset
define('CHAR_SET', 'SET NAMES utf8');

?>