<?php

namespace App\Controllers;

/**
 * Picturo
 *
 * @author Gilbert Pellegrom
 * @link http://pico.dev7studios.com
 * @license http://opensource.org/licenses/MIT
 * @version 0.6.2
 */
class Picturo extends \Core\Controller {

  private $current_page = 0;

  private $url;

  private $foldersPath = array();

  private $folders = array();

  private $imagesPath = array();

  private $images = array();

  private $breadcrumb = array();

  public function __construct($app) {
    parent::__construct($app);
  }

  public function displayFolder($path, $page) {
    global $config;

    $realPath = $this->getRequestRealPath($path . "/");

    $this->generateBreadcrumb();

    $this->generatePagination($page);

    if(is_dir($realPath)) {
      $this->getFiles($realPath);

      foreach($this->foldersPath as $folder) {
        $tmp_array = array();
        $tmp_array['name'] = basename($folder);
        $files = glob("$folder/*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
        $tmp_array['images_count'] = count($files);

        $temp_url = '/' . $this->url . "/" . urlencode($tmp_array['name']);
        $tmp_array['thumbnail_url'] = $temp_url . "/" . basename($files[0]);
        $tmp_array['url'] = $config['base_url'] . preg_replace('/(\/)+\//', '/', $temp_url);
        array_push($this->folders, $tmp_array);
      }

      $this->page_count = ceil(count($this->imagesPath) / $config['items_per_page']);

      $start = $this->current_page * $config['items_per_page'];
      $images = Array();

      $imageCount = 0;
      if($config['items_per_page'] < count($this->imagesPath) - $start) {
        $imageCount = $config['items_per_page'];
      } else {
        $imageCount = count($this->imagesPath) - $start;
      }
      for($i = 0; $i < $imageCount; $i++) {
        $image = $this->imagesPath[$i + $start];
        if(isset($image) && ! empty($image)) {
          $temp_array = array();
          $image_basename = basename($image);

          // lazy link to the image
          $encoded_url = str_replace('%2F', '/', urlencode($this->url));
          $temp_url = '/'. $encoded_url . "/" . urlencode($image_basename);
          $parsed_base_url = parse_url($config['base_url']);
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
        'url' => "/" . $this->url,
        'breadcrumb' => $this->breadcrumb,
        'folders' => $this->folders,
        'images' => $this->images,
        'page_count' => $this->page_count,
        'current_page' => $this->current_page
      );
      $this->app->renderView('gallery', $twig_vars);
    }

    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
    echo "<h1>Not found</h1>";
  }

  public function displayPicture($path, $extension) {
    global $config;

    $realPath = $this->getRequestRealPath( $path . "." . $extension);

    $this->generateBreadcrumb();

    if(is_file($realPath)) {
      // Generate detail pagination
      $this->getFiles(dirname($realPath));
      $previous = "";
      $next = "";
      for($i = 0; $i < count($this->imagesPath); $i++) {
        if($this->imagesPath[$i] == $realPath){
          if($i >= 1) {
            $previous = $this->imagesPath[$i-1];
          }
          if($i < count($this->imagesPath) - 1) {
            $next = $this->imagesPath[$i+1];
          }
          break;
        }
      }

      $view_vars = array(
        "breadcrumb" => $this->breadcrumb,
        "image_url" => $config['base_url'] . "/content/" . str_replace(CONTENT_DIR, "", $realPath),
        "image_previous_url" =>  $config['base_url'] . "/" . str_replace(CONTENT_DIR, "", $previous),
        "image_next_url" => $config['base_url'] . "/" . str_replace(CONTENT_DIR, "", $next)
      );
      $this->app->renderView('detail', $view_vars);
    }

    $this->app->renderNotFound();
  }


  //-- Private functions --------------------------------------------------------------------------


  function getRequestRealPath($path) {
   $this->url = urldecode($path);
   $realPath =  CONTENT_DIR . $this->url;
   return $realPath;
  }

  // TODO : Remove url prefix if installed in subfolder
  private function generateBreadcrumb() {
    global $config;

    if($this->url != "") {
      $this->breadcrumb = array('Home' => $config['base_url']);
      $cleaned_url = str_replace('//', '/', str_replace($config['base_url'], "/", $_SERVER["REQUEST_URI"]));
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
  }


  private function generatePagination($page) {
    $this->current_page = $page - 1;
  }


  private function getFiles($path) {
    if($handle = opendir($path)){
      while(false !== ($file = readdir($handle))){
        if(substr($file,0,1) != "."){
          $file = $path . "/" . $file;
          $file = preg_replace("/\/\//si", "/", $file);
          if( is_dir($file)){
            array_push($this->foldersPath, $file);
          }
          if(is_file($file) && preg_match("/.jpg|.jpeg|.png/i", $file)) {
            array_push($this->imagesPath, $file);
          }
        }
      }
      closedir($handle);
    }
  }

}

?>

