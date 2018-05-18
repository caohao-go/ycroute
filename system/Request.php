<?php
/**
 * Request Class
 *
 * @package        SuperCI
 * @subpackage    Request
 * @category      Request
 * @author        caohao
 */
class Request extends Yaf_Request_Http
{
    private $_params;

    public function & getPost()
    {
        if ($this->_posts) {
            return $this->_posts;
        }

        $this->_posts = $this->trimRequest(parent::getPost());
        return $this->_posts;
    }
    
    public function get_post($key) {
        return $this->getParams()[$key];
    }
    
    public function get($key) {
        return $this->getParams()[$key];
    }
    
    public function & getParams()
    {
        if ($this->_params) {
            return $this->_params;
        }
        
        $gets = $this->trimRequest(parent::getQuery());
        $post = $this->trimRequest(parent::getPost());
        
        $this->_params = array_merge($gets, $post);
        return $this->_params;
    }
    
    private function trimRequest($data)
    {
        //在这里写你的GET/POST数据过滤代码，比如增加转义，过滤引号
        return $data;
    }
}