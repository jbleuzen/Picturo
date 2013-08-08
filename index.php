<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CONTENT_DIR', ROOT_DIR .'content/');
define('CONTENT_EXT', '.md');
define('CORE_DIR', ROOT_DIR .'core/');
define('PLUGINS_DIR', ROOT_DIR .'plugins/');
define('THEMES_DIR', ROOT_DIR .'themes/');
define('CACHE_DIR', ROOT_DIR .'cache/');

require(ROOT_DIR .'vendor/autoload.php');
require(CORE_DIR .'picturo.php');

$picturo = new Picturo();

?>
