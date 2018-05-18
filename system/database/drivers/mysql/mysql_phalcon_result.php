<?php
/**
 * Mysql_phalcon_result Class
 *
 * @package        SuperCI
 * @subpackage    database
 * @category      Mysql_phalcon_result
 * @author        caohao
 */
class Mysql_phalcon_result {
    var $result_id                = NULL;
    var $result_array            = array();
    var $result_row              = array();
    var $result_object            = array();
    var $custom_result_object    = array();
    var $current_row            = 0;
    var $num_rows                = 0;
    var $row_data                = NULL;
    
    function num_rows()
    {
        return $this->result_id->numRows();
    }
    
    function free_result()
    {
        return TRUE;
    }
    
    public function result_array()
    {
        if (count($this->result_array) > 0)
        {
            return $this->result_array;
        }
        
        if (empty($this->result_id) OR $this->num_rows() == 0)
        {
            return array();
        }
        
        $this->result_id->setFetchMode(Phalcon\DB::FETCH_ASSOC);
        $this->result_array = $this->result_id->fetchAll();
        
        return $this->result_array;
    }
    
    public function row_array($n = 0)
    {
        $result = $this->result_array();

        if (count($result) == 0)
        {
            return $result;
        }

        if ($n != $this->current_row AND isset($result[$n]))
        {
            $this->current_row = $n;
        }
        
        return $result[$this->current_row];
    }
}
