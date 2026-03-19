<?php

namespace WeppsExtensions\Addons\Rest;

use WeppsCore\Data;
use WeppsCore\Connect;

/**
 * RestV1M2MUsers - M2M API для работы с пользователями (таблица s_Users)
 * 
 * Наследует общую логику CRUD операций из RestV1M2MManager
 * 
 * Доступные HTTP методы:
 * - GET  /v1/m2m/users          - список пользователей (page, limit, search)
 * - GET  /v1/m2m/users.item     - получить пользователя по ID
 * - POST /v1/m2m/users          - создать пользователя
 * - PUT  /v1/m2m/users          - обновить пользователя
 * - DELETE /v1/m2m/users        - удалить пользователя
 */
class RestV1M2MUsers extends RestV1M2MManager
{
    /**
     * Инициализация - установить имя таблицы и конфиг полей
     */
    protected function init(): void
    {
        $this->tableName = 's_Users';

        // JSON поля таблицы s_Users (если есть)
        $this->jsonFields = ['JCart', 'JFav'];

        // Загрузить маппинг полей из БД (s_ConfigFields.ApiMapping)
        $this->loadInputMappingFromDb($this->tableName);

        // Дополнить маппинг специальными правилами для s_Users, если нужно
        // (если в БД что-то забыли или нужны особые случаи)
        if (empty($this->inputFieldMapping)) {
            // Fallback на случай если в БД ничего не найдено
            $this->inputFieldMapping = [
                'id' => 'Id',
                'login' => 'Login',
                'password' => 'Password',
                'name' => 'Name',
                'phone' => 'Phone',
                'cart' => 'JCart',
                'favorites' => 'JFav',
            ];
        }

        // Типы полей для валидации и схемы
        $this->fieldConfig = [
            'Id' => ['type' => 'int', 'required' => false],
            'Login' => ['type' => 'email', 'required' => true],
            'Password' => ['type' => 'string', 'required' => true],
            'Name' => ['type' => 'string', 'required' => false],
            'Phone' => ['type' => 'phone', 'required' => false],
            'JCart' => ['type' => 'json', 'required' => false],
            'JFav' => ['type' => 'json', 'required' => false],
        ];
    }

    /**
     * Получить список пользователей - GET /v1/m2m/users
     * 
     * Query параметры:
     * - page (int2, optional) - номер страницы, по умолчанию 1
     * - limit (int2, optional) - записей на странице, по умолчанию 20, макс 100
     * - search (string, optional) - поиск по login или nameSurname
     * 
     * @return array - {status: 200, message: 'Success', data: {items, paginator, count}}
     */
    public function getUsers(): array
    {
        $this->init();

        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';

        // Построить условия поиска
        $conditions = '';
        $params = [];

        if (!empty($search)) {
            // Поиск по login (Email) или имени
            $conditions = "t.Login LIKE ? OR t.nameSurname LIKE ?";
            $params = ['%' . $search . '%', '%' . $search . '%'];
        }

        return $this->getList($conditions, $params, $page, $limit);
    }

    /**
     * Получить одного пользователя по ID - GET /v1/m2m/users.item
     * 
     * Query параметры:
     * - id (int2, required) - ID пользователя
     * 
     * @return array - {status: 200 or 404, message: '...', data: {...user...} or null}
     */
    public function getUsersItem(): array
    {
        $this->init();

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            return [
                'status' => 400,
                'message' => 'ID is required',
                'data' => null,
            ];
        }

        return $this->getItem($id);
    }

    /**
     * Создать нового пользователя - POST /v1/m2m/users
     * 
     * Body JSON:
     * {
     *   "login": "user@example.com",
     *   "password": "secure_password",
     *   "nameSurname": "Фамилия",
     *   "nameFirst": "Имя",
     *   "namePatronymic": "Отчество" (optional),
     *   "phone": "+79001234567" (optional),
     *   // ... другие доступные поля из s_ConfigFields.IsApiAvailable = 1
     * }
     * 
     * @return array - {status: 201 or 400, message: '...', data: {id} or null}
     */
    public function postUsers(): array
    {
        $this->init();
        $this->loadRequestData();

        // Получить данные из JSON body
        if (!$this->requestData) {
            return [
                'status' => 400,
                'message' => 'No data provided',
                'data' => null,
            ];
        }

        // Нормализовать входящие ключи (camelCase → PascalCase)
        $this->requestData = $this->normalizeInputKeys($this->requestData);

        // Обязательные поля
        if (empty($this->requestData['Login']) || empty($this->requestData['Password'])) {
            return [
                'status' => 400,
                'message' => 'Login and Password are required',
                'data' => null,
            ];
        }

        // Проверить уникальность login (email)
        $data = new Data('s_Users');
        $data->setParams([$this->requestData['Login']]);
        $existing = $data->fetch('t.Login = ?', 1, 1);

        if (!empty($existing)) {
            return [
                'status' => 409,
                'message' => 'User with this email already exists',
                'data' => null,
            ];
        }

        // Хэшировать пароль перед сохранением
        $userData = $this->requestData;
        $userData['Password'] = password_hash($userData['Password'], PASSWORD_BCRYPT);

        return $this->create($userData);
    }

    /**
     * Обновить пользователя - PUT /v1/m2m/users
     * 
     * Body JSON:
     * {
     *   "id": 5,
     *   "nameSurname": "Новая Фамилия",
     *   "nameFirst": "Новое Имя",
     *   "phone": "+79009999999" (optional)
     *   // ... другие доступные поля
     *   // ВНИМАНИЕ: password не обновляется через этот endpoint
     * }
     * 
     * @return array - {status: 200 or 400 or 404, message: '...', data: null}
     */
    public function putUsers(): array
    {
        $this->init();
        $this->loadRequestData();

        if (!$this->requestData) {
            return [
                'status' => 400,
                'message' => 'No data provided',
                'data' => null,
            ];
        }

        // Нормализовать входящие ключи (camelCase → PascalCase)
        $this->requestData = $this->normalizeInputKeys($this->requestData);

        $id = (int) ($this->requestData['Id'] ?? 0);

        if (!$id) {
            return [
                'status' => 400,
                'message' => 'ID is required',
                'data' => null,
            ];
        }

        // Не разрешаем обновлять пароль через M2M (это отдельный endpoint)
        unset($this->requestData['password'], $this->requestData['Password']);

        // Не разрешаем менять login (email)
        unset($this->requestData['login'], $this->requestData['Login']);

        // Удалить id из данных перед обновлением
        unset($this->requestData['id'], $this->requestData['Id']);

        return $this->update($id, $this->requestData);
    }

    /**
     * Удалить пользователя - DELETE /v1/m2m/users
     * 
     * Query параметры:
     * - id (int2, required) - ID пользователя для удаления
     * 
     * @return array - {status: 200 or 400 or 404, message: '...', data: null}
     */
    public function deleteUsers(): array
    {
        $this->init();

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            return [
                'status' => 400,
                'message' => 'ID is required',
                'data' => null,
            ];
        }

        return $this->delete($id);
    }
}
