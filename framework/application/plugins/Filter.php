<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FilterPlugin Class
 *
 * @package       SuperCI
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

        $this->_auth();
        
        if(!empty($this->params['rpc'])) {
        	$this->_rpc_auth(); //rpc 调用校验
    	}
    }
    
    //路由结束之后
    public function dispatchLoopStartup ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response ) {
        $path = Yaf_Registry::get("config")->application->appconf->directory;
        require($path . "/constants.php");
        require(APPPATH . "/application/core/Core_Controller.php");
        require(APPPATH . "/application/core/Core_Model.php");
    }

    //分发循环结束之后触发
    public function dispatchLoopShutdown ( Yaf_Request_Abstract $request , Yaf_Response_Abstract $response) {
        
    }
    
    //验签过程
    protected function _auth()
    {
        //在这里写你的验签逻辑
    }
    
    //rpc调用校验
    protected function _rpc_auth()
    {
       	$signature = $this->get_rpc_signature($this->params);
       	if($signature != $this->params['signature']) {
       		$this->response_error(1, 'check failed');
       	}
    }
    
    //rpc签名计算
    public function get_rpc_signature($params) 
    {
    	$secret = 'MJCISDYFYHHNKBCOVIUHFUIHCQWE';
    	unset($params['signature']);
    	ksort($params);
		reset($params);
		unset($auth_params['callback']);
		unset($auth_params['_']);
		$str = $secret;
		foreach ($params as $value) {
			$str = $str . trim($value);
		}
			
		return md5($str);
    }
    
    /**
     * 返回错误code以及错误信息
     * @param sting $message   返回错误的提示信息
     * @param int $type 	返回的方式
     */
    private function response_error($code, $message)
    {
        $data = array("errno" => intval($code), "errmsg" => $message);
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
