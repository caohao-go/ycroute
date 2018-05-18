<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Core_Controller Class
 *
 * @package        SuperCI
 * @subpackage    Controller
 * @category      Controller Base
 * @author        caohao
 */
//服务控制器基础类，
class Core_Controller extends Yaf_Controller_Abstract {
    protected $params;
    
    public function init() {
        $this->params = $this->getRequest()->getParams();
    }
    
    /**
    * json输出
    * @param array $data
    */
    protected function response_success($message)
    {
        if(empty($message['errno']) && empty($message['errmsg'])) {
            if(empty($message)) {
                $message = array('errno' => 0, 'errmsg' => 'success');
            } else {
                $error = array('errno' => 0, 'errmsg' => 'success');
                $message = array_merge($error, $message);
            }
        }
        
        if(empty($this->params['callback'])) {
            //处理数据
            $data = $this->__handleData($message);
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        } else {
            //处理数据
            $data = $this->__handleData($message);
            $callback = $this->params['callback'];
            echo $callback.'('.json_encode($data).')';
            exit;
        }
    }
    
    /**
     * 返回错误code以及错误信息
     * @param sting $message   返回错误的提示信息
     * @param int $type 	返回的方式
     */
    protected function response_error($code, $message)
    {
        $data = array("errno" => intval($code), "errmsg" => $message);
        if(empty($this->params['callback'])) {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        } else {
            $callback = $this->params['callback'];
            echo $callback.'('.json_encode($data).')';
            exit;
        }
    }
    
    /**
     * 处理返回数据，将空数据置为空字符串
     * @param unknown_type $data
     * @return Ambigous <string, unknown>
     */
    private function __handleData(& $data)
    {
        if(is_array($data))
        {
            foreach($data as $key=>$val)
            {
                if(is_array($val))
                {
                    $data[$key] = $this->__handleData($val);
                } 
                else if(is_null($val)) 
                {
                    $data[$key] = '';
                }
            }
        }
        return $data;
    }
}


