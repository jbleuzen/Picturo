<?php

include '../bootstrap.php';

use Core\Application as Application;

use App\Helpers\Picturo as Picturo;
use Core\Helper as Helper;

$app = new Application();

// Routing
$router = new \Bramus\Router\Router();

$router->before('GET|POST', '/.*', function() use ($app) {
  global $config;

  if ($config['private'] == true && !isset($_SESSION['username']) && ! preg_match("@/login@", $_SERVER["REQUEST_URI"])) {
    $app->redirect('/login');
  }
});

$router->get('/login', function() use ($app) {
  $controller = new \App\Controllers\Auth($app);
  $controller->login();
});

$router->post('/login', function() use ($app) {
  $controller = new \App\Controllers\Auth($app);
  $controller->authenticate();
});

$router->get('/logout', function() use ($app) {
  $controller = new \App\Controllers\Auth($app);
  $controller->logout();
});

$router->get('/thumbnail/(\d+)x(\d+)/(.*)', function($width, $height, $path) use ($app) {
  $controller = new \App\Controllers\Thumbnail($app, $width, $height, $path);
  $controller->serve();
});

$router->get('/(.*)\.(.*)$', function($path, $extension) use ($app) {
  $controller = new \App\Controllers\Picturo($app);
  $controller->displayPicture($path, $extension);
});

$router->get('/(.*)/page([0-9]*)', function($path, $page = 1) use ($app) {
  $controller = new \App\Controllers\Picturo($app);
  $controller->displayFolder($path, $page);
});

$router->get('/(.*)', function($path) use ($app) {
  $controller = new \App\Controllers\Picturo($app);
  $controller->displayFolder($path, 1);
});

$router->run();
