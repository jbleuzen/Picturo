<?php

define('ROOT_DIR', realpath(dirname(__FILE__)) .'/');
define('CONTENT_DIR', ROOT_DIR .'content/');
define('CORE_DIR', ROOT_DIR .'core/');
define('THEMES_DIR', ROOT_DIR .'themes/');
define('CACHE_DIR', ROOT_DIR .'cache/');

require(ROOT_DIR .'vendor/autoload.php');
require(CORE_DIR .'picturo.php');

$router = new \Bramus\Router\Router();

$router->get('/login', function() {
   $picturo = new Picturo();
   $picturo->login();
});

$router->post('/login', function() {
   $picturo = new Picturo();
   $picturo->authenticate();
});

$router->get('/logout', function() {
   $picturo = new Picturo();
   $picturo->logout();
});

$router->get('/(.*)/page([0-9]*)', function($path, $page = 1) {
   $picturo = new Picturo();
   $picturo->browse($path, $page);
});

$router->get('/(.*)', function($path) {
   $picturo = new Picturo();
   $picturo->browse($path, 1);
});

$router->run();

?>
