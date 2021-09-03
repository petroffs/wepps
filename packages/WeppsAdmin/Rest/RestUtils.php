<?php 
namespace WeppsAdmin\Rest;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;

class RestUtilsWepps {
    public static function _json_validate($string)
    {
        $string = self::_removeBOM($string);
        $result = json_decode($string,true);
        
        // switch and check possible JSON errors
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $error = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
                // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
                // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $error = 'One or more recursive references in the value to be encoded.';
                break;
                // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $error = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $error = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
                break;
        }
        
        if ($error !== '') {
            // throw the Exception or exit // or whatever :)
            return array('status'=>'500','message'=>$error);
        }
        // everything is OK
        return array('status'=>'200','message'=>'ok','data'=>$result);
        
        
    }
    private static function _removeBOM($data) {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }
    public static function getJsonClear($json) {
    	if (!empty($json)) {
    		$json = str_replace('\\\'',"'", $json);
    		return $json;
    	}
    	return null;
    }
}