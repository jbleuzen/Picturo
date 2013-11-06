<?php

/**
 * Picturo
 *
 * @author Gilbert Pellegrom
 * @link http://pico.dev7studios.com
 * @license http://opensource.org/licenses/MIT
 * @version 0.6.2
 */
class Picturo {

  private $settings;

  private $current_page = 0;

  private $foldersPath = array();

  private $folders = array();

  private $imagesPath = array();

  private $images = array();

  private $breadcrumb = array();

  /**
   * The constructor carries out all the processing in Picturo.
   * Does URL routing, Markdown processing and Twig processing.
   */
  public function __construct() {

    session_start();

    // Load the settings
    $this->settings = $this->get_config();
  }

  public function login() {
    if($this->settings['private'] == true) {
      // If already logged in will be redirectec to '/'
      if( ! isset($_SESSION['username']) || $_SESSION['username'] == "") {
        $this->render_view('login', array());
      }
    }
    $this->redirect ('/');
  }

  public function logout() {
    if(isset($_SESSION['username'])) {
      session_destroy();
      $this->redirect('/login');
    } else {
      $this->redirect('/');
    }
  }

  public function authenticate() {
    $postUsername = $_POST['username'];
    $postPassword = $_POST['password'];
    if(isset($postUsername) && isset($postPassword)) {
      if(isset($this->settings['private_pass'][$postUsername]) == true && $this->settings['private_pass'][$postUsername] == sha1($postPassword)) {
        $_SESSION['username'] = $postUsername;
        $this->redirect('/');
      }
      $view_vars['login_error'] = 'Invalid login';
      $view_vars['username'] = $postUsername;

      $this->render_view('login', $view_vars);
    }
  }

  public function browse($path, $page) { 
    if ($this->settings['private'] == true && !isset($_SESSION['username'])) {
      $this->redirect('/login');
    }

    // Get request url and script url
    $this->current_page = $page - 1;
    $url = urldecode($path);

    // Get the file path
    $resource =  CONTENT_DIR . $url;

    // Generate breadcrumb
    if($url != "") {
      $this->breadcrumb = array('Home' => $this->settings['base_url']);
      $cleaned_url = str_replace('//', '/', str_replace($this->settings['base_url'], "/", $_SERVER["REQUEST_URI"]));
      $crumbs = explode("/", $cleaned_url);
      array_shift($crumbs);
      foreach($crumbs as $index => $crumb){
        $key = urldecode(ucfirst(str_replace(array(".jpg", ".jpg",  "-", "_"),array("","", " ", " "),$crumb)));

        // Remove last url of breadcrumb items
        if($index == count($crumbs) - 1) {
          $this->breadcrumb[$key] = "";
        } else {
          $this->breadcrumb[$key] = substr($_SERVER["REQUEST_URI"], 0,  strpos($_SERVER["REQUEST_URI"], $crumb) + strlen($crumb));
        }
      }
    }

    if(is_dir($resource)) {
      $this->get_files($resource);

      foreach($this->foldersPath as $folder) {
        $tmp_array = array();
        $tmp_array['name'] = basename($folder);
        $files = glob("$folder/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
        $tmp_array['images_count'] = count($files);

        $temp_url = '/' . $url . "/" . urlencode($tmp_array['name']);
        $tmp_array['thumbnail_url'] = $temp_url . "/" . basename($files[0]);
        $tmp_array['url'] = $this->settings['base_url'] . preg_replace('/(\/)+\//', '/', $temp_url);
        array_push($this->folders, $tmp_array);
      }

      $this->page_count = ceil(count($this->imagesPath) / $this->settings['items_per_page']);

      $start = $this->current_page * $this->settings['items_per_page'];
      $images = Array();

      $imageCount = 0;
      if($this->settings['items_per_page'] < count($this->imagesPath) - $start) {
        $imageCount = $this->settings['items_per_page'];
      } else {
        $imageCount = count($this->imagesPath) - $start;
      }
      for($i = 0; $i < $imageCount; $i++) {
        $image = $this->imagesPath[$i + $start];
        if(isset($image) && ! empty($image)) {
          $temp_array = array();
          $image_basename = basename($image);

          // lazy link to the image
          $encoded_url = str_replace('%2F', '/', urlencode($url));
          $temp_url = '/'. $encoded_url . "/" . urlencode($image_basename);
          $parsed_base_url = parse_url($this->settings['base_url']);
          $temp_array['thumbnail_url'] = preg_replace('/(\/)+\//', '/', $temp_url);
          if(array_key_exists("path", $parsed_base_url)) {
            $temp_array['url'] = preg_replace('/(\/)+\//', '/', "/" . $parsed_base_url['path'] . $temp_url);
          } else {
            $temp_array['url'] = preg_replace('/(\/)+\//', '/', "/" . $temp_url);
          }
          // strip the folder names and just leave the end piece without the extension
          $temp_array['name'] = $image_basename;

          $images[$i] = $temp_array;
        }
        $this->images = $images;

      }
      $twig_vars = array(
        'url' => "/" . $url,
        'breadcrumb' => $this->breadcrumb,
        'folders' => $this->folders,
        'images' => $this->images,
        'page_count' => $this->page_count,
        'current_page' => $this->current_page
      );
      $this->render_view('gallery', $twig_vars);
    } else {
      if(is_file($resource)) {
        $folders = array();
        $imagesArray = array();

        $this->get_files(dirname($resource)."/");
        $previous = "";
        $next = "";
        for($i = 0; $i < count($imagesArray); $i++) {
          if($imagesArray[$i] == $resource){
            if($i >= 1) {
              $previous = $imagesArray[$i-1];
            }
            if($i < count($imagesArray) - 1) {
              $next = $imagesArray[$i+1];
            }
            break;
          }
        }
        $view_vars = array(
          "breadcrumb" => $this->breadcrumb,
          "image_url" => $this->settings['base_url'] . "/content/" . str_replace(CONTENT_DIR, "", $resource),
          "image_previous_url" =>  $this->settings['base_url'] . "/" . str_replace(CONTENT_DIR, "", $previous),
          "image_next_url" => $this->settings['base_url'] . "/" . str_replace(CONTENT_DIR, "", $next)
        );
        $this->render_view('detail',$view_vars);
      }
    }

    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
    echo "<h1>Not found</h1>";
  }


  private function render_view($name, $twig_vars) {
    // Load the theme
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem(THEMES_DIR . $this->settings['theme']);
    $twig = new Twig_Environment($loader, $this->settings['twig_config']);
    $twig->addExtension(new Twig_Extension_Debug());
    $base_url = $this->settings['base_url'];
    $thumbnail_function = new Twig_SimpleFunction('picturo_thumbnail', function ($path, $width, $height)  use($base_url) {
      $imgTag = "<img src='" . $base_url . "/thumbnail/" . $width . "x" . $height . "/" . $path ."' width='$width' height='$height'/>";
      echo $imgTag;
    });
    $twig->addFunction($thumbnail_function);
    $twig_vars['view'] = $name;
    $twig_vars['base_url'] = $this->settings['base_url'];
    $twig_vars['theme_url'] = $this->settings['base_url'] .'/'. basename(THEMES_DIR) .'/'. $this->settings['theme'];
    $twig_vars['site_title'] = $this->settings['site_title'];
    if(isset($_SESSION['username'])) {
      $twig_vars['username'] = $_SESSION['username'];
    } else {
      $twig_vars['username'] = "";
    }
    $output = $twig->render($name . '.html', $twig_vars);
    echo $output;
    exit;
  }

  private function redirect($url) {
    header("Location: ". $this->settings['base_url'] . "$url");
    exit;
  }

  private function get_files($path) {
    if($handle = opendir($path)){
      while(false !== ($file = readdir($handle))){
        if(substr($file,0,1) != "."){
          $file = $path . "/" . $file;
          $file = preg_replace("/\/\//si", "/", $file);
          if( is_dir($file)){
            array_push($this->foldersPath, $file);
          }
          if(is_file($file) && preg_match("/.jpg/i", $file)) {
            array_push($this->imagesPath, $file);
          }
        }
      }
      closedir($handle);
    }
  }

  /**
   * Loads the config
   *
   * @return array $config an array of config values
   */
  private function get_config() {
    if(!file_exists(ROOT_DIR .'config.php')) {
      return array();
    }

    global $config;
    require_once(ROOT_DIR .'config.php');

    $defaults = array(
      'site_title' => 'Picturo',
      'base_url' => $this->base_url(),
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

  /**
   * Helper function to work out the base URL
   *
   * @return string the base url
   */
  private function base_url() {
    global $config;
    if(isset($config['base_url']) && $config['base_url']) return $config['base_url'];

    $url = '';
    $request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
    $script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
    if($request_url != $script_url)  {
      $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');
    }

    $protocol = $this->get_protocol();
    return rtrim(str_replace($url, '', $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), '/');
  }

  /**
   * Tries to guess the server protocol. Used in base_url()
   *
   * @return string the current protocol
   */
  private function get_protocol() {
    preg_match("|^HTTP[S]?|is", $_SERVER['SERVER_PROTOCOL'], $m);
    return strtolower($m[0]);
  }

}

?>
