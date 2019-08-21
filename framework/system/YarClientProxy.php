<?php
/**
 * YarClientProxy Class https://github.com/caohao-php/ycroute
 *
 * @package       YCRoute
 * @category      System
 * @author        caohao
 */
class YarClientProxy
{
	private $yar_client;
	private $model_name;
	private $url;
	private $debug;
	
	public function __construct($model_name, $model_config) {
		$this->url = $model_config['url'];
		$this->debug = $model_config['debug'];
		$this->model_name = $model_name;
		
		$url = self::get_signatured_url($this->url);
		$this->yar_client = new Yar_Client($url);
		
		if(!empty($model_config['packager'])) $this->yar_client->setOpt(YAR_OPT_PACKAGER, $model_config['packager']);
		if(!empty($model_config['persitent'])) $this->yar_client->setOpt(YAR_OPT_PERSISTENT, true);
		if(!empty($model_config['connect_timeout'])) $this->yar_client->setOpt(YAR_OPT_CONNECT_TIMEOUT, $model_config['connect_timeout']);
		if(!empty($model_config['timeout'])) $this->yar_client->setOpt(YAR_OPT_TIMEOUT, $model_config['timeout']);
	}
	
	public static function get_signatured_url($url) {
		$get = array();
		$t = parse_url($url, PHP_URL_QUERY);
		parse_str($t, $get);
		$get['timestamp'] = time();
		$get['auth'] = rand(11111111, 9999999999);
		$signature = FilterPlugin::get_rpc_signature($get);
		return $url . "&timestamp=" . $get['timestamp'] . "&auth=" . $get['auth'] . "&signature=" . $signature;
	}
	
	public function __call($func, $args) {
		$ret = null;
		try {
			$ret = call_user_func_array(array($this->yar_client, $func), $args);
		} catch (Exception $e) {
			$yar_client_proxy_log = Logger::get_instance("yar_client_proxy");
			$yar_client_proxy_log->LogError("yar_client_call_error URL=[" . $this->url . "] , Remote_model=[" . $this->model_name . "] Func=[{$func}] Exception=[".$e->getMessage()."]");
			if($this->debug) {
				echo $e;
				exit;
			}
		}
		
		return $ret;
	}
	
	public static function concurrent_call($call_params, $model_config) {
		$yar_client_proxy_log = Logger::get_instance("yar_client_proxy");
		
		//register call
		foreach($call_params as $call_param) {
			if(empty($call_param['method'])) {
				$yar_client_proxy_log->LogError("concurrent_call_error method is empty [$model]");
				return;
			}
			
			$model = $call_param['model'];
			$conf = $model_config[$model];
			if(empty($conf['url'])) {
				$yar_client_proxy_log->LogError("concurrent_call_error not find model config [$model]");
				return;
			}
			
			$option = array();
			if(!empty($conf['packager'])) $option[YAR_OPT_PACKAGER] = $conf['packager'];
			if(!empty($conf['persitent'])) $option[YAR_OPT_PERSISTENT] = $conf['persitent'];
			if(!empty($conf['timeout'])) $option[YAR_OPT_TIMEOUT] = $conf['timeout'];
			
			$url = self::get_signatured_url($conf['url']);
			$callback = empty($call_param['callback']) ? "YarClientProxy::empty_callback" : $call_param['callback'];
			$error_callback = empty($call_param['error_callback']) ? "YarClientProxy::error_empty_callback" : $call_param['error_callback'];
			
			Yar_Concurrent_Client::call($url, $call_param['method'], $call_param['parameters'], $callback, $error_callback, $option);
		}
		
		//send all rpc call 
		Yar_Concurrent_Client::loop("YarClientProxy::empty_callback", "YarClientProxy::error_empty_callback");
		
		//clean all call register
		Yar_Concurrent_Client::reset();
	}
	
	public static function empty_callback($retval, $callinfo) {
	}
	
	public static function error_empty_callback($type, $error, $callinfo) {
	}
}
