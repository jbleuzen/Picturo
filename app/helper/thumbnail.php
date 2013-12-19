<?php 

class HelperThumbnail {

  private $width;

  private $height;

  private $path;

  private $destImagePage;


  public function __construct($path) {
    $this->path = urldecode($path);
  }


  public function setThumbnailSize($width, $height) {
    $this->height = $height;
    $this->width = $width;
  }


  public function serve() {
    $this->getImagePath();
    if( ! file_exists($this->destImagePage) && isset($this->width) && isset($this->height)) {
      $this->generate(CONTENT_DIR . $this->path, $this->destImagePage,$this->width, $this->height);
    }
    header("Content-Type: " . mime_content_type($this->destImagePage));
    header('Content-Length: ' . filesize($this->destImagePage));
    echo file_get_contents($this->destImagePage);
  }


  private function getImagePath() {
    if(isset($this->width) || isset($this->height)) {
      $this->destImagePage = CACHE_DIR . "/" . $this->width . "x" . $this->height . "/" . $this->path;
      // Check cache folder configuration
      if(file_exists(CACHE_DIR) && is_writable(CACHE_DIR)) {
        if(!file_exists(dirname($this->destImagePage))) {
          $result = mkdir(dirname($this->destImagePage), 0777, true);
        }
      } else {
        echo "<h1>Error</h1><p>Cache folder does not exist or is not writable</p>";
      }
    } else {
      $this->destImagePage = CONTENT_DIR . $this->path;
    }
  }


  private function generate($src, $dest, $thumb_w, $thumb_h) {
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


}
