<?php
namespace WeppsExtensions\Addons\Images;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

class ImagesWepps {
	private $source;
	private $target;
	private $newfile;
	private $fileinfo;
	private $mime;
	private $ratio = 0.00;
	private $width = 0;
	private $height = 0;
	private $widthDst = 0;
	private $heightDst = 0;
	public $get;
	function __construct($get) {
		$this->get = UtilsWepps::trim($get);
		$filename = (isset($this->get['fileUrl'])) ? $this->get['fileUrl'] : '';
		$action = (isset($this->get['pref'])) ? $this->get['pref'] : '';
		$root = substr(getcwd(),0,strpos(getcwd(), "packages"));
		$rootfilename = $root."".$filename;
		if (!is_file($rootfilename)) {
			ExceptionWepps::error(404);
		}
		$res = ConnectWepps::$instance->fetch("select * from s_Files where FileType like 'image/%' and FileUrl='/{$filename}'");
		if (count($res)==0) {
			ExceptionWepps::error(404);
		}
		$this->fileinfo = $res[0];
		$size = @getimagesize($rootfilename) or die('die.');
		$this->mime = $size['mime'];
		$this->ratio = $size[0]/$size[1];
		
		/*
		 * Проверка на наличие установочных параметров для ресайза/кропа
		 */
		$crop = 1;
		$side = 0;
		$this->widthDst = 0;
		$this->heightDst = 0;
		switch ($action) {
			case "lists" :
				$this->widthDst = 80;
				$this->heightDst = 80;
				$crop = 0;
				break;
			case "full" :
				$side = 1280;
				break;
			case "catprev" :
				$this->widthDst = 320;
				$this->heightDst = 240;
				break;
			case "catbig" :
				$this->widthDst = 640;
				$this->heightDst = 480;
				break;
			case "catbigv" :
				$this->widthDst = 480;
				$this->heightDst = 600;
				break;
			case "catdir":
				$this->widthDst = 600;
				$this->heightDst = 600;
				$crop = 0;
				break;
			case "slider" :
				$side = 1280;
				break;
			case "a4":
				$this->widthDst = 500;
				$this->heightDst = 705;
				break;
			default :
				ExceptionWepps::error(404);
				exit();
				break;
		}
		/*
		 * Директория и файл для записи в файловую систему
		 */
		$newfile = $root."/pic/".$action."/".$filename;
		$newdir = dirname($newfile);
		if (!is_dir($newdir)) mkdir($newdir,0750,true);
		$this->newfile = $newfile;
		$ratio = ($this->widthDst>0 && $this->heightDst>0) ? $this->widthDst/$this->heightDst : $this->ratio;
		
		/*
		 * Подготовка данных для манипуляций
		 */
		
		if ($side>0) {
			$side = (is_numeric($side) && $side>0)?$side:100;
			$side = ($side>1920)?1920:$side;
			$this->widthDst = ($this->ratio >= 1) ? $side : $side * $this->ratio;
			$this->heightDst = ($this->ratio >= 1) ? $side / $this->ratio : $side;
		}
		
		switch ($this->mime) {
			case "image/gif" : // IMAGETYPE_GIF
				$source = imagecreatefromgif($rootfilename) or die('die gif');
				break;
			case "image/jpeg" : // IMAGETYPE_JPEG
				$source = imagecreatefromjpeg($rootfilename) or die('die jpeg');
				break;
			case "image/png" : // IMAGETYPE_PNG
				$source = imagecreatefrompng($rootfilename) or die('die png');
				imagealphablending($source,false);
				imagesavealpha($source, true);
				break;
			default :
				ExceptionWepps::error(404);
				exit();
				break;
		}
		
		/*
		 * CROP
		 * Источник > 1, Цель > 1
		 * https://platform.wepps.ubu/pic/catbig/files/lists/News/000002_Images_1733344138_SS04006.JPG
		 * 
		 * Источник > 1, Цель < 1
		 * https://platform.wepps.ubu/pic/catbigv/files/lists/News/000002_Images_1733344138_SS04006.JPG
		 * 
		 * Источник < 1, Цель > 1
		 * https://platform.wepps.ubu/pic/catbig/files/lists/News/000001_Images_1492375078_llDay.ru_102.JPG
		 * 
		 * Источник < 1, Цель < 1
		 * https://platform.wepps.ubu/pic/catbigv/files/lists/News/000001_Images_1492375078_llDay.ru_102.JPG
		 * 
		 * Источник = 1, Цель > 1
		 * https://platform.wepps.ubu/pic/catbig/files/lists/News/12_Image_1314039898_BS16006.jpg
		 * 
		 * Источник = 1, Цель < 1
		 * https://platform.wepps.ubu/pic/catbigv/files/lists/News/12_Image_1314039898_BS16006.jpg
		 * 
		 * Источник > 1, Цель = 1
		 * https://platform.wepps.ubu/pic/lists/files/lists/News/000003_Images_1733349746_001.png
		 * 
		 * Источник < 1, Цель = 1
		 * https://platform.wepps.ubu/pic/lists/files/lists/News/000003_Images_1733349746_002.png
		 * 
		 */
		if ($this->heightDst > 0) {
			if ($size[0]<$this->widthDst || $size[1]<$this->heightDst) {
				$this->width = $size [0];
				$this->height = $size [1];
			} elseif ($crop==0) {
				$this->resize($this->widthDst, $this->heightDst, $ratio);
			} else {
				$this->crop($this->widthDst, $this->heightDst, $ratio);
			}
			$target = imagecreatetruecolor($this->width, $this->height);
			$target = $this->imagefill($target);
			$target2 = imagecreatetruecolor($this->widthDst, $this->heightDst);
			$target2 = $this->imagefill($target2);
			$newX = round(($this->widthDst-$this->width)/2);
			$newY = round(($this->heightDst-$this->height)/2);
			imagecopyresampled($target,$source,0,0,0,0,$this->width,$this->height,$size[0],$size[1]);
			imagecopy($target2,$target,$newX,$newY,0,0,$this->width,$this->height);
			$this->target = $target2;
			$this->source = $source;
			return;
		}
		
		/*
		 * Сохранение для вывода
		 */
		$target = imagecreatetruecolor($this->widthDst, $this->heightDst);
		$target = $this->imagefill($target);
		imagecopyresampled($target,$source,0,0,0,0,$this->widthDst,$this->widthDst/$this->ratio,$size[0],$size[1]);
		$this->target = $target;
		$this->source = $source;
		return;
	}
	public function save() {
		switch ($this->mime) {
			case "image/png" :
				imagepng($this->target,$this->newfile,5);
				break;
			default :
				imagejpeg($this->target,$this->newfile,100);
				break;
		}
	}
	public function output() {
		header('Content-type: '.$this->mime);
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Expires: ".gmdate("D, d M Y H:i:s",strtotime('+1 years'))." GMT");
		header("Cache-Control: max-age=31536000, s-maxage=31536000, must-revalidate");
		switch ($this->mime) {
			case "image/png" :
				imagepng($this->target,null,5);
				break;
			default :
				imagejpeg($this->target,null,100);
				break;
		}
	}
	private function resize(float $width, float $height, float $ratio) {
		if ($ratio==1) {
			if ($this->ratio>=1) {
				$this->width = $width ;
				$this->height = $width/$this->ratio;
			} else {
				$this->width = $height * $this->ratio;
				$this->height = $height;
			}
		} elseif ($ratio>=1) {
			if ($this->ratio>=1) {
				$this->width = $height * $this->ratio;
				$this->height = $height;
			} else {
				$this->width = $height * $this->ratio;
				$this->height = $height;
			}
		} else {
			if ($this->ratio>=1) {
				$this->width = $width;
				$this->height = $width / $this->ratio;
			} else {
				$this->width = $height * $this->ratio ;
				$this->height = $height;
			}
		}
	}
	private function crop(float $width, float $height, float $ratio) {
		if ($ratio==1) {
			if ($this->ratio>=1) {
				$this->width = $height * $this->ratio;
				$this->height = $height;
			} else {
				$this->width = $width ;
				$this->height = $width/$this->ratio;
			}
		} elseif ($ratio>=1) {
			if ($this->ratio>=1) {
				$this->width = $width ;
				$this->height = $width/$this->ratio;
			} else {
				$this->width = $width;
				$this->height = $width / $this->ratio;
			}
		} else {
			if ($this->ratio>=1) {
				$this->width = $height * $this->ratio;
				$this->height = $height;
			} else {
				$this->width = $width ;
				$this->height = $width/$this->ratio;
			}
		}
	}
	public function stamp(string $x='right',string $y='bottom',float $gap=0.1, string $list='', string $filename='') {
		$exit = 1;
		if (empty($list)) {
			$exit = 0;
		}
		if (!empty($list)) {
			if (strstr($list, $this->fileinfo['TableName'])) {
				$exit = 0;
			}
		}
		if ($exit==1) {
			return;
		}
		if (empty($filename)) {
			$filename = ConnectWepps::$projectDev['root'].ConnectWepps::$projectServices['images']['stamp'];
		}	
		$target = imagecreatefrompng($filename);
		imagesavealpha($target, true);
		$size = getimagesize($filename);
		$ratio = $size[0]/$size[1];
		
		/*
		 * Уменьшить штамп
		 */
		$width = $this->widthDst * 0.15;
		$height = $width / $ratio;
		$thumb = imagecreatetruecolor($width, $height);
		$background = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
		imagecolortransparent($thumb, $background);
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		imagecopyresized($thumb,$target,0,0,0,0,$width,$height,$size[0],$size[1]);
		$target = $thumb;
		$gap = $width*$gap;
		switch ($x) {
			case 'left':
				$posX = $gap;
				break;
			case 'center':
				$posX = $this->widthDst/2 - $width/2;
				break;
			case 'right':
			default:
				$posX = $this->widthDst - $width - $gap;
				break;
		}
		switch ($y) {
			case 'top':
				$posY = $gap;
				break;
			case 'center':
				$posY = $this->heightDst/2 - $height/2;
				break;
			case 'bottom':
			default:
				$posY = $this->heightDst - $height - $gap;
				break;
		}
		imagecopy($this->target,$target,$posX,$posY,0,0,$width,$height);
	}
	private function imagefill($target) {
		switch ($this->mime) {
			case "image/png" :
				imagealphablending( $target, false );
				imagesavealpha( $target, true );
				$transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
				imagefill($target, 0, 0, $transparent);
				break;
			default :
				imagefill($target,0,0,0xFFFFFF);
				break;
		}
		return $target;
	}
	function __destruct() {
		if ($this->target) imagedestroy($this->target);
		if ($this->source) imagedestroy($this->source);
		ConnectWepps::$instance->close();
	}
}