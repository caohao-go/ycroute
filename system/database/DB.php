<?php
/**
 * Initialize the database
 * @category    Database
 * @author      caohao
 * @param       string
 * @param       bool    Determines if use phalcon engine
 */
function &DB($params = '', $use_phalcon = true)
{
    // Is the config file in the environment folder
    if ( ! file_exists($file_path = APPPATH.'/application/config/database.php'))
    {
        show_error('The configuration file database.php does not exist.');
    }

    include($file_path);

    if ( ! isset($db) OR count($db) == 0)
    {
        show_error('No database connection settings were found in the database config file.');
    }
    
    if ($params != '')
    {
        $active_group = $params;
    }

    if ( ! isset($active_group) OR ! isset($db[$active_group]))
    {
        show_error('You have specified an invalid database connection group.');
    }
    
    $params = $db[$active_group];
    if($use_phalcon) {
        require_once(BASEPATH.'/database/DB_phalcon_driver.php');
        require_once(BASEPATH.'/database/DB_phalcon_active_rec.php');
        require_once(BASEPATH.'/database/drivers/mysql/mysql_phalcon_driver.php');
        
        // Instantiate the DB adapter
        $DB = new Mysql_phalcon_driver($params);
        
        if ($DB->autoinit == TRUE)
        {
            $DB->initialize();
        }
        
        if (isset($params['stricton']) && $params['stricton'] == TRUE)
        {
            $DB->simple_query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
        }
        
    } else {
        require_once(BASEPATH.'/database/DB_driver.php');
        require_once(BASEPATH.'/database/DB_active_rec.php');
        require_once(BASEPATH.'/database/drivers/mysqli/mysqli_driver.php');
    
        // Instantiate the DB adapter
        $DB = new CI_DB_mysqli_driver($params);
        if ($DB->autoinit == TRUE)
        {
            $DB->initialize();
        }
    
        if (isset($params['stricton']) && $params['stricton'] == TRUE)
        {
            $DB->query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
        }
    }
    return $DB;
}
