<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * TestController Class
 *
 * @package        SuperCI
 * @subpackage    Controller
 * @category      TestController
 * @author        caohao
 */
class TestController extends Core_Controller
{
    public function init()
    {
        parent::init();
        
        //model
        $this->example_model = Loader::model('ExampleModel');
        
        //日志
        $this->logger = Logger::get_instance('test_log');
        
        //公共函数
        Loader::helper('common_helper');
    }
    
    public function manUserAction()
    {
        $this->logger->LogInfo("manUser: " . createLinkstringUrlencode($this->params));
        
        if(empty($this->params['name'])) {
            $this->logger->LogError("name is empty");
            $this->response_error(10001, "name is empty");
        }
        
        $config = Loader::config('config');
        
        //加载类
        $this->sample = Loader::library('Sample');
        $this->util_sample = Loader::library('Util_Sample');
        
        //数据返回
        $res = array();
        $res['uid'] = $this->example_model->insert_data($this->params['name'], $this->params['sex'], $this->params['age']);
        $res['more_than_30'] = $this->example_model->get_user_more_than_30();
        $res['insert_user'] = $this->example_model->get_user_by_uid($res['uid']);
        $res['config'] = $config;
        $res['sample'] = $this->sample->getInfo();
        $res['util_sample'] = $this->util_sample->getInfo();
        
        $this->response_success($res);
    }
}
