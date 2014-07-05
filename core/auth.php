<?php 
require_once('functions.ban.php');
require_once('functions.password.hash.php');
class Auth {

  public function login() {
    global $config;

    if($config['private']) {
      if (!isset($config['admin_pass']) || empty($config['admin_pass'])) {
        $view_vars['login_error'] = 'Please create an administrator';
        $view_vars['login_username'] = '';
        Helper::renderView('login', $view_vars);
      }
      // If already logged in will be redirectec to '/'
      if( ! isset($_SESSION['username']) || $_SESSION['username'] == "") {
        Helper::renderView('login', array());
      }
    }
    Helper::redirect ('/');
  }

  public function logout() {
    global $config;
    if($config['private']) {
      if(isset($_SESSION['username'])) {
        $_SESSION = array();
        session_destroy();
        Helper::redirect('/login');
      } else {
        Helper::redirect('/');
      }
    }
     Helper::renderNotFound(); 
  }

  public function authenticate() {
    global $config;

    if (!$config['private']) Helper::redirect('/');

    if (!isset($config['admin_pass']) || empty($config['admin_pass'])) {
        $postUsername = $_POST['username'];
        $postPassword = $_POST['password'];
        if(!empty($postPassword)) {
          $pass = create_hash($postPassword);
          $config_file = file_get_contents(CONF_DIR.'config.php');
          $config_file = str_replace('?>', '$config[\'admin_pass\'][\''.$postUsername.'\'] = \''.$pass.'\';'."\n?>", $config_file);
          if(!file_put_contents(CONF_DIR.'config.php', $config_file)) {
            $view_vars['login_error'] = 'Failed to open stream. Permission denied ! Please create a new user by hand.'."\n".'Your pass : '.$pass;
            $view_vars['login_username'] = $postUsername;
            Helper::renderView('login', $view_vars);
          }
          $view_vars['login_success'] = 'New administrator create successfully. Please enter password again.';
          $view_vars['login_username'] = $postUsername;
          Helper::renderView('login', $view_vars);
        } else {
          $view_vars['login_error'] = 'Please create a new user';
          $view_vars['login_username'] = $postUsername;
          Helper::renderView('login', $view_vars);
        }
    }

    if (!ban_canLogin()) { 
        ban_loginFailed();
        $view_vars['login_error'] = 'Invalid login';
        $view_vars['login_username'] = $postUsername;
      } else { 
        $postUsername = $_POST['username'];
        $postPassword = $_POST['password'];
        if (isset($config['admin_pass'][$postUsername]) AND validate_password($postPassword, $config['admin_pass'][$postUsername])){// AND $user->actif == 1 AND $user->role_id != PROFIL_BANNED) {
          ban_loginOk();
          $_SESSION['isAdmin'] = $postUsername;
          $_SESSION['username'] = $postUsername;
          $_SESSION['domain'] =  session_domain;
          Helper::redirect('/');
        }elseif (isset($config['private_pass'][$postUsername]) AND validate_password($postPassword, $config['private_pass'][$postUsername])){// AND $user->actif == 1 AND $user->role_id != PROFIL_BANNED) {
          ban_loginOk();
          $_SESSION['isAdmin'] = array();
          $_SESSION['username'] = $postUsername;
          $_SESSION['domain'] =  session_domain;
          Helper::redirect('/');
        }else{ 
          ban_loginFailed(); 
          $view_vars['login_error'] = 'Invalid login or password';
          $view_vars['login_username'] = $postUsername;
        }
        Helper::renderView('login', $view_vars);
      }
    Helper::cleanHeaders(); 
  }

  public function createNewUser(){
    global $config;

    if (!$config['private']) Helper::renderNotFound();

    $pict = new Picturo();
    $aFolders = $pict->displayFoldersNames('/');

    $view_vars['folders'] = $aFolders;

    if (isset($_POST['username']) && isset($_POST['password']) ) {
      $postUsername = $_POST['username'];
      $postPassword = $_POST['password'];
    } else {
      $postUsername = '';
      $postPassword = '';
    }
    $view_vars['users'] = isset($config['private_pass']) ? $config['private_pass'] : array();
    
    if (isset($config['private_pass'][$postUsername])) {
      $view_vars['login_error'] = 'Username already used.';
      $view_vars['login_username'] = $postUsername;
      Helper::renderView('login', $view_vars);
    }

    if ( ( !isset($config['private_pass']) || empty($config['private_pass']) || !isset($config['private_pass'][$postUsername]) ) && !empty($postUsername) && Helper::isAdmin($_SESSION['username']) && !isset($config['admin_pass'][$postUsername])) {
        
        if(!empty($postPassword)) {
          $pass = create_hash($postPassword);
          $config_file = file_get_contents(CONF_DIR.'config.php');
          $config_file = str_replace('?>', '$config[\'private_pass\'][\''.$postUsername.'\'] = \''.$pass.'\';'."\n?>", $config_file);
          if(!file_put_contents(CONF_DIR.'config.php', $config_file)) {
            $view_vars['login_error'] = 'Failed to open stream. Permission denied ! Please create a new user by hand.'."\n".'Your pass : '.$pass;
            $view_vars['login_username'] = $postUsername;
            Helper::renderView('login', $view_vars);
          }
          Helper::Info('New user create successfully.');
          Helper::redirect('/createUser');
        } else {
          $view_vars['login_error'] = 'Please create a new user';
          $view_vars['login_username'] = $postUsername;
          Helper::renderView('login', $view_vars);
        }
    } elseif (Helper::isAdmin($_SESSION['username'])) {
      $view_vars['login_error'] = 'Please create a new user';
      $view_vars['login_username'] = '';
      Helper::renderView('login', $view_vars);
    } else {
      Helper::renderNotFound();
    }
    Helper::cleanHeaders(); 
  }

  public function manageRights() {
    global $config;

    if (!$config['private']) Helper::renderNotFound();

    $config_file = file_get_contents(CONF_DIR.'config.php');

    if (isset($config['manageFoldersRights'])) {
      $file = explode("\n", $config_file);
      $search = '$config[\'manageFoldersRights\']';
      $filter = array_filter($file, function ($element) use ($search,$file) { 
        if (strpos($element, $search) !== false) {
          return $element;
        } 
      } );
      $k = key($filter);
      $file[$k] = '$config[\'manageFoldersRights\'] = '.var_export($_POST['manageFoldersRights'],true).';';
      $file[$k] = str_replace("\n", '', $file[$k]);
      $config_file = implode("\n", $file);
    } else {
       $manageFoldersRights = var_export($_POST['manageFoldersRights'],true);
       $manageFoldersRights = str_replace("\n", '', $manageFoldersRights);
       $config_file = str_replace('?>', '$config[\'manageFoldersRights\'] = '.$manageFoldersRights.';'."\n?>", $config_file);
    }
    if(!file_put_contents(CONF_DIR.'config.php', $config_file)) {
      $view_vars['login_error'] = 'Failed to open stream. Permission denied !';
      Helper::renderView('login', $view_vars);
    }
    Helper::Info('Rights changed successfully.');
    Helper::redirect('/createUser');
  }
  
}
