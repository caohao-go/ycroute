SuperCI
===

####我们自己的业务之前框架是基于CI框架写的，设计接口达到500多个，如果全面改用其他框架，修改工作量将非常大，如何在不大规模修改业务代码的基础上让它性能更高成为我研究的方向！

######图文介绍：https://blog.csdn.net/caohao0591/article/details/80271974

####越是简单的东西越是好的，基于此最近研究了PHP的各种框架， 有yaf, phalcon, CI框架， 

+ 其中Yaf 是PHP国内第一人写的纯C框架， 核心在于路由部分与类的加载功能， 可惜没有数据库ORM操作，极轻量级。

+ phalcon是国外非常火的一个框架，也是一个纯C框架，非常重量级，过于臃肿，文档不太完善。

+ CI也是一个市场占有率非常高的框架，是纯PHP框架，适度轻量级，文档丰富，性能不及 Yaf 的 1/3。


####依照上面的原理，我对项目进行了优化升级，在此基础上开发了一个新的轻量级组合框架，命名为 SuperCI：

+ 考虑之前做的项目都是CI框架，如果全部推翻，将会有超级多的东西需要修改，所以我将CI引擎替换，但是SuperCI对外提供的调用方式不变，

+ 首先我将CI框架的路由部分抽取出来， 替换成Yaf。

+ 然后将CI的数据库ORM操作底层引擎替换成Phalcon， 然而这并不是一个全部的Phalcon， 而是将Phalcon所有其它模块全部删除，仅保留DB操作部分，重新编译之后生成的ORM引擎，替换到CI的数据库底层操作，相当于给五菱宏光装上了悍马的发动机，数据库操作性能能提升2倍。

+ 代码模块分离，并加入自己写的模块、类库、配置加载类。

+ 加入自己的日志记录类

+ 替换 PHP 5 到 PHP 7 ， 开启代码缓存opcache。

#####通过以上工作，CPU利用率提升10倍，内存使用大幅提升，响应时间降低到原来50%， 线上运行半年，稳定可靠，线上服务器使用减少2/3，框架极度轻量级， 越是简单的东西越是好的，不说了，上图上源码。



框架介绍
===

运行环境： PHP 7 / PHP 5，  opcache 

依赖扩展： yaf.so ,  phalcon.so 

注意：官网的phalcon, 在PHP7下，由于phalcon的一个数据库绑定导致的 opcache 会和 phalcon冲突，导致两个不能同时用， 两者都是提升性能的利器，尤其 Opcache，能提升1倍性能， 请用我提供的源码中的tool/phalcon的源码重新编译生成 phalcon.so，这里的源码去掉了phalcon除了数据库DB操作以外的所有功能，而且解决了与opcache冲突的问题。

如果是PHP5需要到phalcon官网去下载扩展。



###配置文件

superci/conf/application.ini
####我们看看路由配置部分

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

#####控制器由参数c决定，动作有 m 决定。比如如下demo Url：

http://localhost/index.php?c=test&m=manUser&name=bigbox&sex=%E7%94%B7&age=51

程序将被路由到superci/application/controllers/Test.php文件的 TestController::manUserAction方法，其它路由细节参考Yaf框架

	class TestController extends Core_Controller  
	{  
    	public function manUserAction()  
    	{  
    	}  
	}  


###入口

superci/index.php

定义目录，加载Yaf_Application

	$app = new Yaf_Application(APPPATH . "/conf/application.ini");  
	$app->bootstrap()->run();  

###Bootstrap启动过程

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


###过滤器插件

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


###控制层

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

###模型层
所有的Model层位于 superci/application/models目录，

通过 $this->example_model = Loader::model('ExampleModel'); 加载模型



###VIEW层

视图层参考yaf视图渲染那部分， 我没有写案例。



###APP应用配置

所有配置位于 superci/application/config目录

通过 $config = Loader::config('config');  参数为 config里面文件名称， 比如上面加载的就是 config.php



###公共类加载

所有的公共类库位于superci/application/library目录，但是注意的是， 如果你的类位于library子目录下面，你的类必须用下划线"_"分隔；

$this->sample = Loader::library('Sample'); 加载的就是 superci/application/library/Sample.php 中的 Sample类。

$this->util_sample = Loader::library('Util_Sample'); 加载的是 superci/application/library/Util/Sample.php 中的Util_Sample类



###公共函数

所有的公共类库位于superci/application/helpers目录

通过 Loader::helper('common_helper'); 方法包含进来。



###数据库操作

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



###日志

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



####日志格式: [日志级别] [时间] [错误代码] [文件|行数] [ip] [uri] [referer] [cookie] [统计信息] "内容"

[ERROR] [2018-05-10 14:12:38] [0] [Test.php|31] [192.168.37.41] [/index.php?c=test&m=manUser&name=&sex=%E7%94%B7&age=51] [] [uuid=eeced9c342ae1a4010c815d253cbf892; Hm_lvt_ff8e5ea3d826cc3ff9e62f38fb25f05b=1506585535,1506585546,1506585753,1506585757; BDTUJIAID=062546b9e6f05ba8dcd03fcd00e8aec9; UM_distinctid=1626732575637-0363efcaa-671d107a-1fa400-16267325757483; iciba_u_rand=f81419f1f1f83a9627fa928b356020cc%40114.251.146.132; iciba_u_rand_t=1523429063; _last_active=a%3A3%3A%7Bs%3A1%3A%22i%22%3Bs%3A8%3A%2214406053%22%3Bi%3A0%3Bi%3A1524725552%3Bs%3A1%3A%22u%22%3Bs%3A12%3A%22my.iciba.com%22%3B%7D] - "name is empty"



