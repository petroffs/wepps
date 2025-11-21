<?php
namespace WeppsExtensions\Legal;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;

/**
 * Расширение для отображения юридических документов
 *
 * Обрабатывает запросы к разделу юридических документов (политика приватности,
 * пользовательские соглашения и т.д.), отображает список документов или
 * конкретный документ в зависимости от пути.
 *
 * @package WeppsExtensions\Legal
 */
class Legal extends Extension {

    /**
     * Обработка запроса к юридическому разделу
     *
     * В зависимости от текущего пути (Navigator::$pathItem) отображает
     * либо список всех активных документов, либо конкретный документ.
     * Подключает необходимые CSS и JS файлы.
     *
     * @return void
     */
    public function request() {
        $smarty = Smarty::getSmarty ();
        switch (Navigator::$pathItem) {
            case '':
                $this->tpl = 'packages/WeppsExtensions/Legal/Legal.tpl';
                $conditions = "t.IsHidden=0";
                $obj = new Data("Legal");
                $obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
                $res = $obj->fetch($conditions,30,1,"t.Priority");
                $smarty->assign('elements',$res);
                break;
            default:
                $this->tpl = 'packages/WeppsExtensions/Legal/LegalItem.tpl';
                $res = $this->getItem("Legal");
                $smarty->assign('element',$res);
                $conditions = "t.IsHidden=0";
                $obj = new Data("Legal");
                $obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
                $res = $obj->fetch($conditions,30,1,"t.Priority");
                $smarty->assign('elements',$res);
                $smarty->assign('normalView',0);
                break;
        }
        $this->headers->css("/ext/Legal/Legal.{$this->rand}.css");
        $this->headers->js("/ext/Legal/Legal.{$this->rand}.js");
        $smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
        return;
    }
}