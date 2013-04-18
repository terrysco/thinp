<?php
if (!defined('IN_THINP')) exit('You can not access this file directly!');

// thinp libraries directory
define('THINP_LIBS', THINP_ROOT . 'libs/');

// thinp modules directory
define('THINP_MODULES', THINP_ROOT . 'modules/');

require(THINP_LIBS . 'func.inc.php');
$conf = thinp_load_config();

/**
 * unset all unnecessary global variables for the sake of security.
 */
if (ini_get('register_globals')) {
    $allowed = array('_ENV', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_REQUEST', 'GLOBALS');
    foreach ($GLOBALS as $key => $value) {
        if (!array_key_exists($key, $allowed)) {
            unset($GLOBALS[$key]);
        }
    }
}

function bootstrap_dispatch() {
    global $conf;
    $act = get_query('act', "{$conf['app']['default_module']}/{$conf['app']['default_handler']}");
    $routes = explode('/', $act);
    $module = array_shift($routes);

    thinp_load_module($module);

    if (!empty($routes)) {
        $handler = array_shift($routes);
        if (!empty($conf['app']['url_suffix'])) {
            $suffix = $conf['app']['url_suffix'];
            $handler = substr($handler, 0, strrpos($handler, $suffix)); 
        }
    } else
        $handler = $conf['app']['default_handler'];
    return thinp_process_handler($module, $handler, $routes);
}
