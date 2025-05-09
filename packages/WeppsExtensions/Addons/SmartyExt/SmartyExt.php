<?php
namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Extension\Base;
use WeppsCore\Connect\ConnectWepps;

class SmartyExtWepps extends Base {

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {
        switch ($modifier) {
            case 'wepps': return new AdModifierCompilerWepps();
        }
        return null;
    }
}