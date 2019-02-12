<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/********************************程序应用配置***************************************/
$redis_conf['default_master']['host'] = '127.0.0.1';
$redis_conf['default_master']['port'] = 6379;
$redis_conf['default_slave']['host'] = '/tmp/redis_pool.sock';

$redis_conf['userinfo']['host'] = '127.0.0.1';
$redis_conf['userinfo']['port'] = 6379;

return $redis_conf;
