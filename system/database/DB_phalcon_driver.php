<?php
/**
 * DB_phalcon_driver class
 *
 * @package        SuperCI
 * @subpackage    database
 * @category      DB_phalcon_driver
 * @author        caohao
 */
class DB_phalcon_driver {
    var $username;
    var $password;
    var $hostname;
    var $database;
    var $char_set        = 'utf8';
    var $dbcollat        = 'utf8_general_ci';
    var $autoinit        = FALSE; // Whether to automatically initialize the DB
    var $port            = '';
    var $pconnect        = FALSE;
    var $conn_id        = FALSE;
    var $result_id        = FALSE;
    var $db_debug        = FALSE;
    var $trans_enabled    = TRUE;
    var $_trans_start_flag = FALSE;
    var $_trans_status    = TRUE; // Used with transactions to determine if a rollback should occur

    // Private variables
    var $_reserved_identifiers    = array('*'); // Identifiers that should NOT be escaped
    
    //__construct, params : connection settings.
    function __construct($params)
    {
        if (is_array($params))
        {
            foreach ($params as $key => $val)
            {
                $this->$key = $val;
            }
        }
    }
    
    //Initialize Database Settings
    function initialize()
    {
        // If an existing connection resource is available
        // there is no need to connect and select the database
        if (is_resource($this->conn_id) OR is_object($this->conn_id))
        {
            return TRUE;
        }
        
        $config = array();
        $config["host"] = $this->hostname;
        $config["username"] = $this->username;
        $config["password"] = $this->password;
        $config["dbname"] = $this->database;
        
        if($this->port) {
            $config["port"] = $this->port;
        }
        
        if($this->pconnect) {
            $config["persistent"] = true;
        }
        
        if($this->char_set) {
            $config["charset"] = $this->char_set;
        }
        
        try{
            $this->conn_id = new \Phalcon\DB\Adapter\Pdo\Mysql($config);
            
        } catch (\Exception $e){
            $errmsg = $e->getMessage();
        }
        
        if (!$this->conn_id)
        {
            log_message('error', 'Unable to connect to the database' . $errmsg);
            return $this->display_error('db_unable_to_connect');
        }
        
        //we set the character set
        if ( ! $this->db_set_charset($this->char_set, $this->dbcollat))
        {
            return FALSE;
        }
        
        return TRUE;
    }
    
    //Set client character set
    function db_set_charset($charset, $collation)
    {
        if (! $this->simple_query("SET NAMES '$charset'"))
        {
            log_message('error', 'Unable to set database connection charset: '.$this->char_set);
            return $this->display_error('db_unable_to_set_charset' . $this->char_set);
        }

        return TRUE;
    }
    
    function get_conn_id() {
        if ( ! $this->conn_id) {
            $this->initialize();
        }
        
        return $this->conn_id;
    }
    
    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query.  Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @access    public
     * @param    string    An SQL query string
     * @param    array    An array of binding data
     * @return    mixed
     */
    function query($sql, $binds = FALSE)
    {
        if ($sql == '')
        {
            if ($this->db_debug)
            {
                log_message('error', 'Invalid query: '.$sql);
            }
            
            return $this->display_error('db_invalid_query');
        }
        
        // Run the Query
        $error_msg = "";
        $this->result_id = $this->simple_query($sql, $error_msg);
        if (!$this->result_id)
        {
            $this->_trans_status = FALSE;
            if ($this->db_debug)
            {
                log_message('error', 'Query error: '.$error_msg);
            }
            
            return $this->display_error( array( 'Error Number: 1', $error_msg, $sql ));
        }
        
        // Was the query a "write" type? If so we'll simply return true
        if ($this->is_write_type($sql) === TRUE)
        {
            return TRUE;
        }

        // Load and instantiate the result driver
        include_once(BASEPATH.'/database/drivers/mysql/mysql_phalcon_result.php');
        $RES = new Mysql_phalcon_result();
        $RES->result_id = $this->result_id;
        
        return $RES;
    }
    
    /**
     * Simple Query
     * This is a simplified version of the query() function.  Internally
     * we only use it when running transaction commands since they do
     * not require all the features of the main query() function.
     *
     * @access    public
     * @param    string    the sql query
     * @return    mixed
     */
    function simple_query($sql, & $errmsg = '')
    {
        if (!$this->conn_id)
        {
            $this->initialize();
        }
        
        $sql = $this->_prep_query($sql);
        try {
            if ($this->is_write_type($sql) === TRUE)
            {
                $result = $this->conn_id->execute($sql);
            } else {
                $result = $this->conn_id->query($sql);
            }
            
            return $result;
        } catch (\Exception $e){
            $errmsg = $e->getMessage();
            return FALSE;
        }
    }
    
    /**
     * Disable Transactions
     * This permits transactions to be disabled at run-time.
     *
     * @access    public
     * @return    void
     */
    function trans_off()
    {
        $this->trans_enabled = FALSE;
    }
    
    /**
     * Start Transaction
     *
     * @access    public
     * @return    void
     */
    function trans_start()
    {
        if ( ! $this->trans_enabled)
        {
            return FALSE;
        }
        
        $this->_trans_start_flag = TRUE;
        
        $this->get_conn_id()->begin();
    }
    
    /**
     * Complete Transaction
     *
     * @access    public
     * @return    bool
     */
    function trans_complete()
    {
        $this->_trans_start_flag = FALSE;
        
        if ( ! $this->trans_enabled)
        {
            return FALSE;
        }
        
        // The query() function will set this flag to FALSE in the event that a query failed
        if ($this->_trans_status === FALSE)
        {
            $this->get_conn_id()->rollback();
            log_message('debug', 'DB Transaction Failure');
            return FALSE;
        }
        
        $this->get_conn_id()->commit();
        return TRUE;
    }
    
    //Determines if a query is a "write" type.
    function is_write_type($sql)
    {
        if ( ! preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    //"Smart" Escape String
    function escape($str)
    {
        if (is_string($str))
        {
            $str = $this->escape_str($str);
        }
        elseif (is_bool($str))
        {
            $str = ($str === FALSE) ? 0 : 1;
        }
        elseif (is_null($str))
        {
            $str = 'NULL';
        }

        return $str;
    }

    //Escape LIKE String
    function escape_like_str($str)
    {
        return $this->escape_str($str, TRUE);
    }
    
    /**
     * Tests whether the string has an SQL operator
     *
     * @access    private
     * @param    string
     * @return    bool
     */
    function _has_operator($str)
    {
        $str = trim($str);
        if ( ! preg_match("/(\s|<|>|!|=| in|is null|is not null)/i", $str))
        {
            return FALSE;
        }

        return TRUE;
    }

    //Close DB Connection
    function close()
    {
        if (is_resource($this->conn_id) OR is_object($this->conn_id))
        {
            $this->conn_id->close();
        }
        
        $this->conn_id = FALSE;
    }
    
    function display_error($error = '', $swap = '', $native = FALSE)
    {
        if (!$this->db_debug)
        {
            return FALSE;
        }
        
        if($this->_trans_start_flag) {
            // We call this function in order to roll-back queries
            // if transactions are enabled.  If we don't call this here
            // the error message will trigger an exit, causing the
            // transactions to remain in limbo.
            $this->trans_complete();
        }
        
        $heading = "A DB Error Occured";
        if ($native == TRUE)
        {
            $message = $error;
        }
        else
        {
            if(!is_array($error)) {
                $message[] = $error;
            } else {
                $message = $error;
            }
        }

        // Find the most likely culprit of the error by going through
        // the backtrace until the source file is no longer in the
        // database folder.
        $trace = debug_backtrace();

        foreach ($trace as $call)
        {
            if (isset($call['file']) && strpos($call['file'], BASEPATH.'/database') === FALSE)
            {
                // Found it - use a relative path for safety
                $message[] = 'Filename: '.str_replace(array(BASEPATH, APPPATH), '', $call['file']);
                $message[] = 'Line Number: '.$call['line'];

                break;
            }
        }
        
        include_once(BASEPATH."/Exceptions.php");
        echo CI_Exceptions::get_instance()->show_error($heading, $message, 'error_db');
        exit;
    }
    
    public function _protect_identifiers($item, $prefix_single = FALSE, $_protect_identifiers = NULL, $field_exists = TRUE)
    {
        if (is_array($item))
        {
            $escaped_array = array();

            foreach ($item as $k => $v)
            {
                $escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v);
            }

            return $escaped_array;
        }
        
        if (strpos($item, '(') !== FALSE)
        {
            return $item;
        }
        
        $alias = '';
        if (strpos($item, ' ') !== FALSE)
        {
            $alias = strstr($item, ' ');
            $item = substr($item, 0, - strlen($alias));
        }
        
        $item = preg_replace('/[\t ]+/', ' ', $item);
        $item = trim($item);
        
        if(! in_array($item, $this->_reserved_identifiers)) {
            if (strpos($item, '.') !== FALSE && substr($item, -1) != '.')
            {
                $item = str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item);
            }
            
            $item = $this->get_conn_id()->escapeIdentifier($item) . $alias;
            return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $item);
        } 
        
        return $item . $alias;
    }
    
    //abstract function
    protected function _reset_select()
    {
    }
}
