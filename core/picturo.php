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

  private $current_page = 0;

  private $url;

  private $foldersPath = array();

  private $folders = array();

  private $imagesPath = array();

  private $images = array();

  private $breadcrumb = array();

  private $preloader = array();

  public function displayFoldersNames($path) {
    global $config;

    $realPath = $this->getRequestRealPath($path);

    $tmp_array = array();

    if(is_dir($realPath)) {
      $this->getFiles($realPath);
      foreach($this->foldersPath as $k => $folder) {
        $tmp_array[$k]['name'] = basename($folder);
        $files = glob("$folder/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
        $temp_url = '/' . $this->url . "/" . urlencode($tmp_array[$k]['name']);
        $tmp_array[$k]['thumbnail_url'] = $temp_url . "/" . basename($files[0]);
        $tmp_array[$k]['url'] = $config['base_url'] . preg_replace('/(\/)+\//', '/', $temp_url);
      }
    }
    return $tmp_array;
  }

  public function displayFolder($path, $page) {
    global $config;

    $msg = '';

    if (preg_match('!page[0-9]*!', $path)) {
      $path = '';
    }

    $realPath = $this->getRequestRealPath($path . "/");

    $this->generateBreadcrumb();

    $this->generatePagination($page);

    if(is_dir($realPath)) {
      $this->getFiles($realPath);
      if (isset($_SESSION['username']) && Helper::isAdmin($_SESSION['username'])) {

        $total = count($this->foldersPath);
        $config_file = file_get_contents(CONF_DIR.'config.php');

        if (!isset($config['nbOfFolders']) || $total > $config['nbOfFolders']) {
          if (isset($config['nbOfFolders']) ) {
            $file = explode("\n", $config_file);
            $search = '$config[\'nbOfFolders\']';
            $filter = array_filter($file, function ($element) use ($search,$file) { 
              if (strpos($element, $search) !== false) {
                return $element;
              } 
            } );
            $k = key($filter);
            $file[$k] = '$config[\'nbOfFolders\'] = '.$total.';';
            $file[$k] = str_replace("\n", '', $file[$k]);
            $config_file = implode("\n", $file);
          } else {
             $config_file = str_replace('?>', '$config[\'nbOfFolders\'] = '.$total.';'."\n?>", $config_file);
          }
          file_put_contents(CONF_DIR.'config.php', $config_file);
          $msg = 'You added some folders. Please <a href="'.$config['base_url'].'/createUser">check rights</a> for all users.';
          Helper::Info ($msg);
          Helper::redirect ('/');
        }
      }
      if ($this->isFolderAllowedToDisplay(basename(str_replace('//','',$realPath))) ) {
        foreach($this->foldersPath as $folder) {
          $tmp_array = array();
          $tmp_array['name'] = basename($folder);
          if ($this->isFolderAllowedToDisplay($tmp_array['name']) ) {
          
          $files = glob("$folder/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
          if (!empty($files)) {
              $tmp_array['images_count'] = count($files);
  
              $temp_url = '/' . $this->url . "/" . urlencode($tmp_array['name']);
              $tmp_array['thumbnail_url'] = $temp_url . "/" . basename($files[0]);
              $tmp_array['url'] = $config['base_url'] . preg_replace('/(\/)+\//', '/', $temp_url);
  
              array_push($this->folders, $tmp_array);
            } else {
              $tmp_array = array();
            }
          }
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
            $this->preloader[] = $config['base_url'] . '/thumbnail/900x675' . $temp_array['thumbnail_url'];
          }
          $this->images = $images;
        }

        $twig_vars = array(
          'msg' => $msg,
          'url' => ($this->url == "/" ? "/" : "/" . $this->url),
          'breadcrumb' => $this->breadcrumb,
          'folders' => $this->folders,
          'images' => $this->images,
          'preloader' => $this->preloader,
          'page_count' => $this->page_count,
          'current_page' => $this->current_page
        );
        Helper::renderView('gallery', $twig_vars);
      }
    }
    Helper::renderNotFound();
  }

  public function displayPicture($path, $extension) {
    global $config;

    $realPath = $this->getRequestRealPath( $path . "." . $extension);

    $this->generateBreadcrumb();

    if(is_file($realPath)) {
      if ($this->isFolderAllowedToDisplay(basename(dirname($realPath))) ) {
        // Generate detail pagination
        $this->getFiles(dirname($realPath));
        $previous = "";
        $next = "";
        for($i = 0, $c = count($this->imagesPath); $i < $c; $i++) {
           $image = $this->imagesPath[$i];
            if(isset($image) && ! empty($image)) {
              $image_basename = basename($image);

              // lazy link to the image
              $encoded_url = str_replace('%2F', '/', str_replace(CONTENT_DIR, "", $image));
            }
        }
        
        $this->imagesPath = array_values($this->imagesPath);
        for($i = 0, $c = count($this->imagesPath); $i < $c; $i++) {
          if($this->imagesPath[$i] == $realPath){
            $thumbnail = str_replace('%2F', '/', str_replace(CONTENT_DIR, "", $realPath));
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
          "thumbnail_url" => $thumbnail,
          "image_url" => $config['base_url'] . "/content/" . str_replace(CONTENT_DIR, "", $realPath),
          "image_previous_url" =>  $config['base_url'] . "/" . str_replace(CONTENT_DIR, "", $previous),
          "image_next_url" => $config['base_url'] . "/" . str_replace(CONTENT_DIR, "", $next)
        );
        Helper::renderView('detail', $view_vars);
      }
    }

    Helper::renderNotFound();
  }

  public function managePicture($path, $extension) {
    global $config;

    if (!$config['private']) Helper::redirect('/');

    $realPath = $this->getRequestRealPath( $path . "." . $extension);

    $dir = substr(dirname($realPath),strrpos(dirname($realPath), '/')+1);

    $imgToModify = str_replace(' ', '+', str_replace('/content/'.$dir, '/cache/900x675/'.str_replace(' ', '+', $dir), $realPath));

    $this->generateBreadcrumb();

    if(is_file($realPath)) {

      $this->getFiles(dirname($realPath));

      for($i = 0, $c = count($this->imagesPath); $i < $c; $i++) {
         $image = $this->imagesPath[$i];
          if(isset($image) && ! empty($image)) {
            $image_basename = basename($image);

            // lazy link to the image
            $encoded_url = str_replace('%2F', '/', str_replace(CONTENT_DIR, "", $image));
          }
      }
      for($i = 0, $c = count($this->imagesPath); $i < $c; $i++) {
        if($this->imagesPath[$i] == $realPath){
          $thumbnail = str_replace('%2F', '/', str_replace(CONTENT_DIR, "", $realPath));
          break;
        }
      }

      $retour = str_replace(' ', '+', '/modifications/'.$thumbnail);

      # Inclusion de la classe customImage
      include_once(dirname(__FILE__).'/class.custom.image.php');

      # Initialisation 

      $image_path= $config['base_url'] . "/content/" . str_replace(CONTENT_DIR, "", $realPath) ;
      $image_infos = @getimagesize($realPath) ;
      $mode = 'normal' ;

      # On vérifie qu'on a bien une image
      if(!isset($image_infos['mime'])) {
        $_SESSION['my_custom_image'] = '' ;
        Helper::Error("Le fichier ".$image_path." n'existe pas ou n'est pas une image.");
        Helper::redirect($retour);
        exit; 
      }

      # Enregistrement ou prévisualisation d'une transformation
      if(!empty($_POST['transform'])) { 
        # Récupération des informations
        $t = $_POST['transform'] ;
        $d = isset($_POST['d']) ? $_POST['d'] : 0 ;
        $w = isset($_POST['w']) ? abs(intval($_POST['w'])) : 0 ;
        $h = isset($_POST['h']) ? abs(intval($_POST['h'])) : 0 ;
        $x = isset($_POST['x']) ? abs(intval($_POST['x'])) : 0 ;
        $y = 0;
        $toggle = isset($_POST['toggle']) ? $_POST['toggle'] : 0 ;
  
        # Cas particuliers
        switch($t) {
          case 'setwidth' :
            if($toggle=='height') {
              $t='setheight' ;
              $h = $w ;
            }
            break ;
          case 'cut' :
            switch ($toggle) {
              case 'right' : $x = -$x ; break ;
              case 'top' : $y = $x ;$x=0;  break ;
              case 'bottom' : $y = -$x ;$x=0;  break ;
              default: break ;
            }
            break ;
        }

        # Prévisualisation
        if(!empty($_POST['preview'])) {
          $_SESSION['my_custom_image'] = $image_path ;
          $mode = 'preview';
        } 
        # Enregistrement  
        elseif(!empty($_POST['update'])) {
          # Nouvelle image
          $image = new customImage() ;

          # Trop peu probable pour être honnète
          if(!$image->load($imgToModify)) {
            Helper::Error("Le fichier ".$imgToModify." n'existe pas ou n'est pas une image.");
            Helper::redirect($retour);
            exit();
          }

          # Transformation
          if(!$image->launchTransformation($t,$d,$w,$h,$x,$y)) {
            Helper::Error("Erreur lors de la modification de l'image ");
            Helper::redirect($retour);
            exit;   
          }
          # Enregistrement
          elseif(!$image->save($imgToModify)) {
            Helper::Error("Erreur lors de l'enregistrement de l'image ");
            Helper::redirect($retour);
            exit;   
          } 
          # Modification des Miniatures
            $controller = new Thumbnail(164, 164, $thumbnail);
            $controller->regen($imgToModify);
            if ( is_file(str_replace('/content/', '/cache/284x200/', $realPath)) ) {
                $controller = new Thumbnail(284, 200, $thumbnail);
                $controller->regen($imgToModify);
            }
            $controller = new Thumbnail(900, 675, $thumbnail);
            $controller->regen($imgToModify);

          # Redirection
          Helper::Info("Modification et enregistrement de l'image");
          Helper::redirect($retour);
          exit;   
        } 
      }
      # Aucune transformation
      else {
        # Force le rechargement de l'image
        $image_path .= '?'.@date('YdHs') ;
        # Initialisation des paramètres
        $t = $d = $w = $h = $x = $y = $toggle = 0  ;
      } 

    $tokenMethod = $this->getTokenPostMethod();

      $view_vars = array(
        "mode" => $mode,
        "pathOfPage" => $_SERVER['REQUEST_URI'],
        "t" => $t,
        "d" => $d,
        "w" => $w,
        "h" => $h,
        "x" => $x,
        "y" => $y,
        "absXY" => abs($x+$y),
        "toggle" => $toggle,
        "breadcrumb" => $this->breadcrumb,
        "tokenForm" => $tokenMethod,
        "thumbnail_url" => $thumbnail,
        "image_url" => $image_path,
      );
      Helper::renderView('modifications', $view_vars);
    }

    Helper::renderNotFound();
  }

  //-- Private functions --------------------------------------------------------------------------

  /**
   * Méthode qui affiche le champ input contenant le token
   *
   * @return  stdio
   * @author  Stephane F
   **/
  public function getTokenPostMethod() {

    $token = sha1(mt_rand(0, 1000000));
    $_SESSION['formtoken'][$token] = time();
    return '<input name="token" value="'.$token.'" type="hidden" />';

  }

  /**
   * Méthode qui valide la durée de vide d'un token
   *
   * @parm  $request  (deprecated)
   * @return  stdio/null
   * @author  Stephane F
   **/
  public function validateFormToken($request='') {

    if($_SERVER['REQUEST_METHOD']=='POST' AND isset($_SESSION['formtoken'])) {

      if(empty($_POST['token']) OR $this->getValue($_SESSION['formtoken'][$_POST['token']]) < time() - 3600) { # 3600 seconds
        unset($_SESSION['formtoken']);
        die('Security error : invalid or expired token');
      }
      unset($_SESSION['formtoken'][$_POST['token']]);
    }
  }

  /**
   * Méthode qui vérifie si une variable est définie.
   * Renvoie la valeur de la variable ou la valeur par défaut passée en paramètre
   *
   * @param var     string  variable à tester
   * @param default   string  valeur par defaut
   * @return  valeur de la variable ou valeur par défaut passée en paramètre
  */
  public function getValue(&$var, $default='') {
    return (isset($var) ? (!empty($var) ? $var : $default) : $default) ;
  }

  function getRequestRealPath($path) {
   $this->url = urldecode($path);
   $realPath =  CONTENT_DIR . $this->url;
   return $realPath;
  }

  private function generateBreadcrumb() {
    global $config;

    if (!isset($_SESSION['username']) && !$config['private']) {
      $_SESSION['username'] = null;
    }

    $subdir = substr($config['base_url'],strpos($config['base_url'], '://')+3);
    $subdir = substr($subdir, strpos($subdir,'/'));
    if ($subdir == $_SERVER["SERVER_NAME"]) {
      $subdir = '/';
    }
    if($this->url != "" ) {
      $this->breadcrumb = array('Home' => $config['base_url']);
      if ($_SERVER["REQUEST_URI"] == $subdir.'/' || $_SERVER["REQUEST_URI"] == $subdir) {
        return;
      } else {
        if ($subdir == '/') {
          $REQUEST_URI = $_SERVER["REQUEST_URI"];
        } else {
          $REQUEST_URI = str_replace($subdir, '', $_SERVER["REQUEST_URI"]);
        }
        if (substr($REQUEST_URI, -1) == '/') {
          $REQUEST_URI = substr($REQUEST_URI, 0,-1);
        }
        $crumbs = explode("/", $REQUEST_URI);
        array_shift($crumbs);
      }
      foreach($crumbs as $index => $crumb){
        $key = urldecode(ucfirst(str_replace(array(".jpg", ".jpg",  "-", "_"),array("","", " ", " "),$crumb)));

        if (strpos($REQUEST_URI, 'modifications')) {
          $address = str_replace('/modifications', '', $REQUEST_URI);
          $modifications = '/';
        } else {
          $address =  substr($REQUEST_URI, 0,  strpos($REQUEST_URI, $crumb) + strlen($crumb));
          $modifications = ($config['private'] && Helper::isAdmin($_SESSION['username'])) ? '/modifications/' : '/';
        }
        if($index == count($crumbs) - 1 && $index > 0 && substr($crumb,-4,1) == '.') {
          $this->breadcrumb[$key] = $config['base_url'] . $modifications . $crumbs[$index-1] . '/' . $crumb;
        } elseif($index == count($crumbs) - 1 && $index == 0 && substr($crumb,-4,1) == '.') {
          $this->breadcrumb[$key] =$config['base_url'] . $modifications . $crumbs[$index];
        } else {
          $this->breadcrumb[$key] = $config['base_url'] .$address;
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
          if(is_file($file) && preg_match("/.jpg/i", $file)) {
            array_push($this->imagesPath, $file);
          }
        }
      }
      closedir($handle);
    }
    natcasesort($this->foldersPath);
    natcasesort($this->imagesPath);
    $this->foldersPath = array_values($this->foldersPath);
    $this->imagesPath = array_values($this->imagesPath);
  }

  private function isFolderAllowedToDisplay($folder) {
    global $config;

    if (!$config['private']) return true;

    if ( (isset($config['nbOfFolders']) && $config['nbOfFolders'] == 0) || !isset($config['nbOfFolders']) ) {
      return true;
    }

    if ($folder != basename(CONTENT_DIR) && ( isset($config['manageFoldersRights']) && isset($config['manageFoldersRights'][$_SESSION['username']]) && in_array($folder, $config['manageFoldersRights'][$_SESSION['username']]) ) ) {
      return false;
    }
    return true;
  }

}

?>
