<?php
/**
 * Loader Class
 *
 * @package       SuperCI
 * @subpackage    System
 * @category      Loader
 * @author        caohao
 */
class Loader
{
    private static $configs = array();
    private static $has_helpers = array();
    private static $redis = array();
    
    private static $instance;

    public static function & get_instance() {
        if(empty(self::$instance)) {
            self::$instance = new Util_Loader();
        }
        
        return self::$instance;
    }
    
    public static function & config($conf_name) {
        if(isset(self::$configs[$conf_name])) {
            return self::$configs[$conf_name];
        }
        
        $path = Yaf_Registry::get("config")->application->appconf->directory;
        self::$configs[$conf_name] = include("$path/".$conf_name.".php");
        return self::$configs[$conf_name];
    }
    
    public static function helper($helper_name) {
        if (isset(self::$has_helpers[$helper_name])){
            return true;
        }
        
        $path = Yaf_Registry::get("config")->application->helper->directory;
        if (file_exists($path . "/" . $helper_name . ".php")){
           	self::my_include_once($path . "/" . $helper_name . ".php");
            self::$has_helpers[$helper_name] = true;
        } else {
            self::$has_helpers[$helper_name] = false;
        }
        
        return true;
    }
    
    public static function library($library_name, $params = null) {
        if(!Yaf_Registry::has($library_name)) {
            $file_name = APPPATH . "/application/library/" . implode('/', explode('_', $library_name)) . ".php";
            include($file_name);
            if(empty($params)) {
                Yaf_Registry::set($library_name, new $library_name());
            } else {
                Yaf_Registry::set($library_name, new $library_name($params));
            }
        }
        
        return Yaf_Registry::get($library_name);
    }
    
    public static function model($model_name, $params = null) {
        if(!Yaf_Registry::has($model_name)) {
            if(empty($params)) {
                Yaf_Registry::set($model_name, new $model_name());
            } else {
                Yaf_Registry::set($model_name, new $model_name($params));
            }
        }
        
        return Yaf_Registry::get($model_name);
    }
    
    public static function remote_model($model_name) {
    	$remote_key = "Remote_" . $model_name;
    	
        if(!Yaf_Registry::has($remote_key)) {
        	$model_config = self::config("rpc")[$model_name];
        	if(empty($model_config['url'])) {
                $util_log->LogError("Loader::remote_model:  remote_model config not exist");
                return;
            }
            
    		self::my_include_once(BASEPATH . "/YarClientProxy.php");
        	
            Yaf_Registry::set($remote_key, new YarClientProxy($model_name, $model_config));
        }
        
        return Yaf_Registry::get($remote_key);
    }
    
    public static function concurrent_call($call_params) {
    	self::my_include_once(BASEPATH . "/YarClientProxy.php");
    	$model_config = self::config("rpc");
    	YarClientProxy::concurrent_call($call_params, $model_config);
    }
    
    public static function dao($dao_name, $params = null) {
        if(!Yaf_Registry::has($dao_name)) {
            $file_name = APPPATH . "/application/daos/$dao_name.php";
            include($file_name);
            if(empty($params)) {
                Yaf_Registry::set($dao_name, new $dao_name());
            } else {
                Yaf_Registry::set($dao_name, new $dao_name($params));
            }
        }
        
        return Yaf_Registry::get($dao_name);
    }

    public static function database($params = '')
    {
        global $YCDB;

        self::my_include_once(BASEPATH.'/ycdb/DBLoader.php');
        
        if(!isset($YCDB[$params]))
        {
            $YCDB[$params] = DBLoader($params);
        }
        return $YCDB[$params];

    }
    
    public static function & redis($redis_name, $reconnect = false) {
        if(empty(self::$redis[$redis_name]) || $reconnect) {
            unset(self::$redis[$redis_name]);
            $util_log = Logger::get_instance('loader_redis');
            
            $redis_config = self::config("redis")[$redis_name];
            if(empty($redis_config)) {
                $util_log->LogError("Loader::redis:  redis config not exist");
                return;
            }
            
            self::$redis[$redis_name] = new Redis();
            
            if(substr($redis_config['host'], 0, 1) == '/') {
                $flag = self::$redis[$redis_name]->connect($redis_config['host']);
            } else {
                $flag = self::$redis[$redis_name]->pconnect($redis_config['host'], $redis_config['port']);
            }
            
            if(!$flag) {
                $util_log->LogError("Loader::redis:  redis connect error");
                return;
            }
            
			if(!empty($redis_config['auth'])){
                $suc = self::$redis[$redis_name]->auth($redis_config['auth']);
                if(!$suc) {
                    $util_log->LogError("Loader::redis:  redis auth error");
                    return;
                }
            }
        }
        return self::$redis[$redis_name];
    }
    
    public static function my_include_once($path) {
    	$key = "included_" . md5($path);
    	if(!Yaf_Registry::has($key)) {
			include($path);
			Yaf_Registry::set($key, 1);
		}
    }
}
