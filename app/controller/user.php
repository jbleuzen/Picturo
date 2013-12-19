<?php 

class ControllerUser {

  public function get_login() {
    global $config;

    if($config['private'] === true) {
      // If already logged in will be redirectec to '/'
      if( ! isset($_SESSION['username']) || $_SESSION['username'] == "") {
        HelperConfig::renderView('login', array());
      }
    }
    Helper::redirect ('/');
  }

  public function get_logout() {
    if(isset($_SESSION['username'])) {
      session_destroy();
      HelperConfig::redirect('/login');
    } else {
      HelperConfig::redirect('/');
    }
  }

  public function post_login() {
    global $config;

    $postUsername = $_POST['username'];
    $postPassword = $_POST['password'];
    if(isset($postUsername) && isset($postPassword)) {
      if(isset($config['private_pass'][$postUsername]) == true && $config['private_pass'][$postUsername] == sha1($postPassword)) {
        $_SESSION['username'] = $postUsername;
        HelperConfig::redirect('/');
      }
      $view_vars['login_error'] = 'Invalid login';
      $view_vars['login_username'] = $postUsername;

      HelperConfig::renderView('login', $view_vars);
    }
  }

}
