<?php

include 'bootstrap.php';


Helper::loadConfig();


// Routing
$router = new \Bramus\Router\Router();

$router->before('GET|POST', '/.*', function() {
  global $config;

  if ($config['private'] == true && !isset($_SESSION['username']) && ! preg_match("@/login@", $_SERVER["REQUEST_URI"])) {
    Helper::redirect('/login');
  }
});

$router->get('/login', function() {
  $controller = new Auth();
  $controller->login();
});

$router->post('/login', function() {
  $controller = new Auth();
  $controller->authenticate();
});

$router->get('/logout', function() {
  $controller = new Auth();
  $controller->logout();
});

$router->get('/thumbnail/(\d+)x(\d+)/(.*)', function($width, $height, $path) {
  $controller = new Thumbnail($width, $height, $path);
  $controller->serve();
});

$router->get('/(.*)\.(.*)$', function($path, $extension) {
  $controller = new Picturo();
  $controller->displayPicture($path, $extension);
});

$router->get('/(.*)/page([0-9]*)', function($path, $page = 1) {
  $controller = new Picturo();
  $controller->displayFolder($path, $page);
});

$router->get('/(.*)', function($path) {
  $controller = new Picturo();
  $controller->displayFolder($path, 1);
});

$router->run();

