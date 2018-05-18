<?php
/**
 * Mysql_phalcon_driver Class
 *
 * @package        SuperCI
 * @subpackage    database
 * @category      Mysql_phalcon_driver
 * @author        caohao
 */
class Mysql_phalcon_driver extends DB_phalcon_active_rec {
    // The character used for escaping
    var    $_escape_char = '`';
    
    var $_count_string = 'SELECT COUNT(*) AS ';
    
    var $_random_keyword = ' RAND()'; // database specific random keyword
    
    function _prep_query($sql)
    {
        if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql))
        {
            $sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
        }
        
        return $sql;
    }
    
    //Escape String
    function escape_str($str, $like = FALSE)
    {
        if (is_array($str))
        {
            foreach ($str as $key => $val)
            {
                $str[$key] = $this->escape_str($val, $like);
            }
            
            return $str;
        }
        
        $str = $this->get_conn_id()->escapeString($str);
        
        // escape LIKE condition wildcards
        if ($like === TRUE)
        {
            $str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
        }

        return $str;
    }

    //Return Affected Rows
    function affected_rows()
    {
        return $this->get_conn_id()->affectedRows();
    }

    //Return Last Insert ID
    function insert_id()
    {
        return $this->get_conn_id()->lastInsertId();
    }

    //"Count All" query
    function count_all($table = '')
    {
        if ($table == '')
        {
            return 0;
        }
        
        $query = $this->query($this->_count_string . " numrows FROM " . $this->_protect_identifiers($table, TRUE, NULL, FALSE));
        $this->_reset_select();
        
        $result = $query->row_array();
        return intval($result['numrows']);
    }
    
    function _from_tables($tables)
    {
        if ( ! is_array($tables))
        {
            $tables = array($tables);
        }

        return '('.implode(', ', $tables).')';
    }
    
    function _insert($table, $set)
    {
        try {
            $ret = $errmsg = $this->get_conn_id()->insert($table, $set, array_keys($set));
        } catch (\Exception $e){
            $errmsg = $e->getMessage();
        }
        
        if(empty($ret)) {
            $this->_trans_status = FALSE;
            return $this->display_error(array('Error Number: INSERT INTO ' . $table, json_encode($set),  $errmsg));
        }
        
        return TRUE;
    }
    
    function _replace($table, $keys, $values)
    {
        return "REPLACE INTO ".$table." (".implode(', ', $keys).") VALUES (".implode(", ", $values).")";
    }
    
    function _update($table, $values, $where, $orderby = array(), $limit = FALSE)
    {
        $limit = ( ! $limit) ? '' : ' LIMIT '.$limit;
        $orderby = (count($orderby) >= 1)?' ORDER BY '.implode(", ", $orderby):'';

        $sql_where = ($where != '' AND count($where) >=1) ? implode(" ", $where) : '';
        $sql_where .= $orderby.$limit;
        
        try {
            $ret = $errmsg = $this->get_conn_id()->update($table, array_keys($values), array_values($values), $sql_where);
        } catch (\Exception $e){
            $errmsg = $e->getMessage();
        }
        
        if(empty($ret)) {
            $this->_trans_status = FALSE;
            return $this->display_error(array( 'Error Number: UPDATE ' . $table, "SET: " . json_encode($values) , " WHERE " . $sql_where, $errmsg));
        }
        
        return TRUE;
    }
    
    function _delete($table, $where = array(), $like = array(), $limit = FALSE)
    {
        $conditions = '';
        
        if (count($where) > 0 OR count($like) > 0)
        {
            $conditions .= implode("\n", $this->ar_where);

            if (count($where) > 0 && count($like) > 0)
            {
                $conditions .= " AND ";
            }
            $conditions .= implode("\n", $like);
        }

        $limit = ( ! $limit) ? '' : ' LIMIT '.$limit;
        
        try {
            $ret = $errmsg = $this->get_conn_id()->delete($table, $conditions.$limit);
        } catch (\Exception $e){
            echo "======".$e->getMessage();exit;
            $errmsg = $e->getMessage();
        }
        
        if(empty($ret)) {
            $this->_trans_status = FALSE;
            return $this->display_error(array( 'Error Number: DELETE FROM ' . $table, "WHERE $conditions $limit ", $errmsg));
        }
        
        return TRUE;
    }
    
    function _limit($sql, $limit, $offset)
    {
        if ($offset == 0){
            $offset = '';
        } else {
            $offset .= ", ";
        }

        return $sql."LIMIT ".$offset.$limit;
    }
}
