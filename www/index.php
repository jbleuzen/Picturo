<?php

include '../bootstrap.php';


HelperConfig::loadConfig();


// Routing
$router = new \Bramus\Router\Router();

$router->before('GET|POST', '/*', function() {
  global $config;

  if ($config['private'] == true && !isset($_SESSION['username']) && ! preg_match("@/login@", $_SERVER["REQUEST_URI"])) {
    HelperConfig::redirect('/login');
  }
});

$router->get('/login', function() {
  $controller = new ControllerUser();
  $controller->get_login();
});

$router->post('/login', function() {
  $controller = new ControllerUser();
  $controller->post_login();
});

$router->get('/logout', function() {
  $controller = new ControllerUser();
  $controller->get_logout();
});

$router->get('/img/original/(.*)', function($path) {
  $controller = new HelperThumbnail($path);
  $controller->serve();
});

$router->get('/img/(\d+)x(\d+)/(.*)', function($width, $height, $path) {
  $controller = new HelperThumbnail($path);
  $controller->setThumbnailSize($width, $height);
  $controller->serve();
});

$router->get('/(.*)\.(.*)$', function($path, $extension) {
  $controller = new ControllerPicturo();
  $controller->displayPicture($path, $extension);
});

$router->get('/(.*)/page([0-9]*)', function($path, $page = 1) {
  $controller = new ControllerPicturo();
  $controller->displayFolder($path, $page);
});

$router->get('/(.*)', function($path) {
  $controller = new ControllerPicturo();
  $controller->displayFolder($path, 1);
});

$router->run();

