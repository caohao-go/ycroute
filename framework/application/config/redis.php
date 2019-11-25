<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/********************************程序应用配置***************************************/
$redis_conf['default']['master']['host'] = '127.0.0.1';
$redis_conf['default']['master']['port'] = 6379;
$redis_conf['default']['slave'][0]['host'] = '/tmp/redis_pool.sock';

$redis_conf['userinfo']['master']['host'] = '127.0.0.1';
$redis_conf['userinfo']['master']['port'] = 6379;
$redis_conf['userinfo']['master']['auth'] = 'password';

return $redis_conf;
