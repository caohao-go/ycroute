SuperCI
===

## 框架介绍
框架由3层架构构成，Controller、Model、View 层，基于yaf, ycdatabase 扩展 ，支持PHP7，优点如下： <br>

1、框架层次分明、使用简洁（开箱即用）、性能高（yaf、数据库orm都是C语言扩展）、功能强大。

2、支持MySQL数据库 ORM 代理，支持Redis代理，简便的主从配置。

3、强大稳定的数据库/redis连接池支持。

4、强大的日志模块、异常捕获模块。

5、基于PHP7，代码缓存opcache。

###### 图文介绍：


## 运行环境

运行环境： PHP 7 ，  opcache 

依赖扩展： yaf 、  ycdatabase 扩展 

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
         |----- errors        //错误页目录
         |----- helpers       //公共函数目录
         |----- library       //公共类库目录
         |----- models        //模型层目录
         |----- plugins       //yaf路由插件目录，路由前后钩子，(接口验签在这里)
         |----- third         //第三方类库
         |----- views         //视图层
```
	 
## 路由配置
framework/conf/application.ini

##### 我们看看路由配置部分
```php
routes.regex.type="regex"  
routes.regex.match="#^/list/([^/]*)/([^/]*)#"  
routes.regex.route.controller=Index  
routes.regex.route.action=action  
routes.regex.map.1=name  
routes.regex.map.2=value  
routes.simple.type="simple"  
routes.simple.controller=c  
routes.simple.action=m  
routes.simple.module=o
```

##### 控制器由参数c决定，动作有 m 决定。比如如下demo Url：

http://localhost/index.php?c=user&m=getUserInfo&userid=6842811&token=c9bea5dee1f49488e2b4b4645ff3717e

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
framework/application/models/UserinfoModel.php ，模型层，你可以继承自Core_Model， 也可以不用，Core_Model 中封装了许多常用SQL操作。最后一章会介绍各个函数用法。

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

## redis 缓存操作
加载 redis 缓存： Loader::redis('default_master');  参数为framework/application/config/redis.php 配置键值，如下：
```php
$redis_conf['default_master']['host'] = '127.0.0.1';
$redis_conf['default_master']['port'] = 6379;
$redis_conf['default_slave']['host'] = '127.0.0.1';
$redis_conf['default_slave']['port'] = 6380;

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

## 数据库操作
数据库加载：  Loader::database("default");   参数为framework/application/config/redis.php 配置键值，如下：
```php
$db['default']['unix_socket'] = '/var/run/mysql_sock/mysql_user_pool.sock';  //unix socket 数据库连接池，使用参考 https://blog.csdn.net/caohao0591/article/details/85255704
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = TRUE;
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['autoinit'] = FALSE;

$db['payinfo_master']['host']     = '127.0.0.1';
$db['payinfo_master']['username'] = 'root';
$db['payinfo_master']['password'] = 'test123123';
$db['payinfo_master']['dbname']   = 'payinfo';
$db['payinfo_master']['pconnect'] = FALSE;
$db['payinfo_master']['db_debug'] = TRUE;
$db['payinfo_master']['char_set'] = 'utf8';
$db['payinfo_master']['dbcollat'] = 'utf8_general_ci';
$db['payinfo_master']['autoinit'] = FALSE;
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



##### VIEW层

视图层参考yaf视图渲染那部分， 我没有写案例。



##### APP应用配置

所有配置位于 superci/application/config目录

通过 $config = Loader::config('config');  参数为 config里面文件名称， 比如上面加载的就是 config.php



##### 公共类加载

所有的公共类库位于superci/application/library目录，但是注意的是， 如果你的类位于library子目录下面，你的类必须用下划线"_"分隔；

$this->sample = Loader::library('Sample'); 加载的就是 superci/application/library/Sample.php 中的 Sample类。

$this->util_sample = Loader::library('Util_Sample'); 加载的是 superci/application/library/Util/Sample.php 中的Util_Sample类



##### 公共函数

所有的公共类库位于superci/application/helpers目录

通过 Loader::helper('common_helper'); 方法包含进来。



##### 日志

日志使用方法如下：

	$this->logger = Logger::get_instance('test_log');  
	  
	$this->logger->LogInfo("manUser: " . createLinkstringUrlencode($this->params));  
	$this->logger->LogError("name is empty");  

日志级别如下：

	const DEBUG  = 'DEBUG';   /* 级别为 1 ,  调试日志,   当 DEBUG = 1 的时候才会打印调试 */  
    const INFO   = 'INFO';    /* 级别为 2 ,  应用信息记录,  与业务相关, 这里可以添加统计信息 */  
    const NOTICE = 'NOTICE';  /* 级别为 3 ,  提示日志,  用户不当操作，或者恶意刷频等行为，比INFO级别高，但是不需要报告*/  
    const ERROR  = 'ERROR';    /* 级别为 4 ,  警告,   应该在这个时候进行一些修复性的工作，系统可以继续运行下去 */  
    const WARN   = 'WARN';   /* 级别为 5 ,  错误,     可以进行一些修复性的工作，但无法确定系统会正常的工作下去，系统在以后的某个阶段， 很可能因为当前的这个问题，导致一个无法修复的错误(例如宕机),但也可能一直工作到停止有不出现严重问题 */  
    const FATAL  = 'FATAL';   /* 级别为 6 ,  严重错误,  这种错误已经无法修复，并且如果系统继续运行下去的话，可以肯定必然会越来越乱, 这时候采取的最好的措施不是试图将系统状态恢复到正常，而是尽可能的保留有效数据并停止运行 */  

FATAL和ERROR级别日志文件以 .wf 结尾， DEBUG级别日志文件以.debug结尾，日志目录存放于 /data/app/localhost 下面，localhost为你的项目域名，比如：



#### 日志格式: [日志级别] [时间] [错误代码] [文件|行数] [ip] [uri] [referer] [cookie] [统计信息] "内容"

[ERROR] [2018-05-10 14:12:38] [0] [Test.php|31] [192.168.37.41] [/index.php?c=test&m=manUser&name=&sex=%E7%94%B7&age=51] [] [uuid=eeced9c342ae1a4010c815d253cbf892; Hm_lvt_ff8e5ea3d826cc3ff9e62f38fb25f05b=1506585535,1506585546,1506585753,1506585757; BDTUJIAID=062546b9e6f05ba8dcd03fcd00e8aec9; UM_distinctid=1626732575637-0363efcaa-671d107a-1fa400-16267325757483; iciba_u_rand=f81419f1f1f83a9627fa928b356020cc%40114.251.146.132; iciba_u_rand_t=1523429063; _last_active=a%3A3%3A%7Bs%3A1%3A%22i%22%3Bs%3A8%3A%2214406053%22%3Bi%3A0%3Bi%3A1524725552%3Bs%3A1%3A%22u%22%3Bs%3A12%3A%22my.iciba.com%22%3B%7D] - "name is empty"



