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