<?php
/**
 * Bootstrap Class
 *
 * @package        SuperCI
 * @subpackage    Bootstrap
 * @category      Bootstrap
 * @author        caohao
 */
class Bootstrap  extends Yaf_Bootstrap_Abstract {
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

}
