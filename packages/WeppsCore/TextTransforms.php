<?php
namespace WeppsCore;

class TextTransforms {

	/**
	 * Корректные окончания для существительных
	 * Пример результата: 0 коров, 1 корова, 3 коровы, 16 коров, 21 корова
	 * 
	 * @param int $count
	 * @return string
	 */
	public static function ending1(string $string,int $count) : string {
		$tmp1 = $count;
		$tmp2 = substr ( $tmp1, - 1 );
		
		$suffix = "а";
		if ($tmp2 == 0 || ($tmp2 >= 5 && $tmp2 <= 9) || ($tmp1 >= 11 && $tmp1 <= 19)) {
			$suffix = "ов";
		} elseif ($tmp2 >= 2 && $tmp2 <= 4) {
			$suffix = "а";
		}
		return $string.$suffix;
	}
	
	/**
	 * Корректные окончания для существительных
	 * Пример результата: 0 товаров, 1 товар, 3 товара, 16 товаров, 21 товар
	 *
	 * @param int $count
	 * @return string
	 */
	public static function ending2(string $string,int $count) : string {
		$tmp1 = $count;
		$tmp2 = substr($tmp1,-1);
		$output = "";
		if ($tmp2 == 0 || ($tmp2 >= 5 && $tmp2 <= 9) || ($tmp1 >= 11 && $tmp1 <= 19)) {
			$output = "ов";
		} elseif ($tmp2 >= 2 && $tmp2 <= 4) {
			$output = "а";
		}
		return $string.$output;
	}
	
	public static function money($string,$view=0) {
		$tmp = "";
		if (strstr($string,"от ")!==false) {
			$tmp = "от ";
			$string = str_replace("от ","",$string);
		}
		if ($view==1) {
			return number_format($string,2,".","");
		}
		if (is_numeric($string)) {
			return $tmp.number_format($string,0,","," ");
		}
		return 0;
	}
	public static function date($date, $plusyear = null) {
		/** @var array $matches */
		$t = preg_match ( "/(\d\d\d\d)-(\d\d)-(\d\d)/", $date, $matches );
		if ($t == 0) {
			return "";
		}
		$month = array (
				"января",
				"февраля",
				"марта",
				"апреля",
				"мая",
				"июня",
				"июля",
				"августа",
				"сентября",
				"октября",
				"ноября",
				"декабря" 
		);
		$dateD = $matches [3];
		$dateM = $month [$matches [2] - 1];
		$dateY = $matches [1];
		if ($plusyear == 1) {
			$timestamp = mktime ( 0, 0, 0, $matches [2], $matches [3], $matches [1] + 1 );
			$date = date ( "Y-m-d", $timestamp );
			return self::date ( $date );
		}
		return $dateD . " " . $dateM . " " . $dateY . " г.";
	}
	public static function num2str($inn = 0) {
		$o = array (); // Результаты
		$str = array (); // Основные массивы с строками
		$str [0] = array (
				'',
				'сто',
				'двести',
				'триста',
				'четыреста',
				'пятьсот',
				'шестьсот',
				'семьсот',
				'восемьсот',
				'девятьсот',
				'тысяча' 
		);
		$str [1] = array (
				'',
				'десять',
				'двадцать',
				'тридцать',
				'сорок',
				'пятьдесят',
				'шестьдесят',
				'семьдесят',
				'восемьдесят',
				'девяносто',
				'сто' 
		);
		// названия чисел для сущностей женского рода
		$str [2] = array (
				'',
				'один',
				'два',
				'три',
				'четыре',
				'пять',
				'шесть',
				'семь',
				'восемь',
				'девять',
				'десять' 
		);
		// названия чисел для сущностей мужского рода
		$str [3] = array (
				'',
				'одна',
				'две',
				'три',
				'четыре',
				'пять',
				'шесть',
				'семь',
				'восемь',
				'девять',
				'десять' 
		);
		$str11 = array (
				11 => 'одиннадцать',
				12 => 'двенадцать',
				13 => 'тринадцать',
				14 => 'четырнадцать',
				15 => 'пятнадцать',
				16 => 'шестнадцать',
				17 => 'семнадцать',
				18 => 'восемнадцать',
				19 => 'девятнадцать',
				20 => 'двадцать' 
		);
		$forms = array (
				// 1 2,3,4 5... род слова(индекс для $str )
				array (
						'копейка',
						'копейки',
						'копеек',
						3 
				),
				array (
						'рубль',
						'рубля',
						'рублей',
						2 
				), // 10^0
				array (
						'тысяча',
						'тысячи',
						'тысяч',
						3 
				), // 10^3
				array (
						'миллион',
						'миллиона',
						'миллионов',
						2 
				), // 10^6
				array (
						'миллиард',
						'миллиарда',
						'миллиардов',
						2 
				), // 10^9
				array (
						'триллион',
						'триллиона',
						'триллионов',
						2 
				) 
		) // 10^12
		  // можно дописать всякие секстилионы ...
		;
		
		// Нормализация значения, избавляемся от ТОЧКИ, например 6754321.67 переводим в 7654321067
		$tmp = explode ( '.', str_replace ( ',', '.', $inn ) );
		$rub = $tmp [0]; // рубли
		$kop = isset ( $tmp [1] ) ? str_pad ( str_pad ( $tmp [1], 2, '0' ), 3, '0', STR_PAD_LEFT ) : '000'; // копейки
		$rub .= $kop; // нормализованное значение
		              
		// Поехали!
		$levels = explode ( '-', number_format ( $rub, 0, '', '-' ) );
		$offset = sizeof ( $levels ) - 1;
		foreach ( $levels as $k => $level ) {
			$index = $offset - $k;
			$level = str_pad ( $level, 3, '0', STR_PAD_LEFT );
			if (! empty ( $str [0] [$level [0]] ))
				$o [] = $str [0] [$level [0]];
			$tmp = intval ( $level [1] . $level [2] );
			if ($tmp > 20) {
				$tmp = strval ( $tmp );
				for($i = 0, $m = strlen ( $tmp ); $i < $m; $i ++) {
					// $forms[$index][3] - род слова для текущего уровня
					$rod = $forms [$index] [3];
					$tmp_o = ($i + 1) == 2 ? $str [$rod] [$tmp [$i]] : $str [$i + 1] [$tmp [$i]];
					if (! empty ( $tmp_o ))
						$o [] = $tmp_o;
				}
			} else {
				$o [] = ($tmp > 10 ? $str11 [$tmp] : $str [$forms [$index] [3]] [$tmp]);
			}
			$tmp_o = self::pluralForm ( $level, $forms [$index] [0], $forms [$index] [1], $forms [$index] [2] );
			if (! empty ( $tmp_o ))
				$o [] = $tmp_o;
		}
		if ('000' == $kop) { // Если ноль копеек
			$o [] = '00';
			$o [] = $forms [0] [2];
		}
		return  implode ( ' ', $o );
	}
	private static function pluralForm($n, $f1, $f2, $f5) {
		if (intval ( $n ) == 0)
			return '';
		$n = abs ( $n ) % 100;
		$n1 = $n % 10;
		if ($n > 10 && $n < 20)
			return $f5;
		if ($n1 > 1 && $n1 < 5)
			return $f2;
		if ($n1 == 1)
			return $f1;
		return $f5;
	}
	public static function number(int $number,string $format="%04d") : string {
		return sprintf($format,$number);
	}
		
	public static function translit($string,$rule=1) {
		$tr = array(
				"Ґ"=>"G","Ё"=>"YO","Є"=>"E","Ї"=>"YI","І"=>"I",
				"і"=>"i","ґ"=>"g","ё"=>"yo","№"=>"#","є"=>"e",
				"ї"=>"yi","А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
				"Д"=>"D","Е"=>"E","Ж"=>"ZH","З"=>"Z","И"=>"I",
				"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
				"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
				"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
				"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"Y","Ь"=>"",
				"Э"=>"E","Ю"=>"Y","Я"=>"YA","а"=>"a","б"=>"b",
				"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"zh",
				"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
				"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
				"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
				"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"",
				"ы"=>"y","ь"=>"","э"=>"e","ю"=>"u","я"=>"ya"
		);
		$str = strtr($string,$tr);
		switch ($rule) {
			case 3:
				$str = preg_replace('~[^-a-z-A-Z0-9_\.\/]+~u', '-', $str);
				break;
			case 2:
				$str = preg_replace('~[^-a-z-A-Z0-9_\.]+~u', '-', $str);
				$str = strtolower(str_replace(".","",$str));
				break;
			case 1:
			default:
				$str = preg_replace('~[^-a-z-A-Z0-9_\.]+~u', '-', $str);
				break;
		}
		$str = trim(preg_replace('/--+/', '-',$str),'-');
		$str = trim($str, "-");
		return trim($str);
	}
}