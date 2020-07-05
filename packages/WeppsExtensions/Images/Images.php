<?
namespace WeppsExtensions\Images;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

class ImagesWepps {
	private $source;
	private $target;
	private $newfile;
	private $mime;
	public $get;
	function __construct($get) {
		$this->get = UtilsWepps::getStringFormatted($get);
		$filename = (isset($this->get['fileUrl'])) ? $this->get['fileUrl'] : '';
		$action = (isset($this->get['pref'])) ? $this->get['pref'] : '';
		$root = substr(getcwd(),0,strpos(getcwd(), "packages"));
		$rootfilename = $root."".$filename;
		if (!is_file($rootfilename)) ExceptionWepps::error404();
		$res = ConnectWepps::$instance->fetch("select * from s_Files where FileType like 'image/%' and FileUrl='/{$filename}'");
		if (count($res)==0) ExceptionWepps::error404();

		$size = @getimagesize($rootfilename) or die('die.');
		$this->mime = $size['mime'];
		$ratio = $size [0] / $size [1];
		
		/*
		 * Проверка на наличие установочных параметров для ресайза/кропа
		 */
		$vert = 0;
		$crop = 1;
		switch ($action) {
			case "lists" :
				$newsize = 80;
				$vert = 80;
				break;
			case "full" :
				$newsize = 1280;
				break;
			case "catprev" :
				$newsize = 320;
				$vert = 240;
				break;
			case "catbig" :
				$newsize = 640;
				$vert = 480;
				break;
			case "catdir":
				$newsize = 600;
				$vert = 600;
				$crop = 0;
				break;
			case "slider" :
				$newsize = 1280;
				break;
			case "blog":
				$newsize = 700;
				$vert = 460;
				break;
			case "a4":
				$newsize = 500;
				$vert = 705;
				break;
			default :
				ExceptionWepps::error404 ();
				break;
		}

		/*
		 * Директория и файл для записи в файловую систему
		 */
		$newfile = $root."/pic/".$action."/".$filename;
		$newdir = dirname($newfile);
		if (!is_dir($newdir)) mkdir($newdir,0777,true);
		$this->newfile = $newfile;

		/*
		 * Подготовка данных для манипуляций
		 */
		$newsize = (is_numeric ( $newsize ) && $newsize > 0) ? $newsize : 100;
		$newsize = ($newsize > 1920) ? 1920 : $newsize;

		
		//exit();
		$width = ($ratio >= 1) ? $newsize : $newsize * $ratio;
		$height = ($ratio >= 1) ? $newsize / $ratio : $newsize;

		if ($size [0] < $width) {
			$width = $size [0];
			$height = $size [1];
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
				ExceptionWepps::error404 ();
				break;
		}
		
		/*
		 * Манипуляции
		 */
		if ($vert > 10) {
			if ($crop == 1) {
				if (($width <= $height) && $size [0] <= $width) {
					$width = $newsize;
					$height = $width / $ratio;
				}
				if ($height < $vert && $size [0] > $width) {
					$height = $vert;
					$width = $height * $ratio;
				}
				if ($newsize > $vert && $width < $height) {
					$width = $newsize;
					$height = $newsize / $ratio;
				}
				if ($newsize == $vert) {
					$width = $newsize;
					$height = $newsize / $ratio;
				}
				//exit();
			}
			
			if ($crop == 0) {
				if ($height > $vert) {
					$height = $vert;
					$width = $height * $ratio;
				}
			}
			
			$target = imagecreatetruecolor($width, $height);
			$target = $this->imagefill($target);
			$target2 = imagecreatetruecolor($newsize, $vert);
			$target2 = $this->imagefill($target2);
			$newX = 0;
			$newY = 0;
			//$newX = ($width<$newsize && $size [0] > $width) ? 0  : round(($newsize-$width)/2);
			if ($ratio<1) {
				$newX = round(($newsize-$width)/2);
			}
			$newY = ($height==$newsize  && $size [1] > $height) ? 0 : round(($vert-$height)/2);
			imagecopyresampled($target,$source,0,0,0,0,$width,$height,$size[0],$size[1]);
			imagecopy($target2,$target,$newX,$newY,0,0,$width,$height);
			$this->target = $target2;
			$this->source = $source;
			return;
		}

		/*
		 * Сохранение для вывода
		 */
		$target = imagecreatetruecolor($width, $height);
		$target = $this->imagefill($target);
		imagecopyresampled($target,$source,0,0,0,0,$width,$height,$size[0],$size[1]);
		$this->target = $target;
		$this->source = $source;
		return;
	}
	function save() {
		switch ($this->mime) {
			case "image/png" :
				imagepng($this->target,$this->newfile,5);
				break;
			default :
				imagejpeg($this->target,$this->newfile,100);
				break;
		}
	}
	function output() {
		header('Content-type: '.$this->mime);
		header ("Last-Modified: " . gmdate("D, d M Y H:i:s",mktime (0,0,0,1,1,2000)) . " GMT");
		header ("Expires: Mon, 26 Jul 2040 05:00:00 GMT");
		header ("Cache-Control: max-age=10000000, s-maxage=1000000, proxy-revalidate, must-revalidate");
		switch ($this->mime) {
			case "image/png" :
				imagepng($this->target,null,5);
				break;
			default :
				imagejpeg($this->target,null,100);
				break;
		}
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
?>