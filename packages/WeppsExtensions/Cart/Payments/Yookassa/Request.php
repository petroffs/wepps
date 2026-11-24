<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Cart\Payments\Yookassa;

require_once __DIR__ . '/../../../../../configloader.php';

use WeppsCore\Request;
use WeppsExtensions\Cart\CartUtils;
use WeppsCore\Utils;

class RequestYookassa extends Request
{
    #[\Override]
    public function request($action = ""): void
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
