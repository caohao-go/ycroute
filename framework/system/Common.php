<?php
if ( ! function_exists('show_error'))
{
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        include_once(BASEPATH."/Exceptions.php");
        echo CI_Exceptions::get_instance()->show_error($heading, $message, 'error_general', $status_code);
        exit;
    }
}

if ( ! function_exists('show_404'))
{
    function show_404($page = '', $log_error = TRUE)
    {
        include_once(BASEPATH."/Exceptions.php");
        CI_Exceptions::get_instance()->show_404($page, $log_error);
        exit;
    }
}

if ( ! function_exists('log_message'))
{
    function log_message($level = 'error', $msg, $php_error = FALSE)
    {
        $config = Yaf_Registry::get("config");
        $_threshold = $config->application->log_threshold;
        if ($_threshold == 0)
        {
            return;
        }
        
        $level = strtoupper($level);
        $_levels = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');
        
        if ( ! isset($_levels[$level]) OR ($_levels[$level] > $_threshold)) 
        {
            return false;
        }
        
        $message = $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date('Y-m-d H:i:s').' --> ip: '.$_SERVER['REMOTE_ADDR'].' --> '.$msg." --> url: ".$_SERVER['QUERY_STRING'].(empty($_POST)? "" :( " --> post ".  http_build_query($_POST) ))."\n";
        @file_put_contents("/data/app/logs/YCRoute-" . date('Y-m-d') . ".log.wf", $message , FILE_APPEND);
    }
}

if ( ! function_exists('_exception_handler'))
{
    function _exception_handler($severity, $message, $filepath, $line)
    {
        if ($severity == E_STRICT)
        {
            return;
        }
        
        include_once(BASEPATH."/Exceptions.php");
        $_error =CI_Exceptions::get_instance();
        
        if (($severity & error_reporting()) == $severity)
        {
            $_error->show_php_error($severity, $message, $filepath, $line);
        }
        
        $config = Yaf_Registry::get("config");
        $log_threshold = $config->application->log_threshold;
        if ($log_threshold == 0)
        {
            return;
        }
        
        $_error->log_exception($severity, $message, $filepath, $line);
    }
}
