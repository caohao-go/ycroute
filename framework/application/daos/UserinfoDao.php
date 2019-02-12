<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExampleModel Class
 *
 * @package        SuperCI
 * @subpackage    Model
 * @category      Example Model
 * @author        caohao
 */
class UserinfoDao extends Core_Model {
    public function __construct() {
        $this->db = Loader::database('default');
        $this->util_log = Logger::get_instance('userinfo_dao_log');
    }
}
