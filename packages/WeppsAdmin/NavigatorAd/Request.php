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

namespace WeppsAdmin\NavigatorAd;

require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\TextTransforms;

class RequestNavigatorAd extends Request {
    public function request($action="") {
        $this->tpl = '';
        if (@Connect::$projectData['user']['ShowAdmin']!=1) {
        	Exception::error404();
        }
        switch ($action) {
            case "search":
                if (!isset($this->get['term'])) {
                    Connect::$instance->close();
                }
                $id = $this->get['term'];
                $condition = "(t.Name like '%$id%' or t.NameMenu like '%$id%' or t.Id = '$id')";
                
                $obj = new Data("s_Navigator");
                $obj->setConcat("t.Id as id,if (t.NameMenu!='',t.NameMenu,t.Name) as value,concat('/_wepps/navigator',t.Url) as Url");
                $res = $obj->fetch($condition);
                if (!isset($res[0]['Id'])) {
                    Connect::$instance->close();
                }
                $json = json_encode($res,JSON_UNESCAPED_UNICODE);
                header('Content-type:application/json;charset=utf-8');
                echo $json;
                Connect::$instance->close();
                break;
            default:
                Exception::error404();
                break;
        }
    }
}
$request = new RequestNavigatorAd ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);