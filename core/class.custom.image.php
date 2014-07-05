<?php
/*
	Classe customImage
	Charger, transformer et enregistrer une image
	Auteur : Thomas Morin
*/

class customImage {

	public $image = false ;
	public $width = 0;
	public $height = 0;

	# Constructeur
	public function __construct($file=null) {
		if($file) {
			$this->load($file) ;
		}
	}

	# Charge une image
	public function load($file) {
	
		# Vérifie qu'on a un ficher 
		if (!is_file($file)) return false ;
		
		# Vérifie qu'on a une image
		$info = getimagesize($file);
		if (!$info) return false;
		
		# Crée l'image
		$src_image = false ;
		switch ($info['mime']) {
			case 'image/jpeg':
				$src_image = imagecreatefromjpeg($file);
			break;
			case 'image/png':
				$src_image = imagecreatefrompng($file);
			break;
			case 'image/gif':
				$src_image = imagecreatefromgif($file);
			break;
			default:
				return false;
			break;
		}
		
		# Vérifie qu'on a créé une image
		if (!$src_image) return  false ;
		
		# Enregistre les informations
		return $this->saveParams($src_image,$info[0],$info[1]) ;

	}
	
	# Enregistre une image
	public function save($dest_file, $quality=100) {
	
		# L'extension détermine le type de l'image
		switch(strtolower(strrchr($dest_file,'.'))) {
			case ".jpg":
			case ".jpeg":
				return imagejpeg($this->image,$dest_file,$quality);
			case ".png":
				return imagepng($this->image,$dest_file, 9);
			case ".gif":
				return imagegif($this->image,$dest_file);
			default:
				return false;
		}
	}

	# Lance une transformation (ajouté pour pluxml)
	public function launchTransformation($transform,$d,$w,$h,$x,$y) {
		switch($transform) {
			case 'rotate' :
				return $this->rotate($d) ;
			case 'reduce' :
				return $this->reduce($w,$h) ;
			case 'setwidth' :
				return $this->setWidth($w) ;
			case 'setheight' :
				return $this->setHeight($h) ;
			case 'cut' :
				return $this->cut($x,$y) ;
			case 'crop' :
				return $this->crop($w,$h);
			case 'reducecrop' :
				return $this->reduceCrop($w,$h);
			default :
				return false ;
		}
	}

	# Rotation
	public function rotate($degree) {
		# Retourne l'image
		switch($degree) {
			# Rotation 180 degrés
			case 180 :
				$new_dim = true ;
				$dst_image = imagerotate($this->image,180,0);
				break ;
			# Rotation à gauche
			case 'left' :
			case 90 : {
				$new_dim = true ;
				$dst_image = imagerotate($this->image,270,0) ;
				break ;
			}
			# Rotation à droite
			case -90 :
			case 270 :
			case 'right' : {
				$new_dim = true ;
				$dst_image = imagerotate($this->image,90,0) ;
				break ;
			}
			default :
				return false ;
		}
		# Enregistre les nouvelles dimensions 
		if($dst_image) {
			if($new_dim) {
				return $this->saveParams($dst_image,$this->height,$this->width) ;
			} else {
				return $this->saveParams($dst_image,$this->width,$this->height) ;
			}
		}
		return false ;
	}
	
	# Assigne des dimensions maximales
	public function reduce($dst_w=0,$dst_h=0) {
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		
		# Si c'est déjà fait
		if($src_w<=$dst_w and $src_h<=$dst_h) return true ;
		
		# Proportions
		$src_p = $src_w / $src_h ;
		$dst_p = $dst_w / $dst_h ;
		
		# Ratio selon la dimension qui doit le plus être réduite
		if($src_p > $dst_p) {
			$res_h = $dst_h = round($src_h/($src_w / $dst_w ));
		} else {
			$res_w = $dst_w = round($src_w/($src_h / $dst_h));
		}
		
		# Copie, redimensionne, rééchantillonne l'image
		$ok = $this->createImage ($dst_image,$src_image, 0, 0, 0, 0,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}	
	
	# Assigne une nouvelle largeur
	public function setWidth($dst_w=0) {
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		
		# Ajuste la hauteur à la largeur demandée
		$res_h = $dst_h = round($src_h/($src_w/$dst_w));
		
		# Copie, redimensionne, rééchantillonne l'image
		$ok = $this->createImage ($dst_image,$src_image, 0, 0, 0, 0,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}
	
	# Assigne une nouvelle hauteur
	public function setHeight($dst_h=0) {
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		
		# Ajuste la largeur à la hauteur demandée
		$res_w = $dst_w = round($src_w/($src_h/$dst_h));
		
		# Copie, redimensionne, rééchantillonne l'image
		$ok = $this->createImage ($dst_image,$src_image, 0, 0, 0, 0,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}
	
	# Rogne les bords de l'image (coordonnées négatives pour partir du coin en bas à droite)
	public function cut($cut_x=0,$cut_y=0) {
		
		# Formatage
		$cut_x = intval($cut_x) ;
		$cut_y = intval($cut_y) ;
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		$dst_x = $dst_y = $src_x = $src_y = 0 ;
		
		$res_w -= abs($cut_x) ;
		$res_h -= abs($cut_y) ;
		
		if($cut_x>0) {
			$src_x += abs($cut_x) ;
		}
		if($cut_y>0) {
			$src_y += abs($cut_y) ;
		}

		# Crée la nouvelle image
		$ok = $this->createImage ($dst_image,$src_image,$dst_x=0,$dst_y=0,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}
	
	# Coupe en conservant le centre de l'image
	public function crop($res_w=0,$res_h=0) {
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		
		# Si c'est déjà fait
		if($src_w<=$res_w and $src_h<=$res_h) return true ;

		# Coupe dans la largeur si besoin est
		if($res_w < $src_w) {
			$src_x = round(($src_w - $res_w) / 2);
		} else {
			$res_w = $src_w ;
			$src_x = 0 ;
		}
		# Coupe dans la hauteur si besoin est
		if($res_h < $src_h) {
			$src_y = round(($src_h - $res_h) / 2);
		} else {
			$src_y = 0 ;
			$res_h = $src_w ;
		}
				
		# Crée la nouvelle image
		$ok = $this->createImage ($dst_image,$src_image,$dst_x=0,$dst_y=0,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}
	
	# Minimise et coupe pour avoir les dimensions demandées en conservant le centre de l'image
	public function reduceCrop($dst_w=0,$dst_h=0) {
		
		# Initialisation 
		$this->getParams($src_image,$src_w,$src_h,$dst_w,$dst_h,$res_w,$res_h) ;
		
		# Si c'est déjà fait
		if($src_w<=$dst_w and $src_h<=$dst_h) return true ;
		
		# Proportions
		$src_p = $src_w / $src_h;
		$dst_p = $dst_w / $dst_h;

		# Ratio selon la dimension qui doit le moins être réduite
		if ($src_p > $dst_p) {
			$ratio = $src_h / $dst_h;
			$src_x = round(($src_w - $dst_w * $ratio) / 2);
			$src_y = 0 ;
			$dst_w = round($src_w / $ratio);
		}
		else {
			$ratio = $src_w / $dst_w;
			$src_y = round(($src_h - $dst_h * $ratio) / 2);
			$src_x = 0 ;
			$dst_h = round($src_h / $ratio); 
		}
		
		# Crée la nouvelle image
		$ok = $this->createImage ($dst_image,$src_image,$dst_x=0,$dst_y=0,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h);
		
		# Nouvelle dimensions 
		return $ok and $this->saveParams($dst_image,$res_w,$res_h) ; 
	}
	
	# Initialise les paramétres pour imagecopyresampled
	protected function getParams(&$src_image, &$src_w, &$src_h, &$dst_w, &$dst_h, &$res_w, &$res_h) {
		$src_image = $this->image ;
		$src_w = $this->width ;
		$src_h = $this->height ;
		if(!$dst_w or $dst_w<1) $dst_w = $src_w ;
		if(!$dst_h or $dst_h<1) $dst_h = $src_h ;
		if(!$res_h or $dst_w<1) $res_h = $dst_h ;
		if(!$res_w or $dst_w<1) $res_w = $dst_w ;
	}

	# Enregistre les paramétres
	protected function saveParams($src_image,$src_w,$src_h) {
		$this->image = $src_image ;
		$this->width = $src_w ;
		$this->height = $src_h ;
		return true ;
	}
	
	# Crée une image
	protected function createImage(&$dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h,$res_w,$res_h,$color=array()) {
		#
		#	Liste des paramétres (formatés pour imagecopyresampled)
		#	site : http://php.net/manual/fr/function.imagecopyresampled.php
		#
		#	dst_image	=>Lien vers la ressource cible de l'image.
		#	src_image	=>Lien vers la ressource source de l'image.
		#
		#	dst_x		=>X : coordonnées du point de destination.
		#	dst_y		=>Y : coordonnées du point de destination.
		#	src_x		=>X : coordonnées du point source.
		#	src_y		=>Y : coordonnées du point source.
		#
		#	dst_w		=>Largeur de la destination. (rectangle dans lequel l'image est découpée)
		#	dst_h		=>Hauteur de la destination. (rectangle dans lequel l'image est découpée)
		#	src_w		=>Largeur de la source.
		#	src_h		=>Hauteur de la source.	 
		#	
		#	rec_w		=>Largeur de la destination. (rectangle dans lequel l'image est collée)
		#	rec_h		=>Hauteur de la destination. (rectangle dans lequel l'image est collée)
		
		# Crée une nouvelle image
		$dst_image = imagecreatetruecolor($res_w,$res_h);
		
		# Génère la couleur
		$color = array_merge(array('red'=>0,'green'=>0,'blue'=>0,'alpha'=>127),(array)$color) ;
		$color = imagecolorallocatealpha($dst_image, 0, 0, 0, 127);
		
		# Remplit avec la couleur
		imagefill($dst_image, 0, 0, $color);
				
		# Copie, redimensionne, rééchantillonne l'image
		return imagecopyresampled (
			$dst_image,
			$src_image,
			$dst_x,$dst_y,
			$src_x,$src_y,
			$dst_w,$dst_h,
			$src_w, $src_h
		);	
	}
}
?>