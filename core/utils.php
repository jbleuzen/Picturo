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
    if($config['private'] === true) {
      session_start();
    }

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
      $imgTag = "<img src='" . $base_url . "/thumbnail/" . $width . "x" . $height . "/" . $path ."' width='$width' height='$height'/>";
      echo $imgTag;
    });
    $twig->addFunction($thumbnail_function);
    $download_function = new Twig_SimpleFunction('picturo_download', function ($path)  use($base_url,$config) {
      $imgTag = '<a href="' . $base_url . '/serve/'. $path .'"><img src="'. $base_url  .'/themes/'. $config['theme'].'/img/download.png" alt="Télécharger l\'image" width="48" height="48"/></a>';
      echo $imgTag;
    });
    $twig->addFunction($download_function);
    $twig_vars['view'] = $name;
    $twig_vars['base_url'] = $config['base_url'];
    $twig_vars['theme_url'] = $config['base_url'] .'/'. basename(THEMES_DIR) .'/'. $config['theme'];
    $twig_vars['site_title'] = $config['site_title'];
    if(isset($_SESSION['username'])) {
      $twig_vars['username'] = $_SESSION['username'];
    } else {
      $twig_vars['username'] = "";
    }
    $output = $twig->render($name . '.html', $twig_vars);
    echo $output;
    exit;
  }

  public static function renderNotFound() {
    // If 404 in theme render it
    // Then render default 404 in src/views
    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
    echo "<h1>Not found</h1>";
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

}
