<?php
namespace WeppsCore\Validator;

class ValidatorWepps {
	/**
	 * Проверка на непустую переменную
	 *
	 * @param mixed $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isNotEmpty($variable,$errorMessage) {
		$tmp = '';
		if (is_array($variable)) {
			foreach ($variable as $value) {
				$value = trim($value);
				if (!empty($value)) return $tmp;
			}
		} else {
			$variable = trim($variable);
			if (!empty($variable)) return $tmp;
		}
		return $errorMessage;
	}
	
	/**
	 * Проверка на число
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isInt($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^[\d]+$/",$variable)) return $tmp;
		return $errorMessage;
	}
	
	/**
	 * Проверка на латиницу и символы '_', '-'
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isLat($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^[\da-zA-Z\_\-\.]+$/",$variable)) return $tmp;
		return $errorMessage;
	}
	
	/**
	 * Проверка на урл (http://some-addres/?me=valid', http://www.some-addres/hi_world/?me=valid')
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isUrl($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^(http\:\/\/)[\da-zA-Z\.\-\_\/]+\.[a-zA-Z]{2,5}(\/.+)*$/",$variable)) return $tmp;
		return $errorMessage;
	}
	
	/**
	 * Проверка на электронный адрес (mail@petroffs.com, mail@corp.petroffs.com)
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isEmail($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^[\da-zA-Z\.\_\-]+\@[\da-zA-Z\.\-]+\.[a-zA-Z]{2,5}$/",$variable)) return $tmp;
		return $errorMessage;
	}
	
	/**
	 * Проверка на дату (ГГГГ-ММ-ДД)
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isDate($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^\d{4}-\d{1,2}-\d{1,2}$/",$variable)) {
			$exp = explode('-',$variable);
			if ($exp[1]>12) return $errorMessage;
			if ($exp[2]>31) return $errorMessage;
			return $tmp;
		}
		return $errorMessage;
	}
	
	/**
	 * Проверка на время (ЧЧ:ММ:СС)
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isTime($variable,$errorMessage) {
		$tmp = '';
		if (preg_match("/^\d{1,2}\:\d{1,2}\:\d{1,2}$/",$variable)) {
			$exp = explode(':',$variable);
			if ($exp[0]>24) return $errorMessage;
			if ($exp[1]>59) return $errorMessage;
			if ($exp[2]>59) return $errorMessage;
			return $tmp;
		}
		return $errorMessage;
	}
	
	/**
	 * Проверка на тип датавремя (ГГГГ-ММ-ДД ЧЧ:ММ:СС)
	 *
	 * @param string $variable
	 * @param string $errorMessage - сообщение в случае ошибки
	 * @return string - сообщение, в случае ошибки / пустую строку в случае успеха
	 *
	 */
	public static function isDateTime($variable,$errorMessage) {
		$tmp = '';
		$exp = explode(' ',$variable);
		$tmp  = self::isDate($exp[0],$errorMessage);
		if (isset($exp[1])) {
			$tmp .= self::isTime($exp[1],$errorMessage);
		}
		return ($tmp=='')?'':$errorMessage;
	}
	
	/**
	 * Индикация ошибок формы
	 */
	public static function setFormErrorsIndicate ($errors,$form) {
	    $str = "<script>\n";
	    $errorCount=0;
	    foreach ($errors as $key => $value) {
	        if ($value!="") {
	            $errorCount++;
	            $str .= "
				var elem = $('#{$form}').find('[name=\"{$key}\"]');
                if (elem.length==0) {
                    var elem = $('#{$form}').find('[name=\"{$key}[]\"]');
                }
				if (elem.length!=0) {
					elem.closest('label').addClass('pps_error_parent');
					var t = $('<div>{$value}</div>').addClass('pps_error_{$key}').addClass('pps_error');
					elem.eq(0).before(t);
					t.on('click',function(event) {
						$(this).closest('label').removeClass('pps_error_parent');
						$(this).remove();
					});
				}\n";
	        } else {
	            $str .= "$('.pps_error_{$key}').trigger('click');";
	        }
	    }
	    $str .= "
				$('.pps_error_parent').children().on('focus',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.pps_error_'+attr).trigger('click');
				});
				$('.pps_error_parent').children('input').on('change',function() {
                    var attr = $(this).attr('name').replace('[]','');
					$('.pps_error_'+attr).trigger('click');
				});
		";
	    $str .= "</script>";
	    return array('Co'=>$errorCount,'Out'=>$str);
	}
	
	/**
	 * Сообщение об успхе отправки формы
	 */
	public static function setFormSuccess ($message,$form,$js="") {
		$str = "
			<script>
			$('#{$form}').html('{$message}');
			$('#{$form}').addClass('pps_success');
			$('#{$form}').fadeIn();
			$js
			</script>
				";
		return array('Out'=>$str);
	}
}


?>