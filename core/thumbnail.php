<?php 

class Thumbnail {

  private $width;

  private $height;

  private $path;

  private $cache_path;

  public function __construct($width = 200, $height = 200, $path) {
    $this->width = $width;
    $this->height = $height;
    $this->path = urldecode($path);

    $this->cache_path = str_replace(array('//',' '),array('/','+'),CACHE_DIR . "/" . $width . "x" . $height . "/" . urldecode($path));

    // Check cache folder configuration
    if(file_exists(CACHE_DIR) && is_writable(CACHE_DIR)) {
      if(!file_exists(dirname($this->cache_path)))
        mkdir(dirname($this->cache_path), 0777, true);
    } else {
      echo "<h1>Error</h1><p>Cache folder does not exist or is not writable</p>";
    }
  }

  public function serve($reload=false) {
    if(!$this->exist()) {
      //$this->generate(CONTENT_DIR . $this->path, $this->cache_path,$this->width, $this->height);
      $this->auto_thumb(CONTENT_DIR . $this->path, $this->width, $this->height);
    }
    if($reload) {
      //$this->generate(CONTENT_DIR . $this->path, $this->cache_path,$this->width, $this->height);
      $this->auto_thumb(CONTENT_DIR . $this->path, $this->width, $this->height);
    }
    header("Content-Type: " . mime_content_type($this->cache_path));
    header('Content-Length: ' . filesize($this->cache_path));
    echo file_get_contents($this->cache_path);
  }

  public function regen($path) {
    //$this->generate(CONTENT_DIR . $this->path, $this->cache_path,$this->width, $this->height);
    $this->auto_thumb($path, $this->width, $this->height,'',false,true);
  }

  /*  USAGE
   *  on appelle la function au sein d'une balise img :
   *  <img src="<?php echo auto_thumb('nom.jpg', largeur, hauteur); ?>" />
   *  ou en la générant 
   *  $balise="<img src='".auto_thumb('nom.jpg',139,139)."' alt='nom.jpg' class='lightbox'/>";
  */




  /**
   *   Génère automatiquement la miniatures de l'image passée en argument
   *   aux dimensions spécifiées ou par défaut (100px)
   *   Les miniatures ne sont créées que si elles n'existent pas encore;
   *   si elles existent, seul le chemin est renvoyé.
   *
   * @author bronco@warriordudimanche.com
   * @copyright open source and free to adapt (keep me aware !)
   * @version 2.0
   * @param string $img chemin vers le fichier image
   * @param integer $width largeur maximum du thumbnail généré
   * @param integer $height hauteur maximum du thumbnail généré
   * @param string $add_to_thumb_filename suffixe à ajouter au fichier thumbnail
   * @param boolean $crop_image true=redimensionne et recadre l'image aux dimensions width/$height, false, redimensionne avec proportions
  */
  function auto_thumb($img,$width=null,$height=null,$add_to_thumb_filename='_THUMB_',$crop_image=false,$regen=false){
    // initialisation
    $DEFAULT_WIDTH='100';
    $DEFAULT_HEIGHT='100';
    $DONT_RESIZE_THUMBS=true;

    if ($height < 675) {
      $crop_image = true;
    }

    if (!$width){$width=$DEFAULT_WIDTH;}
    if (!$height){$height=$DEFAULT_HEIGHT;}
    $recadrageX=0;$recadrageY=0;
    $motif='#\.(jpe?g|png|gif)#i'; // Merci à JéromeJ pour la correction  ! 
    //$rempl=$add_to_thumb_filename.'_'.$width.'x'.$height.'.$1';
    $thumb_name=$this->cache_path;
    // sortie prématurée:
    if (!file_exists($img)){return 'auto_thumb ERROR: '.$img.' doesn\'t exists';}
    if (file_exists($thumb_name) && !$regen){return $thumb_name;} // miniature déjà créée
    if ($add_to_thumb_filename!='' && preg_match($add_to_thumb_filename,$img) && $DONT_RESIZE_THUMBS){return false;} // on cherche à traiter un fichier miniature (rangez un peu !)

    // redimensionnement en fonction du ratio
    $taille = getimagesize($img);
    $src_width=$taille[0];
    $src_height=$taille[1];
    if (!$crop_image){ 
      // sans recadrage: on conserve les proportions
      if ($src_width<$src_height){
        // portrait
        $ratio=$src_height/$src_width;
        $width=$height/$ratio;
      }else if ($src_width>$src_height){
        // paysage
        $ratio=$src_width/$src_height;
        $height=$width/$ratio;
      }
    }else{
      // avec recadrage: on produit une image aux dimensions définies mais coupée
      if ($src_width<$src_height){
        // portrait
        $recadrageY=round(($src_height-$src_width)/2);
        $src_height=$src_width;
      }else if ($src_width>$src_height){
        // paysage
        $recadrageX=round(($src_width-$src_height)/2);
        $src_width=$src_height;
      }
    }



    // en fonction de l'extension
    $fichier = pathinfo($img);
    $extension=str_ireplace('jpg','jpeg',$fichier['extension']);
    
    
    $fonction='imagecreatefrom'.strtolower($extension);
    $src  = @$fonction($img);  // que c'est pratique ça ^^ !
    
    // création image
    $thumb = imagecreatetruecolor($width,$height);
    /* See if it failed */

    // gestion de la transparence 
    // (voir fonction de Seebz: http://code.seebz.net/p/imagethumb/)
    if( $extension=='png' ){imagealphablending($thumb,false);imagesavealpha($thumb,true);}
    if( $extension=='gif'  && @imagecolortransparent($img)>=0 ){
      $transparent_index = @imagecolortransparent($img);
      $transparent_color = @imagecolorsforindex($img, $transparent_index);
      $transparent_index = imagecolorallocate($thumb, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
      imagefill($thumb, 0, 0, $transparent_index);
      imagecolortransparent($thumb, $transparent_index);
    }
    
    imagecopyresampled($thumb,$src,0,0,$recadrageX,$recadrageY,$width,$height,$src_width,$src_height);
    imagepng($thumb, $thumb_name);
    imagedestroy($thumb);
    
    return $thumb_name;
  }

  private function generate($src, $dest, $thumb_w = 164, $thumb_h = 164) {
    $src = urldecode($src);
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


  private function exist() {
    return file_exists($this->cache_path);
  }

}
