<?php
namespace WeppsExtensions\Legal;

use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;

/**
 * Утилиты для работы с юридическими документами и соглашениями
 *
 * Класс предоставляет функциональность для управления настройками приватности,
 * рендеринга модальных окон с соглашениями о cookies.
 *
 * @package WeppsExtensions\Legal
 */
class LegalUtils
{
    /**
     * @var TemplateHeaders Объект для управления заголовками шаблонов
     */
    protected $headers;

    /**
     * Конструктор класса LegalUtils
     *
     * @param TemplateHeaders $headers Объект для управления заголовками
     */
    public function __construct(TemplateHeaders &$headers)
    {
        $this->headers = $headers;
    }

    /**
     * Получить текущие соглашения пользователя о политике приватности
     *
     * Возвращает статус согласий на использование cookies из Utils::cookies().
     *
     * @return array Массив с соглашениями ['default' => string, 'analytics' => string]
     */
    public function getPrivacyPolicyAgreements(): array
    {
        return [
            'default' =>  Utils::cookies('wepps_cookies_default') ?? 'false',
            'analytics' => Utils::cookies('wepps_cookies_analytics') ?? 'true'
        ];
    }

    /**
     * Рендерить модальное окно с соглашениями
     *
     * Генерирует HTML модального окна с текущими настройками приватности,
     * подключает необходимые CSS и JS файлы.
     *
     * @return string HTML содержимое модального окна
     */
    public function renderModal(): string
    {
        $smarty = Smarty::getSmarty();
        $smarty->assign('privacyPolicyAgreements', $this->getPrivacyPolicyAgreements());
        $this->headers->css("/ext/Legal/LegalModal.{$this->headers::$rand}.css");
        $this->headers->js("/ext/Legal/LegalModal.{$this->headers::$rand}.js");
        return $smarty->fetch(__DIR__ . '/LegalModal.tpl');
    }
}