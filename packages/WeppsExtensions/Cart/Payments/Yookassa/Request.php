<?php
namespace WeppsExtensions\Cart\Payments\Yookassa;

use WeppsCore\Utils\RequestWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsCore\Utils\UtilsWepps;

require_once '../../../../../config.php';
require_once '../../../../../autoloader.php';
require_once '../../../../../configloader.php';

class RequestYookassaWepps extends RequestWepps
{
    public function request($action = "")
    {
        $cartUtils = new CartUtilsWepps();
        $yookassa = new YookassaWepps($this->get, $cartUtils);
        switch ($action) {
            case 'form':
                $response = $yookassa->form();
                if (!empty($response['url'])) {
                    #UtilsWepps::debug($response['url'],1);
                    header("location: {$response['url']}");
                    exit();
                }
                break;
            case 'return':
                $yookassa->return();
                break;
            case 'webhook':
                $yookassa->webhook();
                break;    
            case 'check':
                $yookassa->check();
                break;
            case 'test':
                $yookassa->test();
                break;
            default:
                http_response_code(404);
                exit();
        }
    }
}

$request = new RequestYookassaWepps($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);