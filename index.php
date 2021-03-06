<?php
Class LessCacheer
{
    /**
     * Any files that are found with find_file are stored here so that
     * any further requestes for the files are just given the path
     * from this array, rather than searching for the file again.
     *
     * @var array
     */
    public static $f = ''; // requested less files to parse
    public static $recache = false; // init of recache
    public static $to_parse = true;
    public static $modules = array();
    public static $extends;
    public static $input = '';
    public static $output = '';
    public static $less_files = array(); // loaded less files
    public static $debug_info = null;
    public static $headers;
    public static $conf = array();
    public static $hook_event = array(
            'hook_init', // Init of hooks events
            'preconfig', // Preconfig event
            'init', // init of LessCacheer
            'import_process', // Mixin import event
            'preparse_process', // Before parsing
            'parse_process', // During parsing
            'after_parse_process', // After parsing
            'caching_process', // Caching event
            'rendering_process', // Rendering event
    );
    public static $init_time;
	
    /**
     * Include paths
     *
     * These are used for finding files on the system. Rather than
     * using PHP's built-in include paths, we just store the paths
     * in this array and use the find_file function to locate it.
     *
     * @var array
     */
    
    static function rglob($pattern, $flags = 0, $path = '')
    {
        if (!$path && ($dir = dirname($pattern)) != '.') {
            if ($dir == '\\' || $dir == '/')
                $dir = '';
            return self::rglob(basename($pattern), $flags, $dir . '/');
        }
        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);
        if (is_array($paths) && is_array($files)) {
            foreach ($paths as $p)
                $files = array_merge($files, self::rglob($pattern, $flags, $p . '/'));
        }
        sort($files);
        return is_array($files) ? $files : array();
    }
    
    static public function rload_class($patterns = array(), $ext = '.inc.php')
    {
        self::$extends = new stdClass(); // array of loaded class
        foreach ((array) $patterns as $pattern) {
            # include any php files which sit in the specified folder
            foreach (self::rglob($pattern) as $include) {
                if (!strpos($include, '/lessify.inc.php')) {
                    include_once $include;
                    $filename                  = basename($include);
                    $dirname                   = dirname($include);
                    $classname                 = basename(str_replace('.inc.php', '', $include));
                    self::$modules[$classname] = $classname;
                    
                    if (class_exists($classname)) {
                        if (!empty(self::$conf[$classname])) {
                            self::$extends->$classname = new $classname(self::$config[$classname]);
                        } else {
                            self::$extends->$classname = new $classname();
                        }
                    }
                }
            }
        }
    }
    
    public static function time_generated() {
        $time = explode(" ", microtime());
        return $time[1] + $time[0];
    }
    
    function __construct($f)
    {
        // init of generation time
        self::$init_time = self::time_generated();
        
        self::$f = $f;
        
        // auto include extends
        $extends = self::rload_class(array('lib/*.inc.php', 'extensions/*.inc.php'));
        
        try {
            foreach(self::$hook_event as $event) { hooks::add($event); }
        }
        
        /**
         * If any errors were encountered
         */
        catch (Exception $e) {
            LessCacheer::$extends->headers->set('_status', 500);
            /** 
             * The message returned by the error 
             */
            $message = $e->getMessage();
            $trace   = $e->getTrace();
            $title   = $trace[0]['function'];
            $file    = $f;
            /** 
             * Load in the error view
             */
            LessCacheer::$extends->headers->send();
            require 'view/less_error.php';
        }
    }
    
}
$less = new LessCacheer($_GET['f']);