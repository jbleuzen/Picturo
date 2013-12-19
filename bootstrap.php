<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CONTENT_DIR', ROOT_DIR .'content/');
define('APP_DIR', ROOT_DIR .'app/');
define('CONF_DIR', ROOT_DIR .'conf/');
define('THEMES_DIR', ROOT_DIR .'www/themes/');
define('CACHE_DIR', ROOT_DIR .'cache/');


// Autoload
require(ROOT_DIR .'vendor/autoload.php');

spl_autoload_register(function( $class ) {
  $classFile = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
  // Spliting Uppercase and adding a seperator between words
  $classPath = preg_replace('/(?<!^)([A-Z])/', DIRECTORY_SEPARATOR . '\\1', $classFile);
  $classPath = strtolower($classPath);
 
  include_once(APP_DIR . $classPath . '.php');
});

