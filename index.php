<?php
/**
 * @file
 * The PHP page that serves all http requests.
 *
 * The routines here dispatch control to the appropriate handler
 * All Thinp code is released under the GNU General Public License.
 *
 * @author
 * terrysco <terrysco@gmail.com>
 */

/**
 * you'd better define the IN_THINP constant in the scripts
 * if you don't want anyone to access them directly.
 */
define('IN_THINP', 1);

// debug mode switch
define('THINP_DEBUG', 1);

define('THINP_ROOT', dirname(__FILE__) . '/');

require(THINP_ROOT . 'bootstrap.php');

if ($return = bootstrap_dispatch()) {
    // basically, thinp framework focus on a mobile back-end server
    // so we always response a set of json data to the client
    header('Content-type: text/json, charset=utf-8');
    echo json_encode($return);
}
