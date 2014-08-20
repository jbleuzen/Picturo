<?php


namespace App\Controllers;

class Thumbnail extends \Core\Controller {

  private $width;

  private $height;

  private $path;

  private $cache_path;

  public function __construct($app, $width = 200, $height = 200, $path) {
    parent::__construct($app);
    $this->width = $width;
    $this->height = $height;
    $this->path = urldecode($path);

    $this->cache_path = CACHE_DIR . "/" . $width . "x" . $height . "/" . $path;

    // Check cache folder configuration
    if(file_exists(CACHE_DIR) && is_writable(CACHE_DIR)) {
      if(!file_exists(dirname($this->cache_path)))
        mkdir(dirname($this->cache_path), 0777, true);
    } else {
      echo "<h1>Error</h1><p>Cache folder does not exist or is not writable</p>";
    }
  }

  public function serve() {
    if(!$this->exist()) {
      $this->generate(CONTENT_DIR . $this->path, $this->cache_path,$this->width, $this->height);
    }
    header("Content-Type: " . mime_content_type($this->cache_path));
    header('Content-Length: ' . filesize($this->cache_path));
    echo file_get_contents($this->cache_path);
  }


  private function generate($src, $dest, $thumb_w = 164, $thumb_h = 164) {
    $ext = pathinfo($src, PATHINFO_EXTENSION);
    if($ext == "jpg" || $ext == "jpeg") {
      $srcimg = imagecreatefromjpeg($src);
    }
    if($ext == "png") {
      $srcimg = imagecreatefrompng($src);
    }
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

    if($ext == "jpg" || $ext == "jpeg") {
      imagejpeg($final, $dest, 80); //again, assuming jpeg, 80% quality
    }
    if($ext == "png") {
      imagepng($final, $dest, 0); //again, assuming jpeg, 80% quality
    }
  }


  private function exist() {
    return file_exists($this->cache_path);
  }

}
