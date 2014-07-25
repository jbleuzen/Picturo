<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CORE_DIR', ROOT_DIR .'core/');
define('CONF_DIR', ROOT_DIR .'conf/');
define('WWW_DIR', ROOT_DIR .'www/');
define('CONTENT_DIR', WWW_DIR .'content/');
define('THEMES_DIR', WWW_DIR .'themes/');
define('CACHE_DIR', WWW_DIR .'cache/');


// Autoload
require(ROOT_DIR .'vendor/autoload.php');
function picturo_autoload($class) {
  $class = strtolower($class);
  include CORE_DIR . $class . '.php';
}
spl_autoload_register('picturo_autoload');

