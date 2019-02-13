<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * TestController Class
 *
 * @package       SuperCI
 * @subpackage    Controller
 * @category      TestController
 * @author        caohao
 */
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
    
    //获取用户信息(从远程)
    public function getUserInfoByRemoteAction() {
        $userId = $this->params['userid'];
        $token = $this->params['token'];
        
        if (empty($userId)) {
            $this->response_error(10000017, "user_id is empty");
        }

        if (empty($token)) {
            $this->response_error(10000016, "token is empty");
        }
    	
    	$model = Loader::remote_model('UserinfoModel');
    	$userInfo = $model->getUserinfoByUserid($userId);
    	$this->response_success($userInfo);
    }
    
    //获取用户信息(并行远程调用)
    public function multipleGetUsersInfoByRemoteAction() {
    	$userId = $this->params['userid'];
    	
    	$call_params = array();
    	$call_params[] = ['model' => 'UserinfoModel', 
                          'method' => 'getUserinfoByUserid', 
                          'parameters' => array($userId), 
                          "callback" => array($this, 'callback1')];
    					  
    	$call_params[] = ['model' => 'UserinfoModel', 
                          'method' => 'getUserInUserids', 
                          'parameters' => array(array(6860814, 6870818)), 
                          "callback" => array($this, 'callback2'),
                          "error_callback" => array($this, 'error_callback')];
    					  
    	$call_params[] = ['model' => 'UserinfoModel', 
                          'method' => 'getUserByName', 
                          'parameters' => array('CH.smallhow')];
    	
    	$call_params[] = ['model' => 'UserinfoModel', 
                          'method' => 'unknownMethod', 
                          'parameters' => array(),
                          "error_callback" => array($this, 'error_callback')];
                          
    	Loader::concurrent_call($call_params);
    	echo json_encode($this->retval);
    	exit;
    }
    
    //回调函数1
    public function callback1($retval, $callinfo) {
    	$this->retval['callback1']['retval'] = $retval;
    	$this->retval['callback1']['callinfo'] = $callinfo;
    }
    
    //回调函数2
    public function callback2($retval, $callinfo) {
    	$this->retval['callback2']['retval'] = $retval;
    	$this->retval['callback2']['callinfo'] = $callinfo;
    }
    
    //错误回调
    public function error_callback($type, $error, $callinfo) {
    	$tmp['type'] = $type;
    	$tmp['error'] = $error;
    	$tmp['callinfo'] = $callinfo;
    	$this->retval['error_callback'][] = $tmp;
    }
}
