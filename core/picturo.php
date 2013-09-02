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

   /**
    * The constructor carries out all the processing in Picturo.
    * Does URL routing, Markdown processing and Twig processing.
    */
   public function __construct() {

      // Load the settings
      $settings = $this->get_config();

      // Check cache folder configuration
      if(file_exists(CACHE_DIR) && is_writable(CACHE_DIR)) {
         echo "CACHE OK";
         if( ! file_exists(CACHE_DIR . "/folders"))
            mkdir(CACHE_DIR . "/folders", 0777);
      } else {
         echo "<h1>Error</h1><p>Cache folder does not exist or is not writable</p>";
         die;
      }

      // Get request url and script url
      $url = '';
      $request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
      $script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';

      // Get our url path and trim the / of the left and the right
      if($request_url != $script_url) {
         $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');
      }
      $url = preg_replace('/\?.*/', '', $url); // Strip query string
      $url = urldecode($url);

      // Match page[0-9]
      if(preg_match("/\/page([0-9]+)$/", $url, $page)) {
         $this->current_page = $page[1] - 1;
         $url = str_replace($page[0], "", $url);
      }

      // Get the file path
      $resource = CONTENT_DIR . $url;

      // Create cache folder
      if(! is_dir(CACHE_DIR . $url) && is_dir($resource)) {
         mkdir(CACHE_DIR . $url);
      }

      if(is_dir($resource)) {
         $folders = array();
         $imagesArray = array();
         $this->get_files($resource, &$folders, &$imagesArray);

         foreach($folders as &$folder) {
            $tmp_array = array();
            $tmp_array['name'] = basename($folder);
            $files = glob("$folder/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
            $tmp_array['images_count'] = count($files);
            //$folders = glob("$folder/*", GLOB_ONLYDIR);
            //$tmp_array['folders_count'] = count($folders);

            $tmp_array['last_modified'] = date ("F d Y H:i:s.", filemtime($folder));
            // Generate thumbnail
            if( ! file_exists(CACHE_DIR. "folders/" . basename($files[0]))) {
               $this->make_thumb($files[0], CACHE_DIR. "folders/" . basename($files[0]), 294, 200);
            }
            $tmp_array['thumbnail'] = "/cache/folders/". basename($files[0]);

            // TODO : Find a better way to handle this
            if($url == "") {
               $tmp_array['url'] =   $settings['base_url'] .'/' . strtolower(urlencode($tmp_array['name']));
            } else {
               $tmp_array['url'] =   $settings['base_url'] .'/' . $url . "/" . strtolower(urlencode($tmp_array['name']));
            }
            $folder = $tmp_array;
         }

         $this->items_per_page = 15;

         $this->page_count = ceil(count($imagesArray) / $this->items_per_page);

         $start = $this->current_page * $this->items_per_page;
         $images = Array();
         for($i = 0; $i < $this->items_per_page; $i++) {
            $image = $imagesArray[$i + $start];
            if(isset($image) && ! empty($image)) {
               $temp_array = array();

               // Generate thumbnail
               if( ! file_exists(CACHE_DIR. $url . "/" . basename($image))) {
                  $this->make_thumb($image, CACHE_DIR. $url . "/" . basename($image));
               }
               $temp_array['thumbnail'] = "/cache/" . $url . "/" . basename($image);

               // lazy link to the image
               $encoded_url = str_replace('%2F', '/', urlencode($url));
               $temp_array['url'] =  $settings['base_url'] .'/'. $encoded_url . "/" . urlencode(basename($image));
               // strip the folder names and just leave the end piece without the extension
               $temp_array['name'] = basename($image);

               $images[$i] = $temp_array;
            }

         }
      } else {
         if(is_file($resource)) {
            $folders = array();
            $imagesArray = array();

            $this->get_files(dirname($resource)."/", &$folders, &$imagesArray);
            $previous = "";
            $next = "";
            for($i = 0; $i < count($imagesArray); $i++) {
               if($imagesArray[$i] == $resource){
                  $previous = $imagesArray[$i-1];
                  $next = $imagesArray[$i+1];
                  break;
               }
            }
            $image_url = "/content/" . str_replace(CONTENT_DIR, "", $resource);
            $image_previous_url =  "/" . str_replace(CONTENT_DIR, "", $previous);
            $image_next_url = "/" . str_replace(CONTENT_DIR, "", $next);
         }
      }

      if(file_exists($file)){
         $content = file_get_contents($file);
      } else {
         $content = file_get_contents(CONTENT_DIR .'404'. CONTENT_EXT);
         header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
      }

      // Generate breadcrumb
      if($url != "") {
         $breadcrumb = array('Home' => '/');
         $key = "";
         $crumbs = explode("/",$_SERVER["REQUEST_URI"]);
         array_shift($crumbs);
         foreach($crumbs as $crumb){
            $key = urldecode(ucfirst(str_replace(array(".php","_"),array(""," "),$crumb)));
            $breadcrumb[$key] = substr($_SERVER["REQUEST_URI"], 0,  strpos($_SERVER["REQUEST_URI"], $crumb) + strlen($crumb));
         }
         // Remove last url of breadcrumb items
         $breadcrumb[$key] = "";
      }

      // Load the theme
      Twig_Autoloader::register();
      $loader = new Twig_Loader_Filesystem(THEMES_DIR . $settings['theme']);
      $twig = new Twig_Environment($loader, $settings['twig_config']);
      $twig->addExtension(new Twig_Extension_Debug());
      $twig_vars = array(
         'config' => $settings,
         'base_dir' => rtrim(ROOT_DIR, '/'),
         'base_url' => $settings['base_url'],
         'theme_dir' => THEMES_DIR . $settings['theme'],
         'theme_url' => $settings['base_url'] .'/'. basename(THEMES_DIR) .'/'. $settings['theme'],
         'site_title' => $settings['site_title'],
         'url' => "/" . $url,
         'breadcrumb' => $breadcrumb,
         'folders' => $folders,
         'images' => $images,
         'image_url' => $image_url,
         'image_previous_url' => $image_previous_url,
         'image_next_url' => $image_next_url,
         'page_count' => $this->page_count,
         'current_page' => $this->current_page
      );
      $output = $twig->render('index.html', $twig_vars);
      echo $output;
   }


   private function get_files($path, &$folders, &$images) {
      if($handle = opendir($path)){
         while(false !== ($file = readdir($handle))){
            if(substr($file,0,1) != "."){
               $file = $path . "/" . $file;
               $file = preg_replace("/\/\//si", "/", $file);
               if( is_dir($file)){
                  array_push($folders, $file);
               }
               if(is_file($file)) {
                  array_push($images, $file);
               }
            }
         }
         closedir($handle);
      }
   }

   /**
    * Load any plugins
    */
   private function load_plugins() {
      $this->plugins = array();
      $plugins = $this->get_files(PLUGINS_DIR, '.php');
      if(!empty($plugins)){
         foreach($plugins as $plugin){
            include_once($plugin);
            $plugin_name = preg_replace("/\\.[^.\\s]{3}$/", '', basename($plugin));
            if(class_exists($plugin_name)){
               $obj = new $plugin_name;
               $this->plugins[] = $obj;
            }
         }
      }
   }

   /**
    * Loads the config
    *
    * @return array $config an array of config values
    */
   private function get_config() {
      if(!file_exists(ROOT_DIR .'config.php')) return array();

      global $config;
      require_once(ROOT_DIR .'config.php');

      $defaults = array(
         'site_title' => 'Picturo',
         'base_url' => $this->base_url(),
         'theme' => 'default',
         'date_format' => 'jS M Y',
         'twig_config' => array('cache' => false, 'autoescape' => false, 'debug' => false),
         'pages_order_by' => 'alpha',
         'pages_order' => 'asc'
      );

      if(is_array($config)) {
         $config = array_merge($defaults, $config);
      } else {
         $config = $defaults;
      }

      return $config;
   }

   private function make_thumb($src, $dest, $thumb_w = 164, $thumb_h = 164) {
      $srcimg = imagecreatefromjpeg($src);
      $src_w = imagesx($srcimg);
      $src_h = imagesy($srcimg);
      $src_ratio = $src_w/$src_h;
      if (1 > $src_ratio) {
         $new_h = $thumb_w/$src_ratio;
         $new_w = $thumb_w;
      } else {
         $new_w = $thumb_h*$src_ratio;
         $new_h = $thumb_h;
      }
      $x_mid = $new_w/2;
      $y_mid = $new_h/2;
      $newpic = imagecreatetruecolor(round($new_w), round($new_h));
      imagecopyresampled($newpic, $srcimg, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
      $final = imagecreatetruecolor($thumb_w, $thumb_h);
      imagecopyresampled($final, $newpic, 0, 0, ($x_mid-($thumb_w/2)), ($y_mid-($thumb_h/2)), $thumb_w, $thumb_h, $thumb_w, $thumb_h);
      imagedestroy($newpic);
      imagedestroy($srcimg);

      imagejpeg($final, $dest, 80); //again, assuming jpeg, 80% quality
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
      if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');

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
