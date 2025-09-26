<?php
require_once '../../../../../configloader.php';

use WeppsCore\Request;
use WeppsExtensions\Cart\CartUtils;
use WeppsCore\Utils;
use WeppsExtensions\Cart\Payments\Yookassa\Yookassa;

class RequestYookassa extends Request
{
    public function request($action = "")
    {
        $cartUtils = new CartUtils();
        $yookassa = new Yookassa($this->get, $cartUtils);
        switch ($action) {
            case 'form':
                $response = $yookassa->form();
                if (!empty($response['url'])) {
                    #Utils::debug($response['url'],1);
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
            default:
                http_response_code(404);
                exit();
        }
    }
}

$request = new RequestYookassa($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);