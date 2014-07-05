<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CONTENT_DIR', ROOT_DIR .'content/');
define('CORE_DIR', ROOT_DIR .'core/');
define('CONF_DIR', ROOT_DIR .'conf/');
define('THEMES_DIR', ROOT_DIR .'themes/');
define('CACHE_DIR', ROOT_DIR .'cache/');
define('session_domain',dirname(__FILE__));

// Autoload
require(ROOT_DIR .'vendor/autoload.php');
function picturo_autoload($class) {
  $class = strtolower($class);
  include CORE_DIR . $class . '.php';
}
spl_autoload_register('picturo_autoload');