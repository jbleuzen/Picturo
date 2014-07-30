<?php

namespace App\Controllers;

class Auth extends \Core\Controller {

  function __construct($app) {
    parent::__construct($app);
  }

  public function login() {
    if($this->app->getConfig()['private'] === true) {
      // If already logged in will be redirectec to '/'
      if( ! isset($_SESSION['username']) || $_SESSION['username'] == "") {
        $this->app->renderView('login', array());
      }
    }
    $this->app->redirect ('/');
  }

  public function logout() {
    if(isset($_SESSION['username'])) {
      session_destroy();
      $this->app->redirect('/login');
    } else {
      $this->app->redirect('/');
    }
  }

  public function authenticate() {
    global $config;

    $postUsername = $this->getPost('username');
    $postPassword = $this->getPost('password');
    if(isset($postUsername) && isset($postPassword)) {
      if(isset($this->app->getConfig()['private_pass'][$postUsername]) == true && $this->app->getConfig()['private_pass'][$postUsername] == sha1($postPassword)) {
        $_SESSION['username'] = $postUsername;
        $this->app->redirect('/');
      }
      $view_vars['login_error'] = 'Invalid login';
      $view_vars['login_username'] = $postUsername;

      $this->app->renderView('login', $view_vars);
    }
  }

}
