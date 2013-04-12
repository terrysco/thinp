<?php
/**
 * @file
 * thinp framework common function implementation.
 */
if (!defined('IN_THINP')) exit('You can not access this file directly!');

/**
 * retrieve the global configuration file
 * @see config.inc.php
 */
function thinp_load_config() {
    $config = THINP_ROOT . 'conf.inc.php';
    if (file_exists($config))
        return require_once $config;
    else 
        exit('please check out whether you have a configuration file, if not, please copy one from the sample.');
}

/**
 * a helper function for loading library files.
 */
function thinp_load_library($l, $conf) {
    if (isset($conf['library']) && $conf['library'])
        $l = $conf['library'];
    $library = THINP_LIBS . $l . '.inc.php';
    if (file_exists($library)) {
        require $library;
    }
}

/**
 * a simple wrapper of load thinp libraries.
 * example: l('db')->get('users');
 * @param $l: the library name, like 'db', 'redis', etc.
 * you can specify a library init function in the configuration file 
 * by assigning a function name to the key 'init', if not, thinp will
 * invoke '{$library_name}_init'
 */
function l($l) {
    global $conf;
    static $libraries_enabled;
    if (!isset($libraries_enabled[$l])) {
        if (isset($conf[$l])) {
            thinp_load_library($l, $conf[$l]);
            $func = $l . '_init';
            if (isset($conf[$l]['init']) && $conf[$l]['init'])
                $func = $conf[$l]['init'];
            $libraries_enabled[$l] = call_user_func($func, $conf[$l]);
        } else 
            thinp_error(THINP_ERROR_LIBRARY_NOT_CONFIGURED);
    }
    return $libraries_enabled[$l];
}

/**
 * default redis init process.
 */
function redis_init($conf) {
    $redis = new Redis();
    $redis->connect($conf['host'], $conf['port']);
    if (isset($conf['auth']) && $conf['auth'])
        $redis->auth($conf['auth']);
    if (isset($conf['database']) && is_numeric($conf['database']))
        $redis->select($conf['database']);
    return $redis;
}

/**
 * default database init process.
 */
function db_init($conf) {
    $db = new ThinpMysqli($conf['host'], $conf['username'], $conf['passwd'], $conf['database']);
    return $db;
}

/**
 * response an error to the client.
 * please make sure the debug mode is opening.
 * @param code: the custom error code.
 * you can define any error code and error message in config.inc.php
 */
function thinp_error($code) {
    global $conf;
    $errors = $conf['error'];
    switch ($code) {
    case THINP_ERROR_PAGE_NOT_FOUND:
        header('HTTP/1.1 404 Not Found');
    default:
        break;
    }
    if (defined('THINP_DEBUG') && isset($errors[$code]))
        echo $errors[$code];
    else
        echo 'Unknown error';
}

/**
 * a helper function for loading a specific module
 * we put all the loaded modules into a static array named modules,
 * so never mind if you load a module more than once.
 * @param $module: the name of module
 */
function thinp_load_module($module) {
    static $modules;
    $module_file = THINP_MODULES . $module . '.php';
    if (isset($modules[$module]))
        return true;
    if (!file_exists($module_file))
        thinp_error(THINP_ERROR_MODULE_NOT_EXISTS);

    $modules[$module] = $module_file;
    require $module_file;
}

/**
 * once a module is loaded, thinp will look for a proper function to
 * handle the request.
 * for instance, if a client request /module/handler/abc/123, thinp
 * will open module.php file and invoke a function named "module_handler()"
 * "abc" and 123 are treated as parameters and passed to that function as well.
 */
function thinp_process_handler($module, $handler, $params) {
    $func = $module . '_' .$handler;
    if (function_exists($func)) {
        return call_user_func_array($func, $params);
    } else {
        thinp_error(THINP_ERROR_HANDLER_NOT_EXISTS);
    }
}

/**
 * a simple wrapper for retrieving http post data.
 */
function thinp_get_post($name, $default = null) {
    return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
}

function thinp_get_query($name, $default = null) { 
    return isset($_GET[$name]) ? trim($_GET[$name]) : $default;
}

function cache_get($name, $default = null) {
    $value = l('redis')->get("cache:$name");
    return $value === false ? $default : $value;
}

/**
 * cache a string in redis.
 * please make sure you have to install redis and phpredis extension first.
 */
function cache_set($name, $value, $expired = 0) {
    if ($expired)
        l('redis')->setex("cache:$name", $expired, $value);
    else
        l('redis')->set("cache:$name", $value);
}

/**
 * a helper function to get the base url of your apps.
 * for example: once you want to generate a url pointing to a specific image,
 * base_url().'image/icon.png';
 */
function base_url() {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
    $base_url .= '://'. $_SERVER['HTTP_HOST'] .'/';

    if ($dir = trim(dirname($_SERVER['SCRIPT_NAME']), '\,/')) {
      $base_url .= "$dir/";
    }
    return $base_url;
}
