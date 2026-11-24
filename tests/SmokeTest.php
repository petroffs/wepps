<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WeppsCore\Utils;

/**
 * Смоук-тесты для прогонов после массовых правок.
 *
 * Покрывает два класса регрессий:
 * 1. автозагрузку классов всех пакетов проекта (сломанные use/namespace/переименования);
 * 2. чистые хелперы безопасности (safeRelPath / safeChars / json) — их контракт
 *    не должен меняться при рефакторингах.
 *
 * БД и сеть не используются.
 */
final class SmokeTest extends TestCase
{
    public function testProjectClassesAutoload(): void
    {
        $classes = [
            \WeppsCore\Connect::class,
            \WeppsCore\Utils::class,
            \WeppsCore\Request::class,
            \WeppsCore\Validator::class,
            \WeppsCore\Navigator::class,
            \WeppsCore\Language::class,
            \WeppsCore\Exception::class,
            \WeppsAdmin\Lists\Lists::class,
            \WeppsAdmin\Admin\Admin::class,
            \WeppsExtensions\Cart\CartUtils::class,
            \WeppsExtensions\Cart\Payments\Payments::class,
            \WeppsExtensions\Addons\Images\Images::class,
            \WeppsExtensions\Addons\Files\Files::class,
            \WeppsExtensions\Template\Template::class,
            \WeppsExtensions\Home\Home::class,
        ];
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Класс не найден автозагрузчиком: {$class}");
        }
    }

    public function testSafeRelPathRejectsTraversalAndSchemes(): void
    {
        $this->assertNull(Utils::safeRelPath(''), 'пустой путь');
        $this->assertNull(Utils::safeRelPath('../etc/passwd'), 'path traversal');
        $this->assertNull(Utils::safeRelPath('pic/../../secret'), 'traversal в середине');
        $this->assertNull(Utils::safeRelPath('/absolute/path'), 'абсолютный путь');
        $this->assertNull(Utils::safeRelPath('http://evil.example/x'), 'URL-схема');
        $this->assertNull(Utils::safeRelPath('file://etc/passwd'), 'file-схема');
    }

    public function testSafeRelPathAcceptsNormalPaths(): void
    {
        $this->assertSame('pic/lists/Lists/000722_Images_1.jpg', Utils::safeRelPath('pic/lists/Lists/000722_Images_1.jpg'));
        $this->assertSame('000722_x.jpg', Utils::safeRelPath('000722_x.jpg'));
        $this->assertSame('Admin/Forms/tpl.tpl', Utils::safeRelPath('Admin/Forms/tpl.tpl'));
    }

    public function testSafeCharsStripsMarkupAndKeepsCyrillic(): void
    {
        $clean = Utils::safeChars('<script>alert(1)</script> Свитер');
        $this->assertStringNotContainsString('<', $clean);
        $this->assertStringNotContainsString('>', $clean);
        $this->assertStringNotContainsString('"', $clean);
        $this->assertStringContainsString('Свитер', $clean, 'кириллица должна сохраняться');
    }

    public function testJsonEscapesHtmlButRoundTripsData(): void
    {
        $data = ['html' => '<b>Цена &amp;</b>', 'n' => 1];
        $json = Utils::json($data);
        $this->assertStringNotContainsString('<', $json, 'JSON_HEX_TAG должен экранировать <');
        $this->assertStringNotContainsString('&amp;', $json, 'JSON_HEX_AMP должен экранировать &');
        $this->assertSame($data, json_decode($json, true), 'данные после парсинга не искажаются');
    }
}
