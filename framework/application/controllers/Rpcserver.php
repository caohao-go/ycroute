<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * TestController Class https://github.com/caohao-php/ycroute
 *
 * @package       YCRoute
 * @subpackage    Controller
 * @category      RpcserverController
 * @author        caohao
 */
class RpcserverController extends Core_Controller {
    public function init() {
        parent::init(); //必须
    }

    //用户信息服务
    public function userinfoModelAction() {
    	$user_model = Loader::model('UserinfoModel'); //模型层
        $yar_server = new Yar_server($user_model);
		$yar_server->handle();
		exit;
    }
	
	//支付服务
    public function tradeModelAction() {
    	$trade_model = Loader::model('TradeModel'); //模型层
        $yar_server = new Yar_server($trade_model);
		$yar_server->handle();
		exit;
    }
}
