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

        $this->sample = Loader::library('sample'); //加载类库，加载的就是 framework/library/Sample.php 里的Sample类
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
