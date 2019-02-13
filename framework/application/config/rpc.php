<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| RPC调用远程服务 Model 配置
| -------------------------------------------------------------------
|	['url'] The url of remote server
|	['packager'] type of RPC package, can be  "json", "msgpack", "php", msgpack need install php extension
|	['persitent'] TRUE/FALSE - Whether to use a persistent connection， service need support keepalive
|	['connect_timeout'] timeout of connect to rpc server
|   ['timeout'] timeout of call rpc function
|	['debug'] TRUE/FALSE - Whether rpc call errors should be displayed.
*/

$remote_config['UserinfoModel']['url'] = "http://tr.gaoqu.site/index.php?c=rpcserver&m=userinfoModel&rpc=true";
$remote_config['UserinfoModel']['packager'] = FALSE;         //RPC包类型，FALSE则选择默认，可以为 "json", "msgpack", "php",  msgpack 需要安装扩展
$remote_config['UserinfoModel']['persitent'] = FALSE;        //是否长链接，需要服务端支持keepalive
$remote_config['UserinfoModel']['connect_timeout'] = 1000;   //连接超时(毫秒)，默认 1秒 
$remote_config['UserinfoModel']['timeout'] = 5000;           //调用超时(毫秒)， 默认 5 秒
$remote_config['UserinfoModel']['debug'] = TRUE;             //DEBUG模式，调用异常是否会打印到屏幕

return $remote_config;
