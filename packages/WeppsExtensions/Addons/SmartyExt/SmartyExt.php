<?php
namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Extension\Base;

class SmartyExt extends Base {

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {
        switch ($modifier) {
            case 'weppstest': return new AdModifierCompiler();
        }
        return null;
    }
}