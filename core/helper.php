<?php

class Helper {

  public static function loadConfig() {
    if(!file_exists(CONF_DIR .'config.php')) {
      return array();
    }

    global $config;
    require_once(CONF_DIR .'config.php');

    $defaults = array(
      'site_title' => 'Picturo',
      'base_url' => self::base_url(),
      'theme' => 'default',
      'date_format' => 'jS M Y',
      'twig_config' => array('cache' => false, 'autoescape' => false, 'debug' => false),
      'items_per_page' => 15,
      'private' => false
    );

    if(is_array($config)) {
      $config = array_merge($defaults, $config);
    } else {
      $config = $defaults;
    }

    // Init sessions
    session_start();

    return $config;
  }

  public static function redirect($url) {
    global $config;

    header("Location: ". $config['base_url'] . "$url");
    exit;
  }

  public static function renderView($name, $twig_vars = array()) {
    global $config;

    // Load the theme
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem(THEMES_DIR . $config['theme']);
    $twig = new Twig_Environment($loader, $config['twig_config']);
    $twig->addExtension(new Twig_Extension_Debug());
    $base_url = $config['base_url'];
    $thumbnail_function = new Twig_SimpleFunction('picturo_thumbnail', function ($path, $width, $height)  use($base_url) {
      $path = (substr($path, 0,1) == '/' ? substr($path,1) : $path);
      $imgTag = '<img src="' . $base_url . '/thumbnail/' . $width . 'x' . $height . '/' . $path .'" />';
      echo $imgTag;
    });
    $twig->addFunction($thumbnail_function);
    $preview_function = new Twig_SimpleFunction('picturo_preview', function ($path,$width,$height,$t,$w,$h,$x,$y,$d)  use($base_url) {
      $imgTag = '<img src="' . $base_url . '/preview/' . $width . 'x' . $height . '/' . $path .'?transform='.$t.'&amp;w='.$w.'&amp;h='.$h.'&amp;x='.$x.'&amp;y='.$y.'&amp;d='.$d .'" />';
      echo $imgTag;
    });
    $twig->addFunction($preview_function);
    $download_function = new Twig_SimpleFunction('picturo_download', function ($path)  use($base_url,$config) {
      $imgTag = '<a href="' . $base_url . '/serve/'. $path .'"><img src="'. $base_url  .'/themes/'. $config['theme'].'/img/download.png" alt="Télécharger l\'image" width="48" height="48"/></a>';
      echo $imgTag;
    });
    $twig->addFunction($download_function);
    $input_function = new Twig_SimpleFunction('picturo_checkbox', function ($user,$foldername)  use($config) {
      $input = '<input type="checkbox" name="manageFoldersRights['.$user.'][]" value="'.$foldername.'"'.( (isset($config['manageFoldersRights']) && isset($config['manageFoldersRights'][$user])) ? (in_array($foldername, $config['manageFoldersRights'][$user]) ? ' checked="checked"' : ''): '').' id="'.$foldername.'-'.$user.'"><label for="'.$foldername.'-'.$user.'">'.$foldername.'</label><br/>';
      echo $input;
    });
    $twig->addFunction($input_function);
    $twig_vars['view'] = $name;
    $twig_vars['base_url'] = $config['base_url'];
    $twig_vars['theme_url'] = $config['base_url'] .'/'. basename(THEMES_DIR) .'/'. $config['theme'];
    $twig_vars['site_title'] = $config['site_title'];
    $twig_vars['msg'] = self::Display();
    if(isset($_SESSION['username'])) {
      $twig_vars['username'] = $_SESSION['username'];
    } else {
      $twig_vars['username'] = "";
    }
    if(isset($_SESSION['isAdmin'])) {
      $twig_vars['admin'] = Helper::isAdmin($_SESSION['isAdmin']);
    } else {
      $twig_vars['admin'] = false;
    }
    $output = $twig->render($name . '.html', $twig_vars);
    echo $output;
    exit;
  }

  public static function renderNotFound($twig_vars=array()) {
    // If 404 in theme render it
    self::renderView('error',$twig_vars);
  }

  //-- Private functions -------------------------------------------------------------------------- 

  /**
   * Helper function to work out the base URL
   *
   * @return string the base url
   */
  private static function base_url() {
    global $config;
    if(isset($config['base_url']) && $config['base_url']) return $config['base_url'];

    $url = '';
    $request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
    $script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
    if($request_url != $script_url)  {
      $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');
    }

    $protocol = self::getProtocol();
    return rtrim(str_replace($url, '', $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), '/');
  }

  /**
   * Tries to guess the server protocol. Used in base_url()
   *
   * @return string the current protocol
   */
  protected static function getProtocol() {
    $protocol = 'http';
    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
      $protocol = 'https';
    }
    return $protocol; 
  }

  /**
   * Méthode qui mémorise un message d'information
   *
   * @param msg     message d'info
   * @return  boolean   true (pas d'erreur)
   * @author  Stephane F
   **/
  public static function Info($msg='') {
    $_SESSION['info'] = $msg;
    return true;
  }

  /**
   * Méthode qui mémorise un message d'erreur
   *
   * @param msg     message d'info
   * @return  boolean   false (erreur)
   * @author  Stephane F
   **/
  public static function Error($msg='') {
    $_SESSION['error'] = $msg;
    return false;
  }

  /**
   * Méthode qui affiche le message en mémoire
   *
   * @param null
   * @return  stdout
   * @author  Stephane F
   **/
  public static function Display() {
    $return = '';
    if(isset($_SESSION['error']) AND !empty($_SESSION['error']))
      $return = '<p id="msg" class="notification error">'.$_SESSION['error'].'</p>';
    elseif(isset($_SESSION['info']) AND !empty($_SESSION['info']))
      $return = '<p id="msg" class="notification success">'.$_SESSION['info'].'</p>';
    unset($_SESSION['error']);
    unset($_SESSION['info']);
    return $return;
  }

  /**
   * Méthode qui empeche de mettre en cache une page
   *
   * @return  stdio
   * @author  Stephane F.
   **/
  public static function cleanHeaders() {
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT');
    @header('Cache-Contrôle: no-cache, must-revalidate, max-age=0');
    @header('Cache: no-cache');
    @header('Pragma: no-cache');
    @header('Content-Type: text/html; charset=UTF8');
  }

  public static function checkSession($path) {
    global $config;
    if ($path != 'login'){
      if ($config['private'] && !isset($_SESSION['username'])) {
        self::redirect('/login');
      }
      # Test sur le domaine et sur l'identification
      if((isset($_SESSION['domain']) AND $_SESSION['domain']!=session_domain) OR (!isset($_SESSION['username']) OR $_SESSION['username']=='') && $config['private'] === true){
        self::redirect('/login');
      }
    }
  }

  public static function isAdmin($user) {
    global $config;

    if (!$config['private']) return true;

    if (!is_array($_SESSION['isAdmin']) && isset($config['admin_pass'][$_SESSION['isAdmin']]) && $user == $_SESSION['username']) return true;
    return false;
  }

}
