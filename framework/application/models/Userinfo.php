<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExampleModel Class
 *
 * @package       YCRoute
 * @subpackage    Model
 * @category      Example Model
 * @author        caohao
 */
class UserinfoModel extends Core_Model {
    public function __construct() {
    	$this->user_dao = Loader::dao("UserinfoDao");
        $this->db = Loader::database('default');
        $this->util_log = Logger::get_instance('userinfo_log');
    }
	
	public function getUserinfoByUserid($user_id) {
        $redis_key = "pre_redis_user_info_" . $user_id;
        $redis = Loader::redis("userinfo");
        if (!empty($redis)) {
            $userInfo = $redis->get($redis_key);
        }

        if (empty($userInfo)) {
            $userInfo = $this->get_one_user_info_by_key('user_id', $user_id);

            if (!empty($userInfo)) {
                $redis->set($redis_key, serialize($userInfo));
                $redis->expire($redis_key, 900);
            }
        } else {
            $userInfo = unserialize($userInfo);
        }

        return $userInfo;
    }

    public function getUserByName($nickname) {
        return $this->db->query("select * from user_info where nickname like '%$nickname%'");
    }

    function getUserInUserids($userids) {
        $ret = array();

        if (empty($userids)) {
            return $ret;
        }
		
        $result = $this->db->get('user_info', ['user_id' => $userids], "user_id,nickname,avatar_url,city");
        if (!empty($result) && $result != -1) {
            foreach($result as $value) {
                $ret[$value['user_id']] = $value;
            }
        }

        return $ret;
    }
    
    function registerUser($appid, $userid, $open_id, $session_key) {
        $data = array();
        $data['appid'] = $appid;
        $data['user_id'] = $userid;
        $data['open_id'] = $open_id;
        $data['session_key'] = $session_key;
        $data['last_login_time'] = $data['regist_time'] = date('Y-m-d H:i:s', time());
        $data['token'] = md5(TOKEN_GENERATE_KEY . time() . $userid . $session_key);
        $ret = $this->db->insert("user_info", $data);
        if ($ret != -1) {
            return $data['token'];
        } else {
            $this->util_log->LogError("error to register_user, DATA=[".json_encode($data)."]");
            return false;
        }
    }

    function loginUser($userid, $session_key) {
        $data = array();
        $data['user_id'] = $userid;
        $data['session_key'] = $session_key;
        $data['last_login_time'] = date('Y-m-d H:i:s', time());
        $data['token'] = md5(TOKEN_GENERATE_KEY . time() . $userid . $session_key);

        $ret = $this->db->update("user_info", ["user_id" => $userid], $data);

        if ($ret != -1) {
            return $data['token'];
        } else {
            $this->util_log->LogError("error to login_user, DATA=[".json_encode($data)."]");
            return false;
        }
    }

    function updateUser($userid, $update_data) {
        $redis = Loader::redis("userinfo");
        $redis->del("pre_redis_user_info_" . $userid);


        $ret = $this->db->update("user_info", ["user_id" => $userid], $update_data);
        if ($ret != -1) {
            return true;
        } else {
            $this->util_log->LogError("error to update_user, DATA=[".json_encode($update_data)."]");
            return false;
        }
    }
    
    private function get_one_user_info_by_key($key, $value) {
        $ret = $this->db->get_one("user_info", [$key => $value]);
        if($ret == -1) {
            return array();
        }
        return $ret;
    }
	
	private function generate_userid() {
        $cur_time = time();
        $data = array('time' => $cur_time);

        $sequence_no = 0;
        for ($i = 0; $i < 3; $i++) {
            $sequence_no = $this->db->insert('sequence', $data);
            if ($sequence_no != -1) {
                break;
            }
        }

        if ($sequence_no) {
            $time_from_cur = $cur_time - 1529596800; // 2018-06-22
            $userid = sprintf("%s%03d", substr($time_from_cur, 0, -2), substr($sequence_no, -3));
            return intval($userid);
        }
    }
}
