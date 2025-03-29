<?php
namespace WeppsAdmin\NavigatorAd;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestNavigatorAdWepps extends RequestWepps {
    public function request($action="") {
        $this->tpl = '';
        if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
        	ExceptionWepps::error404();
        }
        switch ($action) {
            case "search":
                if (!isset($this->get['term'])) {
                    ConnectWepps::$instance->close();
                }
                $id = $this->get['term'];
                $condition = "(t.Name like '%$id%' or t.NameMenu like '%$id%' or t.Id = '$id')";
                
                $obj = new DataWepps("s_Navigator");
                $obj->setConcat("t.Id as id,if (t.NameMenu!='',t.NameMenu,t.Name) as value,concat('/_pps/navigator',t.Url) as Url");
                $res = $obj->getMax($condition);
                if (!isset($res[0]['Id'])) {
                    ConnectWepps::$instance->close();
                }
                $json = TextTransformsWepps::getJsonCyr($res);
                header('Content-type:application/json;charset=utf-8');
                echo $json;
                ConnectWepps::$instance->close();
                break;
            default:
                ExceptionWepps::error404();
                break;
        }
    }
}
$request = new RequestNavigatorAdWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);