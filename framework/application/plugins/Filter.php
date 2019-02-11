<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FilterPlugin Class
 *
 * @package        SuperCI
 * @subpackage    Plugin
 * @category      Filter Plugin
 * @author        caohao
 */
//过滤插件
class FilterPlugin extends Yaf_Plugin_Abstract {
    var $params;

    //路由之前调用
    public function routerStartUp ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response) {
        $this->params = & $request->getParams();
        
        if($this->params['c'] == 'dabaojian') {
        	$this->_auth();
        }
    }
    
    //路由结束之后
    public function dispatchLoopStartup ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response ) {
        $path = Yaf_Registry::get("config")->application->appconf->directory;
        require_once($path . "/constants.php");
        
        require_once(APPPATH . "/application/core/Core_Controller.php");
        require_once(APPPATH . "/application/core/Core_Model.php");
    }

    //分发循环结束之后触发
    public function dispatchLoopShutdown ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response) {
        
    }
    
    //验签过程
    protected function _auth()
    {
    	if($this->params['no'] == "test") { //测试
    		return;
    	}
    	
    	if (empty($this->params['client'])) {
				$this->response_error(99990001, 'params error');
			}

			if (empty($this->params['auth_rand'])) {
				$this->response_error(99990002, 'params error');
			}
			
			if (empty($this->params['timestamp'])) {
				$this->response_error(99990003, 'params error');
			}
			
			if (empty($this->params['v'])) {
				$this->response_error(99990004, 'params error');
			}
			
			if (empty($this->params['signature'])) {
				$this->response_error(99990005, 'params error');
			}
			
    	$auth_params = $this->params;
    	$c = $this->params['c'];
    	$m = $this->params['m'];
    	unset($auth_params['c']);
    	unset($auth_params['m']);
    	unset($auth_params['signature']);
			unset($auth_params['callback']);
			unset($auth_params['_']);
			
    	$str = "/" . $c . "/" . substr($m, 1) . "/" . $auth_params['token'] . "/"; // 加密串str = "/游戏名/接口/token/"
			
    	unset($auth_params['token']);  // 去掉 token
			ksort($auth_params);  //数组按 key 排序
			reset($auth_params);  //重置数组指针指向第一个元素
    	
			foreach ($auth_params as $param_value) {  //将有序串加入到加密串 str
				$str = $str . trim($param_value);
			}
			
			$signature = md5($str); //加密得到 signature
			
			if($signature != $this->params['signature']) {  //加密之后与上送的signature 比较，如果不一致则验证失败
				$this->response_error(99990006, "params error");
			}
			
			$redis = Loader::redis("default");
			if(!empty($redis->get("signature_$signature"))) {
				$this->response_error(99990016, "params error");
			}
			
			$redis->set("signature_$signature", 1);
			$redis->expire("signature_$signature", 3600);
    }
    
    /**
     * 返回错误code以及错误信息
     * @param sting $message   返回错误的提示信息
     * @param int $type 	返回的方式
     */
    private function response_error($code, $message)
    {
        $data = array("tagcode" => intval($code), "description" => $message);
        if(empty($_REQUEST['callback'])) {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        } else {
            echo $_REQUEST['callback'].'('.json_encode($data).')';
            exit;
        }
    }
}
