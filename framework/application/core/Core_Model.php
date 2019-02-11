<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExampleModel Class
 *
 * @package        SuperCI
 * @subpackage    Model
 * @category      Example Model
 * @author        caohao
 */
class Core_Model {
    var $db;
    const EMPTY_STRING = -999999999;

    public function __construct() {
        $this->util_log = Logger::get_instance('core_model_log');
    }

    /**
     * 根据key获取表记录
     * @param string redis_key redis 缓存键值
     */
    private function get_redis($redis_key) {
        if (empty($redis_key)) return;

        $redis = Loader::redis("default");
        if (!empty($redis)) {
            //return $redis->get($redis_key);
        }
    }

    /**
     * 设置 redis 值
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     * @param array data 表数据
     * @param int redis_expire redis 缓存到期时长(秒)
     * @param boolean set_empty_flag 是否缓存空值，如果缓存空值，在表记录更新之后，一定记得清理空值标记缓存
     */
    private function set_redis($redis_key, $data, $redis_expire, $set_empty_flag) {
        if (empty($redis_key)) return;

        $redis = Loader::redis("default");
        if (!empty($redis)) {
            if (empty($data) && $set_empty_flag) {
                $redis->set($redis_key, self::EMPTY_STRING);
            } else {
                $redis->set($redis_key, serialize($data));
            }
            $redis->expire($redis_key, $redis_expire);
        }
    }

    /**
     * 清理记录缓存
     * @param string redis_key redis 缓存键值
     */
    public function clear_redis_cache($redis_key = "") {
        if (empty($redis_key)) {
            return;
        }

        $redis = Loader::redis("default");
        if (!empty($redis)) {
            $redis->del($redis_key);
        }
    }

    /**
     * 插入表记录
     * @param string table 表名
     * @param array data 表数据
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     */
    public function insert_table($table, $data, $redis_key = "") {
        $ret = $this->db->insert($table, $data);

        if (!empty($redis_key)) {
            $this->clear_redis_cache($redis_key);
        }

        if ($ret == -1) {
            $this->util_log->LogError("error to insert_table $table , DATA=[".json_encode($data)."]");
            return 0;
        }

        return intval($ret);
    }

    /**
     * 更新表记录
     * @param string table 表名
     * @param array where 查询条件
     * @param array data 更新数据
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     */
    public function update_table($table, $where, $data, $redis_key = "") {
        if (empty($where)) return;
        $ret = $this->db->update($table, $where, $data);

        if (!empty($redis_key)) {
            $this->clear_redis_cache($redis_key);
        }

        if ($ret != -1) {
            return true;
        } else {
            $this->util_log->LogError("error to update_table $table [".json_encode($where)."], DATA=[".json_encode($data)."]");
            return false;
        }
    }

    /**
     * 替换表记录
     * @param string table 表名
     * @param array data 替换数据
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     */
    public function replace_table($table, $data, $redis_key = "") {
        $ret = $this->db->replace($table, $data);

        if (!empty($redis_key)) {
            $this->clear_redis_cache($redis_key);
        }

        if ($ret != -1) {
            return true;
        } else {
            $this->util_log->LogError("error to replace_table $table , DATA=[".json_encode($data)."]");
            return false;
        }
    }

    /**
     * 删除表记录
     * @param string table 表名
     * @param array where 查询条件
     * @param string redis_key redis缓存键值, 可空， 非空时清理键值缓存
     */
    public function delete_table($table, $where, $redis_key = "") {
        if (empty($where)) return;
        $ret = $this->db->delete($table, $where);

        if (!empty($redis_key)) {
            $this->clear_redis_cache($redis_key);
        }

        if ($ret != -1) {
            return true;
        } else {
            $this->util_log->LogError("error to delete_table $table [".json_encode($where)."]");
            return false;
        }
    }

    /**
     * 根据key获取表记录
     * @param string table 表名
     * @param string key 键名
     * @param string value 键值
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     * @param int redis_expire redis 缓存到期时长(秒)
     * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
     */
    public function get_table_data_by_key($table, $key, $value, $redis_key = "", $redis_expire = 300, $set_empty_flag = true) {
        $data = $this->get_redis($redis_key);
        if (!empty($data)) {
            if ($data == self::EMPTY_STRING) {
                return;
            } else {
                return unserialize($data);
            }
        }

        $data = $this->db->get_one($table, [$key => $value]);
        if($data != -1) {
            $this->set_redis($redis_key, $data, $redis_expire, $set_empty_flag);
            return $data;
        }
        return array();
    }

    /**
     * 获取表数据
     * @param string table 表名
     * @param array where 查询条件
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     * @param int redis_expire redis 缓存到期时长(秒)
     * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
     */
    public function get_table_data($table, $where = null, $redis_key = "", $redis_expire = 600, $set_empty_flag = true) {
        $data = $this->get_redis($redis_key);
        if (!empty($data)) {
            if ($data == self::EMPTY_STRING) {
                return;
            } else {
                return unserialize($data);
            }
        }

        $data = $this->db->get($table, $where);
        if($data != -1) {
            $this->set_redis($redis_key, $data, $redis_expire, $set_empty_flag);
            return $data;
        }
        return array();
    }

    /**
     * 获取一条表数据
     * @param string table 表名
     * @param array where 查询条件
     * @param string redis_key redis 缓存键值, 可空， 非空时清理键值缓存
     * @param int redis_expire redis 缓存到期时长(秒)
     * @param boolean set_empty_flag 是否标注空值，如果标注空值，在表记录更新之后，一定记得清理空值标记缓存
     */
    public function get_one_table_data($table, $where = null, $redis_key = "", $redis_expire = 600, $set_empty_flag = true) {
        $data = $this->get_redis($redis_key);
        if (!empty($data)) {
            if ($data == self::EMPTY_STRING) {
                return;
            } else {
                return unserialize($data);
            }
        }

        $data = $this->db->get_one($table, $where);
        if($data != -1) {
            $this->set_redis($redis_key, $data, $redis_expire, $set_empty_flag);
            return $data;
        }
        return array();
    }

    ////////////////////////////// 业务相关 /////////////////////////////////////////
    //分数更新，修改排名
    public function modify_rank($project_name, $userid, $score) {
        $redis = Loader::redis('default');
        if (empty($redis)) {
            return false;
        }
        $redis->zadd("pre_{$project_name}_rank", $score, $userid);
        $redis->del("pre_{$project_name}_rank_cache");
        return true;
    }

    //获取我的排名
    public function get_my_rank($project_name, $userid) {
        $redis = Loader::redis('default');
        if (empty($redis)) {
            return 0;
        }

        $myRank = $redis->zrevrank("pre_{$project_name}_rank", $userid);
        $myRank = (empty($myRank) && $myRank !== 0) ? 0 : $myRank + 1;
        return $myRank;
    }

    //获取排名列表
    public function get_rank_list($project_name, $return_userinfo_flag = true, $start = 0, $end = 99) {
        $redis = Loader::redis('default');
        if (empty($redis)) {
            return array();
        }

        $pre_rank_cache = "pre_{$project_name}_rank_cache";
        $result = $redis->get($pre_rank_cache);
        if (!empty($result)) {
            $result = unserialize($result);
        } else {
            $result = array();
            $score_ranks = $redis->zrevrange("pre_{$project_name}_rank", $start, $end, 1);
            if (!empty($score_ranks)) {
                $user_keys = array_keys($score_ranks);

                $userinfos = array();
                if ($return_userinfo_flag) {
                    $userinfo_model = Loader::model('UserinfoModel');
                    $userinfos = $userinfo_model->getUserInUserids($user_keys);
                }
                
                foreach($score_ranks as $key => $value) {
                    if (!empty($userinfos[$key])) {
                        $tmp = $userinfos[$key];
                        $tmp['score'] = $value;
                    } else {
                        $tmp = array('user_id' => $key, 'zone_id' => 0,  'nickname' => '', 'avatar_url' => '', 'city' => '');
                        $tmp['score'] = $value;
                    }
                    $result[] = $tmp;
                }
            }

            $redis->set($pre_rank_cache, serialize($result));
            $redis->expire($pre_rank_cache, 3600);
        }

        return $result;
    }

    //清理排名
    public function clear_rank($project_name) {
        $redis = Loader::redis('default');
        if (empty($redis)) {
            return array();
        }

        $redis->del("pre_{$project_name}_rank_cache");
        $redis->del("pre_{$project_name}_rank");
    }

    //清理我的排行
    public function clear_my_rank($project_name, $userid) {
        $redis = Loader::redis('default');
        if (empty($redis)) {
            return 0;
        }

        $myRank = $redis->zrem("pre_{$project_name}_rank", $userid);
    }
}
