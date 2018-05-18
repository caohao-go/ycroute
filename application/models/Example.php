<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExampleModel Class
 *
 * @package        SuperCI
 * @subpackage    Model
 * @category      Example Model
 * @author        caohao
 */
class ExampleModel
{
    public function __construct()
    {
        $this->db = Loader::database('default', true);
    }
    
    public function insert_data($name, $sex, $age)
    {
        $data = array();
        $data['name'] = $name;
        $data['sex'] = $sex;
        $data['age'] = $age;
        $this->db->insert('customer', $data);
        return intval( $this->db->insert_id() );
    }
    
    public function get_user_more_than_30()
    {
        $where = array();
        $where['age >'] = 30;
        $data = $this->db->where($where)
                         ->order_by('age')
                         ->limit(3)
                         ->get('customer')
                         ->result_array();
        return $data;
    }
    
    public function get_user_by_uid($uid)
    {
        $data = $this->db->where('uid', $uid)
                         ->get('customer')
                         ->row_array();
        return $data;
    }
}