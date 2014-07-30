<?php

namespace Core;

class Controller {

  protected $app;

  public function __construct($app) {
    $this->app = $app;
  }

  protected function getParam($name) {
    if(isset($_GET[$name]) && ! empty($_GET[$name])) {
      return $_GET[$name];
    }
  }

 protected function getPost($name) {
    if(isset($_POST[$name]) && ! empty($_POST[$name])) {
      return $_POST[$name];
    }
  }

}
