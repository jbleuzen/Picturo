<?php

include 'bootstrap.php';


Helper::loadConfig();


// Routing
$router = new \Bramus\Router\Router();

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
  if ($config['private'] == true && !isset($_SESSION['username'])) {
    Helper::redirect('/login');
  }

  $controller = new Thumbnail($width, $height, $path);
  $controller->serve();
});

$router->get('/(.*)\.(.*)$', function($path, $extension) {
  if ($config['private'] == true && !isset($_SESSION['username'])) {
    Helper::redirect('/login');
  }

  $controller = new Picturo();
  $controller->displayPicture($path, $extension);
});

$router->get('/(.*)/page([0-9]*)', function($path, $page = 1) {
  if ($config['private'] == true && !isset($_SESSION['username'])) {
    Helper::redirect('/login');
  }

  $controller = new Picturo();
  $controller->displayFolder($path, $page);
});

$router->get('/(.*)', function($path) {
  if ($config['private'] == true && !isset($_SESSION['username'])) {
    Helper::redirect('/login');
  }

  $controller = new Picturo();
  $controller->displayFolder($path, 1);
});

$router->run();

