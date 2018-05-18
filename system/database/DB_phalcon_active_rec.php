<?php

/**
 * DB_phalcon_active_rec class
 *
 * @package        SuperCI
 * @subpackage    database
 * @category      DB_phalcon_active_rec
 * @author        caohao
 */
class DB_phalcon_active_rec extends DB_phalcon_driver {

    var $ar_select                = array();
    var $ar_distinct            = FALSE;
    var $ar_from                = array();
    var $ar_join                = array();
    var $ar_where                = array();
    var $ar_like                = array();
    var $ar_groupby                = array();
    var $ar_having                = array();
    var $ar_keys                = array();
    var $ar_limit                = FALSE;
    var $ar_offset                = FALSE;
    var $ar_order                = FALSE;
    var $ar_orderby                = array();
    var $ar_set                    = array();
    var $ar_wherein                = array();
        
    var $ar_no_escape             = array();
    
    public function select($select = '*', $escape = NULL)
    {
        if (is_string($select))
        {
            $select = explode(',', $select);
        }

        foreach ($select as $val)
        {
            $val = trim($val);

            if ($val != '')
            {
                $this->ar_select[] = $val;
            }
        }
        
        return $this;
    }
    
    public function distinct($val = TRUE)
    {
        $this->ar_distinct = (is_bool($val)) ? $val : TRUE;
        return $this;
    }
    
    public function from($from)
    {
        foreach ((array) $from as $val)
        {
            if (strpos($val, ',') !== FALSE)
            {
                foreach (explode(',', $val) as $v)
                {
                    $v = trim($v);

                    $this->ar_from[] = $this->_protect_identifiers($v, TRUE, NULL, FALSE);
                }

            }
            else
            {
                $val = trim($val);
                $this->ar_from[] = $this->_protect_identifiers($val, TRUE, NULL, FALSE);
            }
        }
        
        return $this;
    }
    
    public function join($table, $cond, $type = '')
    {
        if ($type != '')
        {
            $type = strtoupper(trim($type));

            if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER')))
            {
                $type = '';
            }
            else
            {
                $type .= ' ';
            }
        }

        // Strip apart the condition and protect the identifiers
        if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $cond, $match))
        {
            $match[1] = $this->_protect_identifiers($match[1]);
            $match[3] = $this->_protect_identifiers($match[3]);

            $cond = $match[1].$match[2].$match[3];
        }

        // Assemble the JOIN statement
        $join = $type.'JOIN '.$this->_protect_identifiers($table, TRUE, NULL, FALSE).' ON '.$cond;

        $this->ar_join[] = $join;

        return $this;
    }
    
    public function where($key, $value = NULL, $escape = TRUE)
    {
        return $this->_where($key, $value, 'AND ', $escape);
    }
    
    public function or_where($key, $value = NULL, $escape = TRUE)
    {
        return $this->_where($key, $value, 'OR ', $escape);
    }
    
    protected function _where($key, $value = NULL, $type = 'AND ', $escape = NULL)
    {
        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        // If the escape value was not set will will base it on the global setting
        if ( ! is_bool($escape))
        {
            $escape = $this->_protect_identifiers;
        }
        
        foreach ($key as $k => $v)
        {
            $prefix = count($this->ar_where) == 0 ? '' : $type;
            
            if (is_null($v) && ! $this->_has_operator($k))
            {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            }
            
            if ( ! is_null($v))
            {
                if ($escape === TRUE)
                {
                    $k = $this->_protect_identifiers($k, FALSE, $escape);
                    $v = ' '.$this->escape($v);
                }
                
                if ( ! $this->_has_operator($k))
                {
                    $k .= ' = ';
                }
            }
            else
            {
                $k = $this->_protect_identifiers($k, FALSE, $escape);
            }

            $this->ar_where[] = $prefix.$k.$v;
        }
        
        return $this;
    }
    
    public function where_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values);
    }
    
    public function or_where_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, FALSE, 'OR ');
    }
    
    public function where_not_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, TRUE);
    }
    
    public function or_where_not_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, TRUE, 'OR ');
    }
    
    protected function _where_in($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
    {
        if ($key === NULL OR $values === NULL)
        {
            return;
        }

        if ( ! is_array($values))
        {
            $values = array($values);
        }

        $not = ($not) ? ' NOT' : '';

        foreach ($values as $value)
        {
            $this->ar_wherein[] = $this->escape($value);
        }

        $prefix = (count($this->ar_where) == 0) ? '' : $type;

        $where_in = $prefix . $this->_protect_identifiers($key) . $not . " IN (" . implode(", ", $this->ar_wherein) . ") ";
        
        $this->ar_where[] = $where_in;
        
        // reset the array for multiple calls
        $this->ar_wherein = array();
        return $this;
    }
    
    public function like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side);
    }
    
    public function not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT');
    }
    
    public function or_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side);
    }
    
    public function or_not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT');
    }
    
    protected function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '')
    {
        if ( ! is_array($field))
        {
            $field = array($field => $match);
        }

        foreach ($field as $k => $v)
        {
            $k = $this->_protect_identifiers($k);

            $prefix = (count($this->ar_like) == 0) ? '' : $type;

            $v = $this->escape_like_str($v);
            
            if ($side == 'none')
            {
                $like_statement = $prefix." $k $not LIKE '{$v}'";
            }
            elseif ($side == 'before')
            {
                $like_statement = $prefix." $k $not LIKE '%{$v}'";
            }
            elseif ($side == 'after')
            {
                $like_statement = $prefix." $k $not LIKE '{$v}%'";
            }
            else
            {
                $like_statement = $prefix." $k $not LIKE '%{$v}%'";
            }

            $this->ar_like[] = $like_statement;

        }
        return $this;
    }
    
    public function group_by($by)
    {
        if (is_string($by))
        {
            $by = explode(',', $by);
        }

        foreach ($by as $val)
        {
            $val = trim($val);

            if ($val != '')
            {
                $this->ar_groupby[] = $this->_protect_identifiers($val);
            }
        }
        return $this;
    }
    
    public function having($key, $value = '', $escape = TRUE)
    {
        return $this->_having($key, $value, 'AND ', $escape);
    }

    public function or_having($key, $value = '', $escape = TRUE)
    {
        return $this->_having($key, $value, 'OR ', $escape);
    }
    
    protected function _having($key, $value = '', $type = 'AND ', $escape = TRUE)
    {
        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v)
        {
            $prefix = (count($this->ar_having) == 0) ? '' : $type;

            if ($escape === TRUE)
            {
                $k = $this->_protect_identifiers($k);
            }

            if ( ! $this->_has_operator($k))
            {
                $k .= ' = ';
            }

            if ($v != '')
            {
                $v = ' '.$this->escape($v);
            }

            $this->ar_having[] = $prefix.$k.$v;
        }

        return $this;
    }
    
    public function order_by($orderby, $direction = '')
    {
        if (strtolower($direction) == 'random')
        {
            $orderby = ''; // Random results want or don't need a field name
            $direction = $this->_random_keyword;
        }
        elseif (trim($direction) != '')
        {
            $direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), TRUE)) ? ' '.$direction : ' ASC';
        }
        
        if (is_array($orderby))
        {
            $temp = array();
            foreach ($orderby as $part)
            {
                $part = trim($part);
                $part = $this->_protect_identifiers(trim($part));
                $temp[] = $part;
            }

            $orderby = implode(', ', $temp);
        }
        else if ($direction != $this->_random_keyword)
        {
            $orderby = $this->_protect_identifiers($orderby);
        }

        $orderby_statement = $orderby.$direction;

        $this->ar_orderby[] = $orderby_statement;

        return $this;
    }
    
    public function limit($value, $offset = '')
    {
        $this->ar_limit = (int) $value;

        if ($offset != '')
        {
            $this->ar_offset = (int) $offset;
        }

        return $this;
    }
    
    public function offset($offset)
    {
        $this->ar_offset = $offset;
        return $this;
    }
    
    public function set($key, $value = '', $escape = TRUE)
    {
        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }
        
        $this->ar_set = empty($this->ar_set) ? $key : array_merge($this->ar_set, $key);
        return $this;
    }
    
    public function get($table = '', $limit = null, $offset = null)
    {
        if ($table != '')
        {
            $this->from($table);
        }

        if ( ! is_null($limit))
        {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();
        $result = $this->query($sql);
        $this->_reset_select();
        return $result;
    }
    
    public function count_all_results($table = '', $reset = TRUE)
    {
        if ($table != '')
        {
            $this->from($table);
        }

        $sql = $this->_compile_select($this->_count_string . " numrows");
        
        if($reset) {
            $this->_reset_select();
        }
        
        $query = $this->query($sql);
        $result = $query->row_array();
        return intval($result['numrows']);
    }
    
    public function get_where($table = '', $where = null, $limit = null, $offset = null)
    {
        if ($table != '')
        {
            $this->from($table);
        }

        if ( ! is_null($where))
        {
            $this->where($where);
        }

        if ( ! is_null($limit))
        {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();

        $result = $this->query($sql);
        $this->_reset_select();
        return $result;
    }
    
    function _get_ar_from() {
        if ( ! isset($this->ar_from[0]))
        {
            return $this->display_error('db_must_set_table');
        }
        return $this->ar_from[0];
    }
    
    function insert($table = '', $set = NULL, $ignore = false)
    {
        if ( ! is_null($set))
        {
            $this->set($set);
        }
        
        if (count($this->ar_set) == 0)
        {
            return $this->display_error('insert_db_must_use_set');
        }
        
        if ($table == '')
        {
            $table = $this->_get_ar_from();
            if($table === FALSE) {
                return FALSE;
            }
        }
        
        $ret = $this->_insert($table, $this->ar_set);
        $this->_reset_write();
        return $ret;
    }
    
    public function replace($table = '', $set = NULL)
    {
        if ( ! is_null($set))
        {
            $this->set($set);
        }

        if (count($this->ar_set) == 0)
        {
            return $this->display_error('db_must_set_table');
        }

        if ($table == '')
        {
            $table = $this->_get_ar_from();
            if($table === FALSE) {
                return FALSE;
            }
        }
        
        $sql = $this->_replace($this->_protect_identifiers($table, TRUE, NULL, FALSE), $this->_protect_identifiers(array_keys($this->ar_set)), $this->escape_str(array_values($this->ar_set)));
        $this->_reset_write();
        return $this->query($sql);
    }
    
    public function update($table = '', $set = NULL, $where = NULL, $limit = NULL)
    {
        if ( ! is_null($set))
        {
            $this->set($set);
        }

        if (count($this->ar_set) == 0)
        {
            return $this->display_error('db_must_set_table');
        }

        if ($table == '')
        {
            $table = $this->_get_ar_from();
            if($table === FALSE) {
                return FALSE;
            }
        }

        if ($where != NULL)
        {
            $this->where($where);
        }
        
        if ($limit != NULL)
        {
            $this->limit($limit);
        }
        
        $ret = $this->_update($table, $this->ar_set, $this->ar_where, $this->ar_orderby, $this->ar_limit);
        $this->_reset_write();
        return $ret;
    }
    
    public function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE)
    {
        if ($table == '')
        {
            $table = $this->_get_ar_from();
            if($table === FALSE) {
                return FALSE;
            }
        }
        elseif (is_array($table))
        {
            foreach ($table as $single_table)
            {
                $this->delete($single_table, $where, $limit, FALSE);
            }

            $this->_reset_write();
            return;
        }

        if ($where != '')
        {
            $this->where($where);
        }

        if ($limit != NULL)
        {
            $this->limit($limit);
        }

        if (count($this->ar_where) == 0 && count($this->ar_wherein) == 0 && count($this->ar_like) == 0)
        {
            return $this->display_error('db_del_must_use_where');
        }

        $ret = $this->_delete($table, $this->ar_where, $this->ar_like, $this->ar_limit);
        $this->_reset_write();
        return $ret;
    }
    
    protected function _compile_select($select_override = FALSE)
    {
        // Write the "select" portion of the query
        if ($select_override !== FALSE)
        {
            $sql = $select_override;
        }
        else
        {
            $sql = ( ! $this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->ar_select) == 0)
            {
                $sql .= '*';
            }
            else
            {
                foreach ($this->ar_select as $key => $val)
                {
                    $no_escape = isset($this->ar_no_escape[$key]) ? $this->ar_no_escape[$key] : NULL;
                    $this->ar_select[$key] = $this->_protect_identifiers($val, FALSE, $no_escape);
                }

                $sql .= implode(', ', $this->ar_select);
            }
        }

        // Write the "FROM" portion of the query
        if (count($this->ar_from) > 0)
        {
            $sql .= "\nFROM ";

            $sql .= $this->_from_tables($this->ar_from);
        }

        // Write the "JOIN" portion of the query
        if (count($this->ar_join) > 0)
        {
            $sql .= "\n";

            $sql .= implode("\n", $this->ar_join);
        }

        // Write the "WHERE" portion of the query
        if (count($this->ar_where) > 0 OR count($this->ar_like) > 0)
        {
            $sql .= "\nWHERE ";
        }

        $sql .= implode("\n", $this->ar_where);

        // Write the "LIKE" portion of the query
        if (count($this->ar_like) > 0)
        {
            if (count($this->ar_where) > 0)
            {
                $sql .= "\nAND ";
            }

            $sql .= implode("\n", $this->ar_like);
        }
        
        // Write the "GROUP BY" portion of the query
        if (count($this->ar_groupby) > 0)
        {
            $sql .= "\nGROUP BY ";

            $sql .= implode(', ', $this->ar_groupby);
        }
        
        // Write the "HAVING" portion of the query
        if (count($this->ar_having) > 0)
        {
            $sql .= "\nHAVING ";
            $sql .= implode("\n", $this->ar_having);
        }
        
        // Write the "ORDER BY" portion of the query
        if (count($this->ar_orderby) > 0)
        {
            $sql .= "\nORDER BY ";
            $sql .= implode(', ', $this->ar_orderby);

            if ($this->ar_order !== FALSE)
            {
                $sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
            }
        }
        
        // Write the "LIMIT" portion of the query
        if (is_numeric($this->ar_limit))
        {
            $sql .= "\n";
            $sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
        }
        
        return $sql;
    }
    
    //Resets the active record values.  Called by the get() function
    protected function _reset_run($ar_reset_items)
    {
        foreach ($ar_reset_items as $item => $default_value)
        {
            $this->$item = $default_value;
        }
    }

    //Resets the active record values.  Called by the get() function
    protected function _reset_select()
    {
        $ar_reset_items = array(
            'ar_select'            => array(),
            'ar_from'            => array(),
            'ar_join'            => array(),
            'ar_where'            => array(),
            'ar_like'            => array(),
            'ar_groupby'        => array(),
            'ar_having'            => array(),
            'ar_orderby'        => array(),
            'ar_wherein'        => array(),
            'ar_no_escape'        => array(),
            'ar_distinct'        => FALSE,
            'ar_limit'            => FALSE,
            'ar_offset'            => FALSE,
            'ar_order'            => FALSE,
        );

        $this->_reset_run($ar_reset_items);
    }
    
    //Resets the active record "write" values.
    protected function _reset_write()
    {
        $ar_reset_items = array(
            'ar_set'        => array(),
            'ar_from'        => array(),
            'ar_where'        => array(),
            'ar_like'        => array(),
            'ar_orderby'    => array(),
            'ar_keys'        => array(),
            'ar_limit'        => FALSE,
            'ar_order'        => FALSE
        );

        $this->_reset_run($ar_reset_items);
    }
}
