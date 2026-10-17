<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Connect;
use WeppsExtensions\Addons\Rest\RestConfig;

/**
 * Генератор документации REST API.
 *
 * Собирает конфигурацию из RestConfig::getConfig() и формирует три документа
 * в папке .docs/restapi:
 * - auth      — авторизация для трёх типов REST (ClientAPP, M2M, Wepps);
 * - clientapp — клиентское API v1 (витрина/мобильное приложение);
 * - m2m     — машинный CRUD-доступ к таблицам платформы.
 *
 * Каждый документ сохраняется в двух форматах:
 * - HTML — OpenAPI 3.1 + Swagger UI (интерактивный, кнопка «Try it out»);
 * - Markdown — версия для чтения и diff в репозитории.
 */
class RestApiDocs
{
	/**
	 * Эндпоинты v1, относящиеся к авторизации (выносятся в отдельный документ).
	 */
	private const AUTH_ENDPOINTS = [
		'auth.login',
		'auth.confirm',
		'auth.refresh',
		'auth.logout',
		'register',
		'register.confirm',
		'profile.password-reset',
	];

	/**
	 * HTTP-методы в порядке документирования.
	 */
	private const HTTP_METHODS = ['get', 'post', 'put', 'delete'];

	/**
	 * Человекочитаемые названия версий API (для тегов).
	 */
	private const VERSION_TAGS = [
		'v1' => 'ClientAPP',
		'm2m' => 'M2M',
		'wepps' => 'Wepps Admin',
	];

	private string $generatedAt = '';

	/**
	 * Сформировать документацию: 3 документа × (HTML + Markdown).
	 *
	 * @return string[] Абсолютные пути созданных файлов
	 * @throws \RuntimeException При ошибке создания папки или записи файла
	 */
	public function generate(): array
	{
		$config = RestConfig::getConfig();
		$this->generatedAt = date('Y-m-d H:i');

		$dir = $this->docsDir();
		$files = [];

		foreach ($this->docs() as $key => $doc) {
			$items = $this->collectEndpoints($config, $doc);
			if (empty($items)) {
				throw new \RuntimeException("Документ «{$doc['title']}» не содержит эндпоинтов");
			}
			$spec = $this->buildSpec($doc, $items);
			$files[] = $this->writeFile($dir . '/' . $key . '.html', $this->renderHtml($doc, $spec));
			$files[] = $this->writeFile($dir . '/' . $key . '.md', $this->renderMarkdown($key, $doc, $items));
		}

		return $files;
	}

	/**
	 * Определения трёх документов: заголовок, вступление (Markdown), версии и фильтр эндпоинтов.
	 *
	 * @return array<string, array>
	 */
	private function docs(): array
	{
		$authIntro = <<<MD
Авторизация REST API едина для всех типов. Токены — JWT (`typ=auth`)
и передаются в заголовке `Authorization: Bearer <token>`.

### 1. ClientAPP (/rest/v1) — клиентское API

Используется клиентскими приложениями (витриной, мобильным приложением). Токены
выдаются по логину и паролю:

```http
POST /rest/v1/auth.login
Content-Type: application/json

{"data": {"login": "admin@wepps.loc", "password": "secret"}}
```

Ответ содержит `access_token` (1 час) и `refresh_token` (30 дней):

- обновление пары токенов — `POST /rest/v1/auth.refresh`;
- подтверждение входа (двухфакторный режим `CONFIRM_AUTH`) — `POST /rest/v1/auth.confirm`;
- выход из сессии — `POST /rest/v1/auth.logout`.

### 2. M2M (/rest/m2m) — машинное взаимодействие

M2M-эндпоинты (`/rest/m2m/*`) **не имеют собственного эндпоинта авторизации**:
используется тот же `access_token`, что и в ClientAPP, полученный учётной записью
с правами `UserPermissions = 1` (это проверяется на каждом M2M-эндпоинте).
MD;

		$clientappIntro = <<<MD
Клиентское API v1 (ClientAPP) — эндпоинты витрины и мобильного приложения:
авторизация, профиль, товары, корзина, заказы, новости, слайды.

- **Авторизация:** Bearer-токен из `POST /rest/v1/auth.login` — подробности в [auth.md](./auth.md).
- **Обёртка запроса:** тело запроса передаётся как `{"data": {...}}`;
  GET-параметры — в query-строке.
- **Обёртка ответа:** `{"status": 200, "message": "...", "data": ...}`.
MD;

		$m2mIntro = <<<MD
M2M API — машинный CRUD-доступ к таблицам платформы (пользователи, заказы,
товары, файлы). Доступ только для учётных записей с `UserPermissions = 1`.

Особенности:

- **Авторизация:** Bearer-токен из `POST /rest/v1/auth.login` — подробности в [auth.md](./auth.md).
- **GET:** пагинация `page` / `limit`.
- **POST:** одиночный объект или batch-массив (макс. 100) → `201` для одиночного,
  `207 Multi-Status` для batch с per-item статусами; часть эндпоинтов работает
  асинхронно → `202 Accepted` с `task_id` (очередь `s_Tasks`).
- **PUT:** обновление по `id` из тела JSON (`{"id": 123, ...}`), также поддерживается batch.
- **DELETE:** только batch-формат тела `{"data": [123, 456, ...]}`;
  `?id=` в query-строке не поддерживается.
MD;

		return [
			'auth' => [
				'title' => 'Wepps REST API — Авторизация',
				'description' => $authIntro,
				'versions' => ['v1'],
				'tagBy' => 'version',
				'filter' => function (string $version, string $name): bool {
					return in_array($name, self::AUTH_ENDPOINTS, true);
				},
			],
			'clientapp' => [
				'title' => 'Wepps ClientAPP REST API (v1)',
				'description' => $clientappIntro,
				'versions' => ['v1'],
				'tagBy' => 'resource',
				'filter' => function (string $version, string $name): bool {
					return !in_array($name, self::AUTH_ENDPOINTS, true);
				},
			],
			'm2m' => [
				'title' => 'Wepps M2M REST API',
				'description' => $m2mIntro,
				'versions' => ['m2m'],
				'tagBy' => 'resource',
				'filter' => null,
			],
		];
	}

	/**
	 * Собрать эндпоинты документа из конфигурации RestConfig.
	 *
	 * @param array $config Конфигурация RestConfig::getConfig()
	 * @param array $doc Определение документа
	 * @return array[] Список ['version', 'method', 'name', 'config']
	 */
	private function collectEndpoints(array $config, array $doc): array
	{
		$items = [];
		foreach ($doc['versions'] as $version) {
			foreach (self::HTTP_METHODS as $method) {
				foreach ($config[$version][$method] ?? [] as $name => $cfg) {
					if (!is_array($cfg)) {
						continue;
					}
					if ($doc['filter'] !== null && !($doc['filter'])($version, $name)) {
						continue;
					}
					$items[] = [
						'version' => $version,
						'method' => $method,
						'name' => $name,
						'config' => $cfg,
					];
				}
			}
		}
		return $items;
	}

	// -------------------------------------------------------------------------
	// OpenAPI
	// -------------------------------------------------------------------------

	/**
	 * Построить спецификацию OpenAPI 3.1 для документа.
	 *
	 * @param array $doc Определение документа
	 * @param array $items Эндпоинты (см. collectEndpoints())
	 * @return array Спецификация OpenAPI
	 */
	private function buildSpec(array $doc, array $items): array
	{
		$paths = [];
		$tags = [];

		foreach ($items as $item) {
			$version = $item['version'];
			$method = $item['method'];
			$name = $item['name'];
			$cfg = $item['config'];

			$tag = $this->tagFor($doc, $version, $name);
			$tags[$tag] = ['name' => $tag];

			$path = '/rest/' . $version . '/' . $name;
			$paths[$path][strtolower($method)] = $this->buildOperation($tag, $cfg, $version, $method, $name);
		}

		// В HTML-версии ссылки вводных текстов ведут на HTML-документы, в Markdown — на .md
		$description = str_replace('[auth.md](./auth.md)', '[auth.html](./auth.html)', $doc['description']);
		$description .= "\n\n---\n_Сгенерировано: " . $this->generatedAt . '_';

		return [
			'openapi' => '3.1.0',
			'info' => [
				'title' => $doc['title'],
				'version' => '1.0.0',
				'description' => $description,
			],
			// Без зашитого домена: при открытии по http/https JS подставит текущий origin
			'servers' => [['url' => '/']],
			'tags' => array_values($tags),
			'paths' => $paths,
			'components' => [
				'securitySchemes' => [
					'bearerAuth' => [
						'type' => 'http',
						'scheme' => 'bearer',
						'bearerFormat' => 'JWT',
						'description' => 'JWT access_token (typ=auth), полученный через POST /rest/v1/auth.login',
					],
				],
				'schemas' => [
					'Envelope' => [
						'type' => 'object',
						'description' => 'Стандартная обёртка ответа',
						'properties' => [
							'status' => ['type' => 'integer', 'example' => 200],
							'message' => ['type' => 'string', 'example' => 'OK'],
							'data' => ['description' => 'Полезная нагрузка (тип зависит от эндпоинта)'],
						],
					],
				],
			],
		];
	}

	/**
	 * Построить операцию OpenAPI для одного эндпоинта.
	 */
	private function buildOperation(string $tag, array $cfg, string $version, string $method, string $name): array
	{
		$note = (string) ($cfg['note'] ?? $name);
		$authRequired = !empty($cfg['auth_required']) || isset($cfg['role_required']);
		$authOptional = !empty($cfg['auth_optional']) && !$authRequired;

		$description = $note;
		if (isset($cfg['role_required'])) {
			$description .= "\n\nТребуемые права (UserPermissions): " . implode(', ', $cfg['role_required']) . '.';
		} elseif ($authRequired) {
			$description .= "\n\nТребуется заголовок `Authorization: Bearer <token>`.";
		}
		if ($authOptional) {
			$description .= "\n\nАвторизация опциональна: для персонализированных данных передайте Bearer-токен.";
		}
		if (!empty($cfg['async'])) {
			$description .= "\n\n**Async:** возвращается `202 Accepted` с `task_id`, результат обрабатывается "
				. "в очереди `s_Tasks`.";
		}
		if (!empty($cfg['custom_response'])) {
			$description .= "\n\nОтвет формируется в произвольном формате (без обёртки `status/message/data`).";
		}

		$op = [
			'summary' => $this->makeSummary($note),
			'description' => $description,
			'tags' => [$tag],
			'operationId' => $version . '-' . $method . '-' . str_replace('.', '-', $name),
			'responses' => $this->buildResponses($cfg, $version, $method),
		];

		if ($authRequired) {
			$op['security'] = [['bearerAuth' => []]];
		} elseif ($authOptional) {
			$op['security'] = [['bearerAuth' => []], []];
		}

		$queryRules = $cfg['query_validation'] ?? [];
		if (!empty($queryRules)) {
			$parameters = [];
			foreach ($queryRules as $field => $rule) {
				if (strpos($field, '[]') !== false || !is_array($rule)) {
					continue;
				}
				$parameters[] = [
					'name' => $field,
					'in' => 'query',
					'required' => !empty($rule['required']),
					'schema' => $this->mapType($rule['type'] ?? 'string'),
					'description' => 'Тип: `' . ($rule['type'] ?? 'string') . '`',
				];
			}
			if (!empty($parameters)) {
				$op['parameters'] = $parameters;
			}
		}

		$validation = $cfg['validation'] ?? [];
		if (in_array($method, ['post', 'put', 'delete'], true) && !empty($validation)) {
			$body = $this->buildBody($validation, $version, $method);
			if ($body !== null) {
				$op['requestBody'] = [
					'required' => $body['required'],
					'content' => [
						'application/json' => [
							'schema' => $body['schema'],
							'example' => $body['example'],
						],
					],
				];
			}
		}

		return $op;
	}

	/**
	 * Ответы операции: базовый 200 + коды, зависящие от конфига эндпоинта.
	 */
	private function buildResponses(array $cfg, string $version, string $method): array
	{
		$envelope = ['$ref' => '#/components/schemas/Envelope'];
		$schema = !empty($cfg['custom_response']) ? ['type' => 'object'] : $envelope;

		$responses = [
			'200' => ['description' => 'Успешный ответ', 'content' => ['application/json' => ['schema' => $schema]]],
		];

		if ($version === 'm2m' && $method === 'post') {
			$responses['201'] = ['description' => 'Создано (одиночный объект)', 'content' => ['application/json' => ['schema' => $envelope]]];
			$responses['207'] = ['description' => 'Multi-Status: результат по каждому элементу batch', 'content' => ['application/json' => ['schema' => $envelope]]];
		}
		if (!empty($cfg['async'])) {
			$responses['202'] = ['description' => 'Accepted: задача поставлена в очередь s_Tasks', 'content' => ['application/json' => ['schema' => $envelope]]];
		}

		$responses['400'] = ['description' => 'Ошибка валидации входных данных', 'content' => ['application/json' => ['schema' => $envelope]]];

		if (!empty($cfg['auth_required']) || isset($cfg['role_required']) || !empty($cfg['auth_optional'])) {
			$responses['401'] = ['description' => 'Токен отсутствует, недействителен или просрочен', 'content' => ['application/json' => ['schema' => $envelope]]];
		}
		if (isset($cfg['role_required'])) {
			$responses['403'] = ['description' => 'Недостаточно прав (UserPermissions)', 'content' => ['application/json' => ['schema' => $envelope]]];
		}

		return $responses;
	}

	/**
	 * Схема и пример тела запроса.
	 *
	 * Тело всегда оборачивается в {"data": ...}. Для M2M POST/PUT data может быть
	 * одиночным объектом или batch-массивом.
	 *
	 * @return array|null ['schema', 'example', 'required'] или null, если правил нет
	 */
	private function buildBody(array $rules, string $version, string $method): ?array
	{
		if (empty($rules)) {
			return null;
		}

		// Batch-удаление: {"data": [1, 2, 3]}
		if (isset($rules['ARRAY'])) {
			$itemType = $this->mapType($rules['ARRAY']['type'] ?? 'int');
			$dataSchema = ['type' => 'array', 'items' => $itemType];
			$required = !empty($rules['ARRAY']['required']);
			$schema = ['type' => 'object', 'properties' => ['data' => $dataSchema]];
			if ($required) {
				$schema['required'] = ['data'];
			}
			return ['schema' => $schema, 'example' => ['data' => [1, 2]], 'required' => $required];
		}

		[$properties, $requiredFields] = $this->objectFromRules($rules);
		if (empty($properties)) {
			return null;
		}

		$objSchema = ['type' => 'object', 'properties' => $properties];
		if (!empty($requiredFields)) {
			$objSchema['required'] = $requiredFields;
		}
		$objExample = $this->exampleFromSchema($objSchema);
		if ($objExample === []) {
			$objExample = (object) [];
		}

		if ($version === 'm2m' && in_array($method, ['post', 'put'], true)) {
			$dataSchema = [
				'oneOf' => [
					$objSchema,
					['type' => 'array', 'items' => $objSchema],
				],
				'description' => 'Одиночный объект или batch-массив (макс. 100 элементов)',
			];
			$example = ['data' => [$objExample]];
		} else {
			$dataSchema = $objSchema;
			$example = ['data' => $objExample];
		}

		$schema = ['type' => 'object', 'properties' => ['data' => $dataSchema]];
		$required = !empty($requiredFields);
		if ($required) {
			$schema['required'] = ['data'];
		}

		return ['schema' => $schema, 'example' => $example, 'required' => $required];
	}

	/**
	 * Преобразовать правила валидации в свойства JSON-объекта (с поддержкой dot-notation `items[].field`).
	 *
	 * @return array [properties, required]
	 */
	private function objectFromRules(array $rules): array
	{
		$properties = [];
		$required = [];
		$nested = [];

		foreach ($rules as $key => $rule) {
			if ($key === 'ARRAY' || !is_array($rule)) {
				continue;
			}
			// Вложенные правила: items[], items[].field, data.items[].field
			if (preg_match('/^(.+)\[\](?:\.([A-Za-z_]\w*))?$/', $key, $m)) {
				$nested[$m[1]][$m[2] ?? ''] = $rule;
				continue;
			}
			$properties[$key] = $this->mapType($rule['type'] ?? 'string');
			if (!empty($rule['required'])) {
				$required[] = $key;
			}
		}

		foreach ($nested as $path => $fields) {
			$all = explode('.', $path);
			$top = $all[0];
			$last = array_pop($all);

			$cursor = &$properties;
			foreach ($all as $segment) {
				if (!isset($cursor[$segment]) || ($cursor[$segment]['type'] ?? '') !== 'object') {
					$cursor[$segment] = ['type' => 'object', 'properties' => []];
				}
				if (!isset($cursor[$segment]['properties'])) {
					$cursor[$segment]['properties'] = [];
				}
				$cursor = &$cursor[$segment]['properties'];
			}
			if (!isset($cursor[$last])) {
				$cursor[$last] = [];
			}
			if (($cursor[$last]['type'] ?? '') !== 'array') {
				$cursor[$last] = ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [], 'required' => []]];
			}
			if (!isset($cursor[$last]['items'])) {
				$cursor[$last]['items'] = ['type' => 'object', 'properties' => [], 'required' => []];
			}

			$itemsSchema = &$cursor[$last]['items'];
			foreach ($fields as $field => $rule) {
				if ($field === '') {
					continue; // Правило на весь массив — узел уже создан
				}
				$itemsSchema['properties'][$field] = $this->mapType($rule['type'] ?? 'string');
				if (!empty($rule['required'])) {
					$itemsSchema['required'][] = $field;
				}
			}
			foreach ($fields as $rule) {
				if (!empty($rule['required']) && !in_array($top, $required, true)) {
					$required[] = $top;
					break;
				}
			}
			unset($itemsSchema);
			unset($cursor);
		}

		if (isset($properties['data']) && empty($properties['data']['properties'])) {
			// Простое правило 'data' => object без вложенных полей
			unset($properties['data']['properties']);
		}

		return [$properties, $required];
	}

	/**
	 * Пример значения по схеме (только обязательные поля, не более 12 свойств).
	 *
	 * @param array $schema Схема OpenAPI
	 * @return mixed Значение-пример
	 */
	private function exampleFromSchema(array $schema)
	{
		if (array_key_exists('example', $schema)) {
			return $schema['example'];
		}
		if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
			return $this->exampleFromSchema($schema['oneOf'][0]);
		}
		$type = $schema['type'] ?? null;

		if ($type === 'array') {
			return [$this->exampleFromSchema($schema['items'] ?? ['type' => 'string'])];
		}
		if ($type === 'object') {
			$out = [];
			$required = $schema['required'] ?? [];
			foreach ($schema['properties'] ?? [] as $key => $prop) {
				if (!empty($required) && !in_array($key, $required, true)) {
					continue;
				}
				if (!is_array($prop)) {
					continue;
				}
				$out[$key] = $this->exampleFromSchema($prop);
				if (count($out) >= 12) {
					break;
				}
			}
			return $out;
		}

		return null;
	}

	/**
	 * Схема OpenAPI для типа валидации.
	 */
	private function mapType(string $type): array
	{
		if (str_ends_with($type, '[]')) {
			return ['type' => 'array', 'items' => $this->mapType(substr($type, 0, -2))];
		}
		switch ($type) {
			case 'int':
			case 'int2':
				return ['type' => 'integer', 'example' => 1];
			case 'float':
			case 'float2':
				return ['type' => 'number', 'example' => 99.9];
			case 'email':
				return ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'];
			case 'date':
				return ['type' => 'string', 'description' => 'Дата или дата-время (2026-01-15 или 2026-01-15 12:00:00)', 'example' => '2026-01-15'];
			case 'guid':
				return ['type' => 'string', 'format' => 'uuid', 'example' => 'a6a5a7b0-520a-fb8d-4b24-23451de424e6'];
			case 'phone':
				return ['type' => 'string', 'example' => '79001234567'];
			case 'barcode':
				return ['type' => 'string', 'example' => '4600000000001'];
			case 'object':
				return ['type' => 'object'];
			default:
				return ['type' => 'string', 'example' => 'string'];
		}
	}

	/**
	 * Короткое описание операции — первое предложение из note.
	 */
	private function makeSummary(string $note): string
	{
		$parts = preg_split('/[.(]/u', trim($note), 2);
		$summary = trim($parts[0] ?? $note);
		if ($summary === '') {
			$summary = trim($note);
		}
		return mb_strlen($summary) > 120 ? mb_substr($summary, 0, 117) . '…' : $summary;
	}

	/**
	 * Тег операции: по версии API или по ресурсу (часть имени до первой точки).
	 */
	private function tagFor(array $doc, string $version, string $name): string
	{
		if ($doc['tagBy'] === 'version') {
			return self::VERSION_TAGS[$version] ?? $version;
		}
		$pos = strpos($name, '.');
		$tag = $pos === false ? $name : substr($name, 0, $pos);
		if ($tag === 'register') {
			return 'register';
		}
		return $tag;
	}

	// -------------------------------------------------------------------------
	// HTML (Swagger UI)
	// -------------------------------------------------------------------------

	/**
	 * Отрисовать HTML-страницу со встроенной спецификацией и Swagger UI (CDN).
	 */
	private function renderHtml(array $doc, array $spec): string
	{
		$title = htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8');
		$specJson = json_encode(
			$spec,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		if ($specJson === false) {
			throw new \RuntimeException('Не удалось сериализовать OpenAPI-спецификацию: ' . json_last_error_msg());
		}

		return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
<style>
body { margin: 0; background: #fafafa; }
.swagger-ui .topbar { display: none; }
</style>
</head>
<body>
<div id="swagger-ui"></div>
<script id="openapi-spec" type="application/json">{$specJson}</script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
window.onload = function () {
	var spec = JSON.parse(document.getElementById('openapi-spec').textContent);
	// Домен при открытии по http/https — текущий, а не зашитый при генерации
	if (window.location.protocol === 'http:' || window.location.protocol === 'https:') {
		spec.servers = [{ url: window.location.origin }];
	}
	window.ui = SwaggerUIBundle({
		spec: spec,
		dom_id: '#swagger-ui',
		deepLinking: true,
		docExpansion: 'list',
		defaultModelsExpandDepth: 1,
		persistAuthorization: true,
		tryItOutEnabled: true
	});
};
</script>
</body>
</html>
HTML;
	}

	// -------------------------------------------------------------------------
	// Markdown
	// -------------------------------------------------------------------------

	/**
	 * Отрисовать Markdown-версию документа.
	 *
	 * @param string $key Ключ документа (auth/clientapp/m2m)
	 * @param array $doc Определение документа
	 * @param array $items Эндпоинты
	 */
	private function renderMarkdown(string $key, array $doc, array $items): string
	{
		$out = [];
		$out[] = '# ' . $doc['title'];
		$out[] = '';
		$out[] = '- **Базовый URL:** `/rest/...` — относительно текущего домена сайта '
			. '(в HTML-версии домен определяется автоматически при открытии)';
		$out[] = '- **Сгенерировано:** ' . $this->generatedAt;
		$out[] = '- **Обёртка ответа:** `{"status": 200, "message": "OK", "data": ...}`';
		$out[] = '- **HTML-версия (Swagger UI):** [`' . $key . '.html`](./' . $key . '.html)';
		$out[] = '';
		$out[] = $doc['description'];
		$out[] = '';
		$out[] = '## Примеры ответов';
		$out[] = '';
		$out[] = '**Успех:**';
		$out[] = '';
		$out[] = '```json';
		$out[] = '{';
		$out[] = '    "status": 200,';
		$out[] = '    "message": "OK",';
		$out[] = '    "data": []';
		$out[] = '}';
		$out[] = '```';
		$out[] = '';
		$out[] = '**Ошибка:**';
		$out[] = '';
		$out[] = '```json';
		$out[] = '{';
		$out[] = '    "status": 400,';
		$out[] = '    "message": "Field \'name\' is required",';
		$out[] = '    "data": null';
		$out[] = '}';
		$out[] = '```';
		$out[] = '';
		$out[] = '## Обзор эндпоинтов';
		$out[] = '';
		$out[] = '| Метод | Путь | Авторизация | Описание |';
		$out[] = '|---|---|---|---|';
		foreach ($items as $item) {
			$path = '/rest/' . $item['version'] . '/' . $item['name'];
			$note = (string) ($item['config']['note'] ?? $item['name']);
			$out[] = '| ' . strtoupper($item['method'])
				. ' | `' . $path . '`'
				. ' | ' . $this->mdEscape($this->authLabel($item['config']))
				. ' | ' . $this->mdEscape($this->makeSummary($note)) . ' |';
		}
		$out[] = '';

		// Детальные секции, сгруппированные по тегу
		$groups = [];
		foreach ($items as $item) {
			$tag = $this->tagFor($doc, $item['version'], $item['name']);
			$groups[$tag][] = $item;
		}
		foreach ($groups as $tag => $group) {
			$out[] = '## ' . $tag;
			$out[] = '';
			foreach ($group as $item) {
				$this->renderMarkdownEndpoint($out, $item);
			}
		}

		return implode("\n", $out) . "\n";
	}

	/**
	 * Дополнить вывод Markdown разделом одного эндпоинта.
	 *
	 * @param array $out Выходной массив строк (по ссылке)
	 * @param array $item Эндпоинт
	 */
	private function renderMarkdownEndpoint(array &$out, array $item): array
	{
		$cfg = $item['config'];
		$version = $item['version'];
		$method = $item['method'];
		$name = $item['name'];
		$path = '/rest/' . $version . '/' . $name;
		$note = (string) ($cfg['note'] ?? $name);

		$out[] = '### `' . strtoupper($method) . ' ' . $path . '`';
		$out[] = '';
		$out[] = $note;
		$out[] = '';
		$out[] = '- **Авторизация:** ' . $this->authLabel($cfg);
		if (!empty($cfg['async'])) {
			$out[] = '- **Async:** `202 Accepted` + `task_id` (очередь `s_Tasks`)';
		}
		if (!empty($cfg['custom_response'])) {
			$out[] = '- **Формат ответа:** произвольный (без обёртки `status/message/data`)';
		}
		$out[] = '';

		$queryRules = $cfg['query_validation'] ?? [];
		if (!empty($queryRules)) {
			$out[] = '**Query-параметры:**';
			$out[] = '';
			$out[] = '| Имя | Тип | Обязательный |';
			$out[] = '|---|---|---|';
			foreach ($queryRules as $field => $rule) {
				if (!is_array($rule)) {
					continue;
				}
				$out[] = '| `' . $field . '` | `' . ($rule['type'] ?? 'string') . '` | '
					. (!empty($rule['required']) ? 'да' : 'нет') . ' |';
			}
			$out[] = '';
		}

		$validation = $cfg['validation'] ?? [];
		if (in_array($method, ['post', 'put', 'delete'], true) && !empty($validation)) {
			$body = $this->buildBody($validation, $version, $method);
			if ($body !== null) {
				$simpleRules = [];
				$nestedKeys = [];
				foreach ($validation as $field => $rule) {
					if (!is_array($rule)) {
						continue;
					}
					if (strpos($field, '[]') !== false) {
						$nestedKeys[] = $field;
					} else {
						$simpleRules[$field] = $rule;
					}
				}

				if (!empty($simpleRules)) {
					$out[] = '**Тело запроса** (JSON, ключ `data`):';
					$out[] = '';
					$out[] = '| Поле | Тип | Обязательный |';
					$out[] = '|---|---|---|';
					foreach ($simpleRules as $field => $rule) {
						$label = $field === 'ARRAY' ? 'data[] (массив id)' : $field;
						$out[] = '| `' . $label . '` | `' . ($rule['type'] ?? 'string') . '` | '
							. (!empty($rule['required']) ? 'да' : 'нет') . ' |';
					}
				} elseif (empty($nestedKeys)) {
					$out[] = '**Тело запроса** (JSON): массив в ключе `data`.';
				}
				if (!empty($nestedKeys)) {
					if (!empty($simpleRules)) {
						$out[] = '';
					}
					$out[] = 'Вложенные поля тела: '
						. implode(', ', array_map(fn($k) => '`' . $k . '`', $nestedKeys)) . '.';
				}
				$out[] = '';
				$out[] = '**Пример запроса:**';
				$out[] = '';
				$out[] = '```json';
				$out[] = $this->jsonPretty($body['example']);
				$out[] = '```';
				$out[] = '';
			}
		}

		return $out;
	}

	/**
	 * Подпись авторизации для MD-таблиц.
	 */
	private function authLabel(array $cfg): string
	{
		if (isset($cfg['role_required'])) {
			return '`Bearer`, права: ' . implode(', ', $cfg['role_required']);
		}
		if (!empty($cfg['auth_required'])) {
			return '`Bearer`';
		}
		if (!empty($cfg['auth_optional'])) {
			return '`Bearer`, опционально';
		}
		return 'не требуется';
	}

	/**
	 * Экранировать `|` в ячейках Markdown-таблиц.
	 */
	private function mdEscape(string $text): string
	{
		return str_replace('|', '\\|', $text);
	}

	/**
	 * JSON-пример с отступами.
	 */
	private function jsonPretty($data): string
	{
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		return $json === false ? '{}' : $json;
	}

	// -------------------------------------------------------------------------
	// Файлы и окружение
	// -------------------------------------------------------------------------

	/**
	 * Папка .docs/restapi (создаётся при необходимости).
	 */
	private function docsDir(): string
	{
		$root = rtrim((string) (Connect::$projectDev['root'] ?? ''), '/\\');
		if ($root === '') {
			$root = dirname(__DIR__, 4);
		}
		$dir = $root . '/.docs/restapi';
		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new \RuntimeException('Не удалось создать папку: ' . $dir);
		}
		return $dir;
	}

	/**
	 * Записать файл на диск.
	 */
	private function writeFile(string $path, string $content): string
	{
		if (file_put_contents($path, $content) === false) {
			throw new \RuntimeException('Не удалось записать файл: ' . $path);
		}
		return $path;
	}
}
