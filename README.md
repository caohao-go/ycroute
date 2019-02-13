SuperCI
===

## 目录
- 框架介绍
- 运行环境
- 代码结构
- 路由配置
- 过滤验签
- 控制层
- 加载器
- 模型层
- 数据交互dao层(可选)
- Redis缓存操作
- 数据库操作
- 配置加载
- 公共类加载
- 公共函数
- 日志模块
- 视图层
- 附录 - Core_Model 中的辅助极速开发函数
- RPC 框架介绍
- RPC Server
- RPC Client
- RPC 并发调用

## 框架介绍
框架由3层架构构成，Controller、Model、View 以及1个可选的Dao层，支持PHP7，优点如下：

1、框架层次分明，灵活可扩展至4层架构、使用简洁（开箱即用）、功能强大。

2、基于 yaf 路由和 ycdatabase 框架，两者都是C语言扩展，保证了性能。

3、ycdatabase 是强大的数据库 ORM 框架，功能强大，安全可靠，支持便捷的主从配置，支持稳定、强大的数据库连接池。具体参考 https://blog.csdn.net/caohao0591/article/details/85255704

4、支持Redis代理，简便的主从配置，支持稳定的redis连接池。具体参考：https://blog.csdn.net/caohao0591/article/details/85679702

5、强大的日志模块、异常捕获模块，便捷高效的类库、共用函数加载模块

6、基于PHP7，代码缓存opcache。

## 运行环境

运行环境： PHP 7 

依赖扩展： yaf 、  ycdatabase 扩展 

创建日志目录：/data/app/logs ，目录权限为 php 项目可写。 

yaf 介绍以及安装： https://github.com/laruence/yaf

ycdatabase 介绍以及安装： https://github.com/caohao-php/ycdatabase

## 代码结构
```php
———————————————— 
|--- system                   //框架系统代码
|--- conf                     //yaf配置路径 
|--- application              //业务代码 
         |----- config        //配置目录
         |----- controller    //控制器目录
                |------ User.php    //User控制器
         |----- core          //框架基类目录
	 |----- daos          //DAO层目录(可选)
         |----- errors        //错误页目录
         |----- helpers       //公共函数目录
         |----- library       //公共类库目录
         |----- models        //模型层目录
         |----- plugins       //yaf路由插件目录，路由前后钩子，(接口验签在这里)
         |----- third         //第三方类库
         |----- views         //视图层
```
	 
## 路由配置
路由配置位于： framework/conf/application.ini<br>
示例： http://localhost/index.php?c=user&m=getUserInfo&userid=6842811&token=c9bea5dee1f49488e2b4b4645ff3717e<br>
详细参考文档： http://php.net/manual/zh/book.yaf.php

##### 控制器由参数c决定，动作有 m 决定。
|参数|方式|描述| 
|------|---|----|
|c|GET|控制器，路由到 /application/controller/User.php 文件|
|m|GET|入口方法， User.php 里面的 getUserInfoAction 方法|

程序将被路由到 framework/application/controllers/User.php文件的 UserController::getUserInfoAction方法，其它路由细节参考Yaf框架
```php
class UserController extends Core_Controller  
{  
    public function getUserInfoAction()  
    {  
    }  
}  
```

## 过滤验签
framework/application/plugins/Filter.php  ，  在 _auth 中写入验签方法，所有接口都会在这里校验， 所有GET、POST等参数放在 $this->params 里。
```php
class FilterPlugin extends Yaf_Plugin_Abstract {
    var $params;

    //路由之前调用
    public function routerStartUp ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response) {
        $this->params = & $request->getParams();
       
       	$this->_auth();
    }
    
    
    //验签过程
    protected function _auth()
    {
        //在这里写你的验签逻辑
    }
    ...
}
```

## 控制层

所有控制器位于：framework/application/controllers 目录，所有控制器继承自Core_Controller方法，里面主要获取GET/POST参数，以及返回数据的处理，Core_Controller继承自 Yaf_Controller_Abstract， init方法会被自动调用，更多细节参考 Yaf 框架控制器。
```php
class UserController extends Core_Controller {
    public function init() {
        parent::init(); //必须

        $this->user_model = Loader::model('UserinfoModel'); //模型层

        $this->util_log = Logger::get_instance('user_log'); //日志
        Loader::helper('common_helper'); //公共函数

        $this->sample = Loader::library('Sample'); //加载类库，加载的就是 framework/library/Sample.php 里的Sample类
    }

    //获取用户信息接口
    public function getUserInfoAction() {
        $userId = $this->params['userid'];
        $token = $this->params['token'];

        if (empty($userId)) {
            $this->response_error(10000017, "user_id is empty");
        }

        if (empty($token)) {
            $this->response_error(10000016, "token is empty");
        }

        $userInfo = $this->user_model->getUserinfoByUserid($userId);
        if (empty($userInfo)) {
            $this->response_error(10000023, "未找到该用户");
        }

        if (empty($token) || $token != $userInfo['token']) {
            $this->response_error(10000024, "token 校验失败");
        }
        
        $this->response_success($userInfo);
    }
}
```

通过 $this->response_error(10000017, 'user_id is empty'); 返回错误结果 <br>
```json
{
    "errno":10000017,
    "errmsg":"user_id is empty"
}
```
通过 $this->response_success($result);  返回JSON格式成功结果，格式如下： 
```json
{
    "errno":0,
    "union":"",
    "amount":0,
    "session_key":"ZqwsC+Spy4C31ThvqkhOPg==",
    "open_id":"oXtwn4_mrS4zIxtSeV0yVT2sAuRo",
    "nickname":"凉之渡",
    "last_login_time":"2018-09-04 18:53:06",
    "regist_time":"2018-06-29 22:03:38",
    "user_id":6842811,
    "token":"c9bea5dee1f49488e2b4b4645ff3717e",
    "updatetime":"2018-09-04 18:53:06",
    "avatar_url":"https://wx.qlogo.cn/mmopen/vi_32/xfxHib91BictV8T4ibRQAibD10DfoNpzpB1LBqZvRrz0icPkN0gdibZg62EPJL3KE1Y5wkPDRAhibibymnQCFgBM2nuiavA/132",
    "city":"Guangzhou",
    "province":"Guangdong",
    "country":"China",
    "appid":"wx385863ba15f573b6",
    "gender":1,
    "form_id":""
}
```

## 加载器
通过 Loader 加载器可以加载模型层，公共类库，公共函数，数据库，缓存等对象， Logger 为日志类。

## 模型层
framework/application/models/Userinfo.php ，模型层，你可以继承自Core_Model， 也可以不用，Core_Model 中封装了许多常用SQL操作。最后一章会介绍各个函数用法。

通过 $this->user_model = Loader::model('UserinfoModel') 加载模型层，模型层与数据库打交道。
```php
class UserinfoModel extends Core_Model {
    public function __construct() {
        $this->db = Loader::database('default');
        $this->util_log = Logger::get_instance('userinfo_log');
    }

    function register_user($appid, $userid, $open_id, $session_key) {
        $data = array();
        $data['appid'] = $appid;
        $data['user_id'] = $userid;
        $data['open_id'] = $open_id;
        $data['session_key'] = $session_key;
        $data['last_login_time'] = $data['regist_time'] = date('Y-m-d H:i:s', time());
        $data['token'] = md5(TOKEN_GENERATE_KEY . time() . $userid . $session_key);
        $ret = $this->db->insert("user_info", $data);
        if ($ret != -1) {
            return $data['token'];
        } else {
            $this->util_log->LogError("error to register_user, DATA=[".json_encode($data)."]");
            return false;
        }
    }
    
    ...
}
```

## 数据交互Dao层(可选)
如果你习惯了4层结构，你可以加载Dao层，作为与数据库交互的层，而model层作为业务层。这个时候 Model 最好不要继承 Core_Model，而由 Dao 层来继承。


framework/application/daos/UserinfoDao.php ，数据库交互层，你可以继承自Core_Model， 也可以不用，Core_Model 中封装了许多常用SQL操作。最后一章会介绍各个函数用法。


通过 $this->user_dao = Loader::dao('UserinfoDao') 加载dao层，我们建议一个数据库对应一个Dao层。


## redis 缓存操作
加载 redis 缓存： Loader::redis('default_master');  参数为framework/application/config/redis.php 配置键值，如下：
```php
$redis_conf['default_master']['host'] = '127.0.0.1';
$redis_conf['default_master']['port'] = 6379;
$redis_conf['default_slave']['host'] = '/tmp/redis_pool.sock';  //unix socket redis连接池，需要配置 openresty-pool/conf/nginx.conf，并开启代理，具体参考 https://blog.csdn.net/caohao0591/article/details/85679702

$redis_conf['userinfo']['host'] = '127.0.0.1';
$redis_conf['userinfo']['port'] = 6379;

return $redis_conf;
```

使用例子：
```php
$redis = Loader::redis("default_master"); //主写
$redis->set("pre_redis_user_${userid}", serialize($result));
$redis->expire("pre_redis_user_${userid}", 3600);

$redis = Loader::redis("default_slave"); //从读
$data = $redis->get("pre_redis_user_${userid}");
```

连接池配置 openresty-pool/conf/nginx.conf ：<br>
```lua
worker_processes  1;        #nginx worker 数量

error_log logs/error.log;   #指定错误日志文件路径

events {
    worker_connections 1024;
}

stream {
    lua_code_cache on;

    lua_check_client_abort on;

    server {
    	listen unix:/tmp/redis_pool.sock;
    	content_by_lua_block {
    		local redis_pool = require "redis_pool"
    		pool = redis_pool:new({ip = "127.0.0.1", port = 6380, auth = "password"})
    		pool:run()
    	}
    }
	
    server {

    	listen unix:/var/run/mysql_sock/mysql_user_pool.sock;
		
    	content_by_lua_block {
    		local mysql_pool = require "mysql_pool"
			
    		local config = {host = "127.0.0.1", 
    				user = "root", 
    				password = "test123123",
    				database = "userinfo", 
    				timeout = 2000, 
    				max_idle_timeout = 10000, 
    				pool_size = 200}
						   
    		pool = mysql_pool:new(config)
			
    		pool:run()
    	}
    }
}
```

## 数据库操作
数据库加载：  Loader::database("default");   参数为framework/application/config/database.php 配置，如下：
```php
$db['default']['unix_socket'] = '/var/run/mysql_sock/mysql_user_pool.sock';  //unix socket 数据库连接池，具体使用参考 https://blog.csdn.net/caohao0591/article/details/85255704
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = TRUE;
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['autoinit'] = FALSE;

$db['payinfo_master']['host']     = '127.0.0.1';   //地址
$db['payinfo_master']['username'] = 'root';        //用户名
$db['payinfo_master']['password'] = 'test123123';  //密码
$db['payinfo_master']['dbname']   = 'payinfo';     //数据库名
$db['payinfo_master']['pconnect'] = FALSE;         //是否连接池
$db['payinfo_master']['db_debug'] = TRUE;          //debug标志，线上关闭，打开后，异常SQL会显示到页面，不安全，仅在测试时打开，（注意，上线一定得将 db_debug 置为 FALSE，否则一定概率可能暴露数据库配置）
$db['payinfo_master']['char_set'] = 'utf8';
$db['payinfo_master']['dbcollat'] = 'utf8_general_ci';
$db['payinfo_master']['autoinit'] = FALSE;         //自动初始化，Loader的时候就连接，建议关闭
$db['payinfo_master']['port'] = 3306;

$db['payinfo_slave']['host']     = '192.168.0.7';
$db['payinfo_slave']['username'] = 'root';
$db['payinfo_slave']['password'] = 'test123123';
$db['payinfo_slave']['dbname']   = 'payinfo';
$db['payinfo_slave']['pconnect'] = FALSE;
$db['payinfo_slave']['db_debug'] = TRUE;
$db['payinfo_slave']['char_set'] = 'utf8';
$db['payinfo_slave']['dbcollat'] = 'utf8_general_ci';
$db['payinfo_slave']['autoinit'] = FALSE;
$db['payinfo_slave']['port'] = 3306;
```

#### 原生SQL：
```php
$data = $this->db->query("select * from user_info where country='China' limit 3");
```

#### 查询多条记录：
```php
$data = $this->db->get("user_info", ['regist_time[<]' => '2018-06-30 15:48:39', 
                                    'gender' => 1,
                                    'country' => 'China',
				    'city[!]' => null,
                                    'ORDER' => [
                                        "user_id",
                                        "regist_time" => "DESC",
                                        "amount" => "ASC"
                                        ],
                                    'LIMIT' => 10], "user_id,nickname,city");
echo json_encode($data);exit;
```
```json
[
    {
        "nickname":"芒果",
        "user_id":6818810,
        "city":"Yichun"
    },
    {
        "nickname":"Smile、格调",
        "user_id":6860814,
        "city":"Guangzhou"
    },
    {
        "nickname":"Yang",
        "user_id":6870818,
        "city":"Hengyang"
    },
    {
        "nickname":"凉之渡",
        "user_id":7481824,
        "city":"Guangzhou"
    }
]
```

#### 查询单列
```php
$data = $this->db->get("user_info", ['regist_time[<]' => '2018-06-30 15:48:39', 
                                    'gender' => 1,
                                    'country' => 'China',
				    'city[!]' => null,
                                    'ORDER' => [
                                        "user_id",
                                        "regist_time" => "DESC",
                                        "amount" => "ASC"
                                        ],
                                    'LIMIT' => 10], "nickname");
echo json_encode($data);exit;
```
```json
[
	"芒果",
	"Smile、格调",
	"Yang",
	"凉之渡"
]
```

#### 查询单条记录
```php
$data = $this->db->get_one("user_info", ['user_id' => 6818810]);
```
```json
{
    "union":null,
    "amount":0,
    "session_key":"Et1yjxbEfRqVmCVsYf5qzA==",
    "open_id":"oXtwn4wkPO4FhHmkan097DpFobvA",
    "nickname":"芒果",
    "last_login_time":"2018-10-04 16:01:27",
    "regist_time":"2018-06-29 21:24:45",
    "user_id":6818810,
    "token":"5a350bc05bbbd9556f719a0b8cf2a5ed",
    "updatetime":"2018-10-04 16:01:27",
    "avatar_url":"https://wx.qlogo.cn/mmopen/vi_32/DYAIOgq83epqg7FwyBUGd5xMXxLQXgW2TDEBhnNjPVla8GmKiccP0pFiaLK1BGpAJDMiaoyGHR9Nib2icIX9Na4Or0g/132",
    "city":"Yichun",
    "province":"Jiangxi",
    "country":"China",
    "appid":"wx385863ba15f573b6",
    "gender":1,
    "form_id":"" 
}
```

#### 插入数据
```php
function register_user($appid, $userid, $open_id, $session_key) {
	$data = array();
        $data['appid'] = $appid;
        $data['user_id'] = $userid;
        $data['open_id'] = $open_id;
        $data['session_key'] = $session_key;
        $data['last_login_time'] = $data['regist_time'] = date('Y-m-d H:i:s', time());
        $data['token'] = md5(TOKEN_GENERATE_KEY . time() . $userid . $session_key);
        $ret = $this->db->insert("user_info", $data);
        if ($ret != -1) {
            return $data['token'];
        } else {
            $this->util_log->LogError("error to register_user, DATA=[".json_encode($data)."]");
            return false;
        }
}
```

#### 更新数据
```php
function update_user($userid, $update_data) {
        $redis = Loader::redis("userinfo");
        $redis->del("pre_redis_user_info_" . $userid);

        $ret = $this->db->update("user_info", ["user_id" => $userid], $update_data);
        if ($ret != -1) {
            return true;
        } else {
            $this->util_log->LogError("error to update_user, DATA=[".json_encode($update_data)."]");
            return false;
        }
}
```

#### 删除操作
```php
$ret = $this->db->delete("user_info", ["user_id" => 7339820]);
```


#### 更多操作参考
通过 $this->db->get_ycdb(); 可以获取ycdb句柄进行更多数据库操作， ycdb 的使用教程如下：
英文： https://github.com/caohao-php/ycdatabase<br>
中文： https://blog.csdn.net/caohao0591/article/details/84390713

## 配置加载
通过 Loader::config('xxxxx'); 加载 /application/config/xxxxx.php 的配置。例如：
```php
$config = Loader::config('config');
var_dump($config);
```

## 公共类加载
所有的公共类库位于superci/application/library目录，但是注意的是， 如果你的类位于library子目录下面，你的类必须用下划线"_"分隔；
```php
$this->sample = Loader::library('Sample');
```
加载的就是 framework/application/library/Sample.php 中的 Sample类。

```php
$this->ip_location = Loader::library('Ip_Location');
```
加载的是 framework/application/library/Ip/Location.php 中的Ip_Location类

## 公共函数
所有的公共类库位于superci/application/helpers目录，通过 Loader::helper('common_helper'); 方法包含进来。

## 日志
日志使用方法如下：
```php
$this->util_log = Logger::get_instance('userinfo');
$this->util_log->LogInfo("register success");
$this->util_log->LogError("not find userinfo");
```
日志级别：
```php
const DEBUG  = 'DEBUG';   /* 级别为 1 ,  调试日志,   当 DEBUG = 1 的时候才会打印调试 */
const INFO   = 'INFO';    /* 级别为 2 ,  应用信息记录,  与业务相关, 这里可以添加统计信息 */
const NOTICE = 'NOTICE';  /* 级别为 3 ,  提示日志,  用户不当操作，或者恶意刷频等行为，比INFO级别高，但是不需要报告*/
const WARN  = 'WARN';    /* 级别为 4 ,  警告,   应该在这个时候进行一些修复性的工作，系统可以继续运行下去 */
const ERROR   = 'ERROR';   /* 级别为 5 ,  错误,     可以进行一些修复性的工作，但无法确定系统会正常的工作下去，系统在以后的某个阶段， 很可能因为当前的这个问题，导致一个无法修复的错误(例如宕机),但也可能一直工作到停止有不出现严重问题 */
const FATAL  = 'FATAL';   /* 级别为 6 ,  严重错误,  这种错误已经无法修复，并且如果系统继续运行下去的话，可以肯定必然会越来越乱, 这时候采取的最好的措施不是试图将系统状态恢复到正常，而是尽可能的保留有效数据并停止运行 */
```

FATAL和ERROR级别日志文件以 .wf 结尾， DEBUG级别日志文件以.debug结尾，日志目录存放于 /data/app/localhost 下面，localhost为你的项目域名，比如：
```shell
[root@gzapi: /data/app/logs/localhost]# ls
userinfo.20190211.log  userinfo.20190211.log.wf
```

日志格式: [日志级别] [时间] [错误代码] [文件|行数] [ip] [uri] [referer] [cookie] [统计信息] "内容"
```shell
[INFO] [2019-02-11 18:57:01] - - [218.30.116.8] - - - [] "register success"
[ERROR] [2019-02-11 18:57:01] [0] [index.php|23 => | => User.php|35 => Userinfo.php|93] [218.30.116.8] [/index.php?c=user&m=getUserInfo&userid=6842811&token=c9bea5dee1f49488e2b4b4645ff3717e] [] [] - "not find userinfo"
```

## VIEW层
视图层参考yaf视图渲染那部分， 我没有写案例。

## 附录 - Core_Model 中的辅助极速开发函数（不关心可以跳过）
$this->redis_conf_path = 'default_master';   //用到快速缓存时，需要在 __construct 构造函数中加上 redis  缓存配置
```php
/**
 * 插入表记录
 * @param string table 表名
 * @param array data 表数据
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 */
public function insert_table($table, $data, $redis_key = "");
/**
 * 更新表记录
 * @param string table 表名
 * @param array where 查询条件
 * @param array data 更新数据
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 */
public function update_table($table, $where, $data, $redis_key = "");
/**
 * 替换表记录
 * @param string table 表名
 * @param array data 替换数据
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 */
public function replace_table($table, $data, $redis_key = "");
/**
 * 删除表记录
 * @param string table 表名
 * @param array where 查询条件
 * @param string redis_key redis缓存键值, 可空， 非空时清理键值缓存
 */
public function delete_table($table, $where, $redis_key = "");
/**
 * 获取表数据
 * @param string table 表名
 * @param array where 查询条件
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 * @param int redis_expire redis 缓存到期时长(秒)
 * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
 */
public function get_table_data($table, $where = array(), $redis_key = "", $redis_expire = 600, $set_empty_flag = true);
/**
 * 根据key获取表记录
 * @param string table 表名
 * @param string key 键名
 * @param string value 键值
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 * @param int redis_expire redis 缓存到期时长(秒)
 * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
 */
public function get_table_data_by_key($table, $key, $value, $redis_key = "", $redis_expire = 300, $set_empty_flag = true);

/**
 * 获取一条表数据
 * @param string table 表名
 * @param array where 查询条件
 * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
 * @param int redis_expire redis 缓存到期时长(秒)
 * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
 */
public function get_one_table_data($table, $where, $redis_key = "", $redis_expire = 600, $set_empty_flag = true);
```

## RPC 框架介绍
#### 传统web应用弊端
  传统的Web应用, 一个应用随着业务快速增长, 开发人员的流转, 就会慢慢的进入一个恶性循环, 代码量上只有加法没有了减法. 因为随着系统变复杂, 牵一发就会动全局, 而新来的维护者, 对原有的体系并没有那么多时间给他让他全面掌握. 即使有这么多时间, 要想掌握以前那么多的维护者的思维的结合, 也不是一件容易的事情…

那么, 长次以往, 这个系统将会越来越不可维护…. 到一个大型应用进入这个恶性循环, 那么等待他的只有重构了.

那么, 能不能对这个系统做解耦呢？ 我们已经做了很多解耦了, 数据, 中间件, 业务, 逻辑, 等等, 各种分层. 但到Web应用这块, 还能怎么分呢, MVC我们已经做过了….

#### 微服务
目前比较流行的解决方案是采用微服务来解决，它可以让我们的系统尽可能快地响应变化，微服务是指开发一个单个小型的但有业务功能的服务，每个服务都有自己的处理和轻量通讯机制，可以部署在单个或多个服务器上。微服务也指一种种松耦合的、有一定的有界上下文的面向服务架构。也就是说，如果每个服务都要同时修改，那么它们就不是微服务，因为它们紧耦合在一起；如果你需要掌握一个服务太多的上下文场景使用条件，那么它就是一个有上下文边界的服务，这个定义来自DDD领域驱动设计。

相对于单体架构和SOA，它的主要特点是组件化、松耦合、自治、去中心化，体现在以下几个方面：

- 一组小的服务

服务粒度要小，而每个服务是针对一个单一职责的业务能力的封装，专注做好一件事情。

- 独立部署运行和扩展 

每个服务能够独立被部署并运行在一个进程内。这种运行和部署方式能够赋予系统灵活的代码组织方式和发布节奏，使得快速交付和应对变化成为可能。

- 独立开发和演化 

技术选型灵活，不受遗留系统技术约束。合适的业务问题选择合适的技术可以独立演化。服务与服务之间采取与语言无关的API进行集成。相对单体架构，微服务架构是更面向业务创新的一种架构模式。

- 独立团队和自治 

团队对服务的整个生命周期负责，工作在独立的上下文中，自己决策自己治理，而不需要统一的指挥中心。团队和团队之间通过松散的社区部落进行衔接。

   我们可以看到整个微服务的思想就如我们现在面对信息爆炸、知识爆炸是一样的：通过解耦我们所做的事情，分而治之以减少不必要的损耗，使得整个复杂的系统和组织能够快速的应对变化。

#### 微服务的基石--RPC服务
 基于Yar的RPC为我们提供了跨网络的服务调用框架，Yar是一个非常轻量级的RPC框架, Yar作者在实现Yar的时候, 追求了极致的轻量级, 它使用非常简单, 对于Server端和Soap使用方法很相像吧，而对于客户端，你可以像调用本地对象的函数一样，调用远程的函数。



