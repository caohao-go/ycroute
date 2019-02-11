<?php
/**
 * Initialize the database
 * @category    DBLoader
 * @author      caohao
 * @param       string
 * @param       bool    Determines if use phalcon engine
 */
function &DBLoader($params = '')
{
	// Is the config file in the environment folder
    if ( ! file_exists($file_path = APPPATH . '/application/config/database.php'))
    {
        show_error('The configuration file database.php does not exist.');
    }
	
    include($file_path);

    if ( ! isset($db) OR count($db) == 0)
    {
        show_error('No database connection settings were found in the database config file.');
    }
	
	if(empty($params)) {
		$params = 'default';
	}
	
    if (! isset($db[$params]))
    {
        show_error('You have specified an invalid database connection group.');
    }
    
    $config = $db[$params];
    
    require_once(BASEPATH.'/ycdb/YCDB_Driver.php');
    $DB = new YCDB_Driver($config);
    
    if ($DB->autoinit == TRUE) {
		$DB->initialize();
	}
	
    return $DB;
}
