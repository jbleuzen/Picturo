<?php

namespace Core;

class Application {

  private $config = array();

  public function __construct() {
    session_start();
    $this->config = $this->loadConfig();
  }

  public function loadConfig() {
    if(!file_exists(CONF_DIR .'config.php')) {
      return array();
    }

    global $config;
    require_once(CONF_DIR .'config.php');

    $defaults = array(
      'site_title' => 'Picturo',
      'base_url' => $this->getBaseUrl(),
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
    return $config;
  }


  public function getConfig() {
    return $this->config;
  }

  public function redirect($url) {
    header("Location: ". $this->config['base_url'] . "$url");
    exit;
  }

  public function renderView($name, $twig_vars = array()) {
    // Load the theme
    \Twig_Autoloader::register();
    $loader = new \Twig_Loader_Filesystem(THEMES_DIR . $this->config['theme']);
    $twig = new \Twig_Environment($loader, $this->config['twig_config']);
    $twig->addExtension(new \Twig_Extension_Debug());
    $base_url = $this->config['base_url'];
    $thumbnail_function = new \Twig_SimpleFunction('picturo_thumbnail', function ($path, $width, $height)  use($base_url) {
      $imgTag = "<img src='" . $base_url . "/thumbnail/" . $width . "x" . $height . "/" . $path ."' width='$width' height='$height'/>";
      echo $imgTag;
    });
    $twig->addFunction($thumbnail_function);
    $twig_vars['view'] = $name;
    $twig_vars['base_url'] = $this->config['base_url'];
    $twig_vars['theme_url'] = $this->config['base_url'] .'/'. basename(THEMES_DIR) .'/'. $this->config['theme'];
    $twig_vars['site_title'] = $this->config['site_title'];
    if(isset($_SESSION['username'])) {
      $twig_vars['username'] = $_SESSION['username'];
    } else {
      $twig_vars['username'] = "";
    }
    $output = $twig->render($name . '.html', $twig_vars);
    echo $output;
    exit;
  }

  /**
   * Helper function to work out the base URL
   *
   * @return string the base url
   */
  private static function getBaseUrl() {
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

