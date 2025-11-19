<?php
namespace WeppsExtensions\Legal;

use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;

class LegalUtils
{
    protected $headers;
    public function __construct(TemplateHeaders &$headers)
    {
        $this->headers = $headers;
    }
    private function getPrivacyPolicyAgreements(): array
    {
        return [
            'default' => $_COOKIE['wepps_cookies_default'] ?? 'false',
            'analytics' => $_COOKIE['wepps_cookies_analytics'] ?? 'true'
        ];
    }
    public function renderModal(): string
    {
        $smarty = Smarty::getSmarty();
        $smarty->assign('privacyPolicyAgreements', $this->getPrivacyPolicyAgreements());
        $this->headers->css("/ext/Legal/LegalModal.{$this->headers::$rand}.css");
        // $this->headers->js("/ext/Legal/LegalModal.{$this->headers::$rand}.js");
        return $smarty->fetch(__DIR__ . '/LegalModal.tpl');
    }
}