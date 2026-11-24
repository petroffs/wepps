<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Смоук-прогоны части bruno-коллекции (.tools/bruno/WeppsPlatformV1) по HTTP.
 *
 * Окружение: клон environments/platform.bru (base_url https://platform.wepps);
 * переопределяется переменной окружения WEPPS_TEST_BASE_URL.
 *
 * Гарантии:
 * - выполняются только GET-запросы с auth: none (плюс auth.login при явно
 *   заданном WEPPS_TEST_PASSWORD) — безопасные чтения, без мутаций данных;
 * - если сервер недоступен — тесты помечаются skipped, чтобы прогон оставался
 *   зелёным в окружении без живого хоста (CI, чистая машина);
 * - секреты (пароли, токены) из vars:secret в тест не попадают.
 */
final class BrunoSmokeTest extends TestCase
{
    private const BRUNO_ROOT = __DIR__ . '/../bruno/WeppsPlatformV1';

    /** Кандидаты на смоук (аналог test.sh): читающие GET без секретов */
    private const GET_REQUESTS = [
        'clientAPP/goods/goods.get.bru',
        'clientAPP/goods/goods.item.get.bru',
        'clientAPP/goods/goods.categories.get.bru',
        'clientAPP/goods/goods.filters.get.bru',
        'clientAPP/news/news.get.bru',
        'clientAPP/news/news.item.get.bru',
        'clientAPP/slides/slides.get.bru',
        'clientAPP/cart/cart.get.bru',
    ];

    /**
     * @return array<string, array{0: string}>
     */
    public static function requestProvider(): array
    {
        $cases = [];
        foreach (self::GET_REQUESTS as $file) {
            $cases[basename($file)] = [$file];
        }

        return $cases;
    }

    #[DataProvider('requestProvider')]
    public function testGetRequest(string $file): void
    {
        $request = $this->parseRequest($file);
        $this->assertNotSame('', $request['url'], "Не найден url в {$file}");

        if ($request['auth'] !== 'none') {
            $this->markTestSkipped("{$file}: запрос требует авторизации, в смоук не входит");
        }

        $response = $this->send($request);
        if ($response === null) {
            $this->markTestSkipped('Сервер недоступен: ' . $this->baseUrl());
        }

        $this->assertSame(200, $response['status'], "{$file}: ожидали 200, получили {$response['status']}");
        $this->assertNotSame('', trim($response['body']), "{$file}: пустое тело ответа");
        $decoded = json_decode($response['body'], true);
        $this->assertIsArray($decoded, "{$file}: ответ не JSON");
    }

    /**
     * auth.login — по опциональной переменной окружения WEPPS_TEST_PASSWORD.
     * Без пароля тест пропускается (секреты в репозитории не хранятся).
     */
    public function testAuthLogin(): void
    {
        $password = getenv('WEPPS_TEST_PASSWORD');
        if (!is_string($password) || $password === '') {
            $this->markTestSkipped('Не задан WEPPS_TEST_PASSWORD');
        }

        $vars = $this->envVars();
        $request = $this->parseRequest('auth/auth.login.bru');
        $request['body'] = strtr((string) $request['body'], [
            '{{user_login}}' => (string) ($vars['user_login'] ?? ''),
            '{{user_password}}' => $password,
        ]);

        $response = $this->send($request);
        if ($response === null) {
            $this->markTestSkipped('Сервер недоступен: ' . $this->baseUrl());
        }

        $this->assertSame(200, $response['status'], "auth.login: ожидали 200, получили {$response['status']}");
        $decoded = json_decode($response['body'], true);
        $this->assertIsArray($decoded, 'auth.login: ответ не JSON');
        $this->assertArrayHasKey('data', $decoded, 'auth.login: нет data в ответе');
        $this->assertArrayHasKey('accessToken', (array) $decoded['data'], 'auth.login: нет accessToken');
    }

    // ------------------------------------------------------------------

    private function baseUrl(): string
    {
        $fromEnv = getenv('WEPPS_TEST_BASE_URL');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return rtrim($fromEnv, '/');
        }

        return rtrim((string) ($this->envVars()['base_url'] ?? ''), '/');
    }

    /**
     * Клон окружения platform.bru, при отсутствии — local.bru.
     *
     * @return array<string, string>
     */
    private function envVars(): array
    {
        foreach (['platform.bru', 'local.bru'] as $name) {
            $path = self::BRUNO_ROOT . '/environments/' . $name;
            if (!is_file($path)) {
                continue;
            }
            $content = (string) file_get_contents($path);
            if (preg_match('/vars\s*\{(.*?)\}/s', $content, $m) !== 1) {
                continue;
            }
            $vars = [];
            foreach (explode("\n", $m[1]) as $line) {
                if (preg_match('/^\s*([A-Za-z0-9_]+)\s*:\s*(.*?)\s*$/', $line, $pair) === 1) {
                    $vars[$pair[1]] = $pair[2];
                }
            }
            if ($vars !== []) {
                return $vars;
            }
        }

        return [];
    }

    /**
     * Разбор .bru: метод, url, auth, тело (body:json).
     *
     * @return array{method: string, url: string, auth: string, body: string}
     */
    private function parseRequest(string $file): array
    {
        $path = self::BRUNO_ROOT . '/' . $file;
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        $method = 'get';
        if (preg_match('/^\s*(get|post|put|delete)\s*\{/mi', $content, $m) === 1) {
            $method = strtolower($m[1]);
        }

        $url = '';
        if (preg_match('/^\s*url:\s*(\S+)\s*$/mi', $content, $m) === 1) {
            $url = trim($m[1]);
        }

        $auth = 'none';
        if (preg_match('/^\s*auth:\s*(\S+)\s*$/mi', $content, $m) === 1) {
            $auth = strtolower($m[1]);
        }

        $body = '';
        if (preg_match('/body:json\s*\{(.*?)\n\}/s', $content, $m) === 1) {
            $body = trim($m[1]);
        }

        $vars = $this->envVars();
        $vars['base_url'] = $this->baseUrl();
        $map = [];
        foreach ($vars as $key => $value) {
            $map['{{' . $key . '}}'] = $value;
        }
        $url = strtr($url, $map);
        $body = strtr($body, $map);
        // Фильтры свойств f_{id} привязаны к данным конкретного окружения:
        // на другом стенде id/алиасы не совпадают (400 Unexpected field) — в смоук не шлём
        $urlParts = explode('?', $url, 2);
        if (($urlParts[1] ?? '') !== '') {
            $pairs = array_filter(
                explode('&', $urlParts[1]),
                static fn(string $pair): bool => preg_match('/^f_\d+=/', $pair) !== 1,
            );
            $url = $urlParts[0] . ($pairs !== [] ? '?' . implode('&', $pairs) : '');
        }
        // '|' в query допустим сервером, но транспорт его экранирует
        $url = str_replace('|', '%7C', $url);

        return ['method' => $method, 'url' => $url, 'auth' => $auth, 'body' => $body];
    }

    /**
     * @param array{method: string, url: string, auth: string, body: string} $request
     * @return array{status: int, body: string}|null null — сервер недоступен
     */
    private function send(array $request): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($request['method']),
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
                'content' => $request['body'] !== '' ? $request['body'] : null,
            ],
            // Тестовый стенд с локальным/самоподписанным сертификатом
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $body = @file_get_contents($request['url'], false, $context);
        if ($body === false) {
            return null;
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                $status = (int) $m[1];
            }
        }

        return ['status' => $status, 'body' => (string) $body];
    }
}
