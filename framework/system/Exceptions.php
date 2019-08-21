<?php
/**
 * Exceptions Class https://github.com/caohao-php/ycroute
 *
 * @package        YCRoute
 * @subpackage    Exceptions
 * @category      Exceptions Handler
 * @author        caohao
 */
class CI_Exceptions {
    var $action;
    var $severity;
    var $message;
    var $filename;
    var $line;
    private static $instance;
    /**
     * Nesting level of the output buffering mechanism
     *
     * @var int
     * @access public
     */
    var $ob_level;

    /**
     * List if available error levels
     *
     * @var array
     * @access public
     */
    var $levels = array(
        E_ERROR                 =>    'Error',
        E_WARNING               =>    'Warning',
        E_PARSE                 =>    'Parsing Error',
        E_NOTICE                =>    'Notice',
        E_CORE_ERROR            =>    'Core Error',
        E_CORE_WARNING          =>    'Core Warning',
        E_COMPILE_ERROR         =>    'Compile Error',
        E_COMPILE_WARNING       =>    'Compile Warning',
        E_USER_ERROR            =>    'User Error',
        E_USER_WARNING          =>    'User Warning',
        E_USER_NOTICE           =>    'User Notice',
        E_STRICT                =>    'Runtime Notice'
    );
                    
    public function __construct()
    {
        $this->ob_level = ob_get_level();
    }

    public static function & get_instance() {
        if(empty(CI_Exceptions::$instance)) {
            CI_Exceptions::$instance = new CI_Exceptions();
        }
        return CI_Exceptions::$instance;
    }

    /**
     * Exception Logger
     *
     * This function logs PHP generated error messages
     *
     * @access    private
     * @param    string    the error severity
     * @param    string    the error string
     * @param    string    the error filepath
     * @param    string    the error line number
     * @return    string
     */
    function log_exception($severity, $message, $filepath, $line)
    {
        $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
        log_message($severity, 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);
    }
    
    /**
     * 404 Page Not Found Handler
     *
     * @access    private
     * @param    string    the page
     * @param     bool    log error yes/no
     * @return    string
     */
    function show_404($page = '', $log_error = TRUE)
    {
        $heading = "404 Page Not Found";
        $message = "The page you requested was not found.";

        // By default we log this, but allow a dev to skip it
        if ($log_error)
        {
            log_message('error', '404 Page Not Found --> '.$page);
        }
        
        echo $this->show_error($heading, $message, 'error_404', 404);
        exit;
    }
    
    /**
     * General Error Page
     *
     * This function takes an error message as input
     * (either as a string or an array) and displays
     * it using the specified template.
     *
     * @access    private
     * @param    string    the heading
     * @param    string    the message
     * @param    string    the template name
     * @param     int        the status code
     * @return    string
     */
    function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        $this->set_status_header($status_code);

        $message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

        if (ob_get_level() > $this->ob_level + 1)
        {
            ob_end_flush();
        }
        ob_start();
        include(BASEPATH.'/errors/'.$template.'.php');
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    /**
     * Native PHP error handler
     *
     * @access    private
     * @param    string    the error severity
     * @param    string    the error string
     * @param    string    the error filepath
     * @param    string    the error line number
     * @return    string
     */
    function show_php_error($severity, $message, $filepath, $line)
    {
        $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

        $filepath = str_replace("\\", "/", $filepath);

        // For safety reasons we do not show the full file path
        if (FALSE !== strpos($filepath, '/'))
        {
            $x = explode('/', $filepath);
            $filepath = $x[count($x)-2].'/'.end($x);
        }

        if (ob_get_level() > $this->ob_level + 1)
        {
            ob_end_flush();
        }
        ob_start();
        include(BASEPATH.'/errors/error_php.php');
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }
    
    function set_status_header($code = 200, $text = '')
    {
        $stati = array(
                            200    => 'OK',
                            201    => 'Created',
                            202    => 'Accepted',
                            203    => 'Non-Authoritative Information',
                            204    => 'No Content',
                            205    => 'Reset Content',
                            206    => 'Partial Content',

                            300    => 'Multiple Choices',
                            301    => 'Moved Permanently',
                            302    => 'Found',
                            304    => 'Not Modified',
                            305    => 'Use Proxy',
                            307    => 'Temporary Redirect',

                            400    => 'Bad Request',
                            401    => 'Unauthorized',
                            403    => 'Forbidden',
                            404    => 'Not Found',
                            405    => 'Method Not Allowed',
                            406    => 'Not Acceptable',
                            407    => 'Proxy Authentication Required',
                            408    => 'Request Timeout',
                            409    => 'Conflict',
                            410    => 'Gone',
                            411    => 'Length Required',
                            412    => 'Precondition Failed',
                            413    => 'Request Entity Too Large',
                            414    => 'Request-URI Too Long',
                            415    => 'Unsupported Media Type',
                            416    => 'Requested Range Not Satisfiable',
                            417    => 'Expectation Failed',
                            
                            500    => 'Internal Server Error',
                            501    => 'Not Implemented',
                            502    => 'Bad Gateway',
                            503    => 'Service Unavailable',
                            504    => 'Gateway Timeout',
                            505    => 'HTTP Version Not Supported'
                        );

        if ($code == '' OR ! is_numeric($code))
        {
            die('Status codes must be numeric');
            exit;
        }

        if (isset($stati[$code]) AND $text == '')
        {
            $text = $stati[$code];
        }

        if ($text == '')
        {
            die('No status text available.  Please check your status code number or supply your own message text.');
        }

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

        if (substr(php_sapi_name(), 0, 3) == 'cgi')
        {
            header("Status: {$code} {$text}", true);
        }
        elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0')
        {
            header($server_protocol." {$code} {$text}", true, $code);
        }
        else
        {
            header("HTTP/1.1 {$code} {$text}", true, $code);
        }
    }
}
