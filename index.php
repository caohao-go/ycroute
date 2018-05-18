<?php
/**
 * Index
 * @package        SuperCI
 * @subpackage    Index
 * @category      Index
 * @author        caohao
 */
date_default_timezone_set('Asia/Shanghai');

header('Content-Type: text/html; charset=UTF-8');

ini_set('display_errors', 'Off');
error_reporting(0);

define("APPPATH", realpath(dirname(__FILE__) . '/'));

define("BASEPATH", APPPATH . '/system');
$app = new Yaf_Application(APPPATH . "/conf/application.ini");
$app->bootstrap()->run();

