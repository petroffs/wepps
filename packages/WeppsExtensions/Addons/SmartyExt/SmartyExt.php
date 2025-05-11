<?php
namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Extension\Base;

class SmartyExtWepps extends Base {

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {
        switch ($modifier) {
            case 'weppstest': return new AdModifierCompilerWepps();
        }
        return null;
    }
}