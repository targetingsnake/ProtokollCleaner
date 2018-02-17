<?php
/**
 * CONFIG FILE ProtocolHelper
 *
 * @author michael g
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.2018
 *
 */
// RENAME THIS FILE TO 'config.php'

// ===== DB SETTINGS =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbname_xxx');
define('DB_USERNAME', 'dbuser___xxx');
define('DB_CHARSET', 'utf8');
define('DB_PASSWORD', 'dbpassword_xxx');
define('TABLE_PREFIX', 'silmph__'); //_S_tura _ILM_enau _P_rotocol _H_elper

// ===== Base Settings =====
define('BASE_TITLE', 'ProtocolHelper');
define('BASE_URL', 'https://refit01.mollybee.de');

define('TIMEZONE', 'Europe/Berlin'); //Mögliche Werte: http://php.net/manual/de/timezones.php
define('TEMPLATE', 'stura');

// ===== SimpleSAML Settings & Konstants
define('SIMPLESAML_ACCESS_GROUP', 'stura');

// ===== Security Settings =====
define('PW_PEPPER', 'XXXXX_PLEASECHANGE_TO_CRYPTIC_LETTERS_a-zA-Z0-9_MIN_LENGTH_32_XXXXX'); //TODO REMOVE not needed anymore
define('RENAME_FILES_ON_UPLOAD', 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm'); //TODO remove

// ===== DO NOT CHANGE THIS =====
require_once (dirname(__FILE__)."/framework/init.php");
// end of file -------------
