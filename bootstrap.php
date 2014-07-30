<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CORE_DIR', ROOT_DIR .'core/');
define('CONF_DIR', ROOT_DIR .'conf/');
define('WWW_DIR', ROOT_DIR .'www/');
define('CONTENT_DIR', WWW_DIR .'content/');
define('THEMES_DIR', WWW_DIR .'themes/');
define('CACHE_DIR', WWW_DIR .'cache/');

// Autoload vendors
require(ROOT_DIR .'vendor/autoload.php');

function PicturoAutoload($class) {
  spl_autoload_register(function( $class ) {
    $classFile = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
    $classPI = pathinfo( $classFile );
    $classPath = strtolower( $classPI[ 'dirname' ] );

    include_once( $classPath . DIRECTORY_SEPARATOR . $classPI[ 'filename' ] . '.php' );
  });
}

spl_autoload_register('PicturoAutoload');

