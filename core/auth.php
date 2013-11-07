<?php 

class Auth {

  public function login() {
    global $config;

    if($config['private'] === true) {
      // If already logged in will be redirectec to '/'
      if( ! isset($_SESSION['username']) || $_SESSION['username'] == "") {
        Helper::renderView('login', array());
      }
    }
    Helper::redirect ('/');
  }

  public function logout() {
    if(isset($_SESSION['username'])) {
      session_destroy();
      Helper::redirect('/login');
    } else {
      Helper::redirect('/');
    }
  }

  public function authenticate() {
    global $config;

    $postUsername = $_POST['username'];
    $postPassword = $_POST['password'];
    if(isset($postUsername) && isset($postPassword)) {
      if(isset($config['private_pass'][$postUsername]) == true && $config['private_pass'][$postUsername] == sha1($postPassword)) {
        $_SESSION['username'] = $postUsername;
        Helper::redirect('/');
      }
      $view_vars['login_error'] = 'Invalid login';
      $view_vars['login_username'] = $postUsername;

      Helper::renderView('login', $view_vars);
    }
  }

}
