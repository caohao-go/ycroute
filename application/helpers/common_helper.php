<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @package        SuperCI
 * @subpackage    Function
 * @category      Common Function
 * @author        caohao
 */
if (!function_exists('http_open'))
{
function http_open($url, $post = '', &$errmsg = '', $refer = '', $cookie = '', $out_time = 2)
{
    if (empty($url)) {
        $errmsg = 'URL参数不能为空';
        return false;
    }
        
    if (!function_exists('curl_init')) {
        $errmsg = 'CURL模块没有加载';
        return false;
    }
        
    $ch = curl_init();
    if ($ch === false) {
            $errmsg = '初始化句柄错误';
            return false;
    }
        
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
        
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
        
    if ($refer) {
        curl_setopt($ch, CURLOPT_REFERER, $refer);
    }
        
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
            
    if(isset($_SERVER['HTTP_USER_AGENT']))
    {
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $out_time);
        
    $data = curl_exec($ch);
        
    if ($data === false) {
        $errmsg = curl_error($ch);
        curl_close($ch);
        return false;
    }
    return $data;
} 
}

if ( ! function_exists('createLinkstringUrlencode'))
{
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
    $arg  = "";
    
    if(is_array($para)) {
        foreach($para as $key => $val) {
            $arg.=$key."=".urlencode($val)."&";
        }
    }
    
    //去掉最后一个&字符
    $arg = rtrim(trim($arg), "&");
    
    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
    
    return $arg;
}
}