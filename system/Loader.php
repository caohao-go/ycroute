<?php
/**
 * Loader Class
 *
 * @package        SuperCI
 * @subpackage    Libraries
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
        self::$configs[$conf_name] = include_once("$path/".$conf_name.".php");
        return self::$configs[$conf_name];
    }
    
    public static function helper($helper_name) {
        
        if (isset(self::$has_helpers[$helper_name])){
            return true;
        }
        
        $path = Yaf_Registry::get("config")->application->helper->directory;
        if (file_exists($path . "/" . $helper_name . ".php")){
            include_once($path . "/" . $helper_name . ".php");
            self::$has_helpers[$helper_name] = true;
        } else {
            self::$has_helpers[$helper_name] = false;
        }
        
        return true;
    }
    
    public static function library($library_name, $params = null) {
        if(!Yaf_Registry::has($library_name)) {
            $file_name = APPPATH . "/application/library/" . implode('/', explode('_', $library_name)) . ".php";
            include_once($file_name);
            if(empty($params)) {
                Yaf_Registry::set($library_name, new $library_name());
            } else {
                Yaf_Registry::set($library_name, new $library_name($params));
            }
        }
        
        return Yaf_Registry::get($library_name);
    }
    
    public static function model($library_name, $params = null) {
        if(!Yaf_Registry::has($library_name)) {
            if(empty($params)) {
                Yaf_Registry::set($library_name, new $library_name());
            } else {
                Yaf_Registry::set($library_name, new $library_name($params));
            }
        }
        
        return Yaf_Registry::get($library_name);
    }
    
    public static function database($params = '', $return = true, $use_phalcon = true)
    {
        global $CI_DATABASES;
        
        require_once(BASEPATH.'/database/DB.php');
        
        if(!isset($CI_DATABASES[$params]))
        {
            $CI_DATABASES[$params] = DB($params, $use_phalcon);
        }
        
        if ($return == true)
        {
            return $CI_DATABASES[$params];
        }
    }

    public static function & redis($redis_name, $host, $port = 6379) {
        if(empty(self::$redis[$redis_name])) {
            if(empty($host)) {
                return null;
            }
            self::$redis[$redis_name] = new Redis();
            $flag = self::$redis[$redis_name]->connect($host, $port);
            if(!$flag) {
                unset(self::$redis[$redis_name]);
                return null;
            }
        }
        
        return self::$redis[$redis_name];
    }
}
