SuperCI
===


## 框架介绍
框架由3层架构构成，Controller、Model、View 层，基于yaf, ycdatabase 扩展 ，支持PHP7，优点如下： <br>

1、框架层次分明、使用简洁（开箱即用）、性能高（yaf、数据库orm都是C语言扩展）、功能强大。

2、支持MySQL数据库 ORM 代理，支持Redis代理，简便的主从配置。

3、强大稳定的数据库/redis连接池支持。

4、强大的日志模块、异常捕获模块。

5、基于PHP7，代码缓存opcache。

###### 图文介绍：https://blog.csdn.net/caohao0591/article/details/80271974


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


/application/plugins/Filter.php  ，  在 _auth 中写入验签方法，所有接口都会在这里校验， 所有GET、POST等参数放在 $this->params 里。

##### 入口

superci/index.php

定义目录，加载Yaf_Application

	$app = new Yaf_Application(APPPATH . "/conf/application.ini");  
	$app->bootstrap()->run();  

##### Bootstrap启动过程

文件位于superci/application/bootstrap.php， Yaf的初始化逻辑， 每个_init开头的函数都会被顺序执行，用户也可以在这里添加自己的初始化逻辑

	public function _initConfig() {  
        $config = Yaf_Application::app()->getConfig();  
        Yaf_Registry::set("config", $config);  
    }  
  
    public function _initRoute(Yaf_Dispatcher $dispatcher) {  
        $router = Yaf_Dispatcher::getInstance()->getRouter();  
        $router->addConfig(Yaf_Registry::get("config")->routes);  
    }  
      
    public function _initCommon(Yaf_Dispatcher $dispatcher) { //注册插件  
        require_once(BASEPATH . "/Request.php");  
        require_once(BASEPATH . "/Loader.php");  
        require_once(BASEPATH . "/Logger.php");  
    }  
      
    public function _initRequest(Yaf_Dispatcher $dispatcher) { //初始化请求  
        $dispatcher->setRequest(new Request());  
    }  
      
    public function _initPlugins(Yaf_Dispatcher $dispatcher) { //注册插件  
        $dispatcher->registerPlugin(new FilterPlugin());  
    }  
  
    public function _initException() { //设置异常回调  
        include_once(BASEPATH . "/Common.php");  
        set_error_handler('_exception_handler');  
    }  


##### 过滤器插件

在bootstrap.php 中， 有注册插件 _initPlugins ，我们注册了一个过滤器FilterPlugin，插件定义了6个Hook。


	触发顺序    名称  触发时机    说明  
	1   routerStartup   在路由之前触发 这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成  
	2   routerShutdown  路由结束之后触发    此时路由一定正确完成, 否则这个事件不会触发  
	3   dispatchLoopStartup 分发循环开始之前被触发    
	4   preDispatch 分发之前触发  如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次  
	5   postDispatch    分发结束之后触发    此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次  
	6   dispatchLoopShutdown    分发循环结束之后触发  此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送  
我们将验签的过程放在路由之前，如果进来的请求，验签失败，则直接报错。

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
	        //$this->response_error(1, "验签失败");  
	    }  
	}  


##### 控制层

所有控制器位于：superci/application/controllers 目录，所有控制器继承自Core_Controller方法，里面主要获取GET/POST参数，以及返回数据的处理，Core_Controller继承自 Yaf_Controller_Abstract， init方法会被自动调用，更多细节参考 Yaf 框架控制器。

	class TestController extends Core_Controller  
	{  
	    public function init()  
	    {  
	        parent::init();  
	        $this->example_model = Loader::model('ExampleModel');  
	    }  
	      
	    public function manUserAction()  
	    {  
	        $this->logger->LogInfo("manUser: " . createLinkstringUrlencode($this->params));  
	          
	        //数据返回  
	        $res = array();  
	        $res['uid'] = $this->example_model->insert_data($this->params['name'], $this->params['sex'], $this->params['age']);  
	        ...  
	          
	        $this->response_success($res);  
	    }  
	}  

##### 模型层
所有的Model层位于 superci/application/models目录，

通过 $this->example_model = Loader::model('ExampleModel'); 加载模型



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



##### 数据库操作

数据库操作与CI操作一致，如下，具体细节可以参考CI框架，底层引擎采用phalcon，用户可以从下载包中获取被摘取

	$this->db = Loader::database('default', true);  
  
	//插入数据库  
	$data = array();  
	$data['name'] = $name;  
	$data['sex'] = $sex;  
	$data['age'] = $age;  
	$this->db->insert('customer', $data);  
	$uid = intval( $this->db->insert_id() );  
	  
	//获取多条数据  
	$where = array();  
	$where['age >'] = 30;  
	$data = $this->db->where($where)  
                 ->order_by('age')  
                 ->limit(3)  
                 ->get('customer')  
                 ->result_array();  
	  
	//获取单条数据  
	$data = $this->db->where('uid', $uid)  
                 ->get('customer')  
                 ->row_array();  



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



