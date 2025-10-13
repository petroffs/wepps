<?php
/**
 * Установщик приложения
 *
 * Этот скрипт предназначен для установки и начальной настройки приложения.
 * Он выполняет восстановление базы данных из резервной копии и позволяет
 * установить логин и пароль администратора.
 *
  */

if (php_sapi_name() !== 'cli') {
    http_response_code(401);
    exit();
}

if (!is_file(__DIR__ . '/packages/vendor/autoload.php')) {
    if (!is_file(__DIR__ . '/packages/composer.phar')) {
        echo 'Неправильный путь к установщику' . PHP_EOL;
        exit();
    }
    $dir = str_replace('\\', '/', __DIR__);
    $message = "Необходимо выполнить composer install\nВыполните следующие команды:\ncd {$dir}/packages && php composer.phar self-update && php composer.phar install && cd ../\nПосле этого заново выполните php install.php";
    echo $message;
    exit();
}

require_once 'config.php';

if (empty($projectSettings['DB']['host']) || empty($projectSettings['DB']['port']) || empty($projectSettings['DB']['user']) || empty($projectSettings['DB']['dbname'])) {
    echo "error: Секция DB некорректно заполнена\n";
    exit();
}

require_once 'configloader.php';

use WeppsCore\Connect;
use WeppsCore\Cli;

/**
 * Класс для выполнения установки приложения.
 *
 * Этот класс предоставляет методы для восстановления базы данных,
 * изменения пароля администратора и отображения финальных инструкций
 * после установки.
 */
class Install
{
    /**
     * @var Cli Интерфейс командной строки
     */
    private Cli $cli;

    /**
     * @var array Конфигурация проекта
     */
    private array $config;

    /**
     * Конструктор класса.
     *
     * Инициализирует интерфейс командной строки и загружает конфигурацию проекта.
     * Если конфигурационный файл не найден, создает его.
     */
    public function __construct()
    {
        $this->cli = new Cli();
        $this->config = Connect::$projectDB;

        if (empty($this->config['cnf'])) {
            $content = "[client]\n";
            $content .= "host=" . $this->config['host'] . "\n";
            $content .= "port=" . $this->config['port'] . "\n";
            $content .= "user=" . $this->config['user'] . "\n";
            $content .= "password=" . @$this->config['password'] . "\n";
            $this->cli->put($content, __DIR__ . '/config.conf');
            $this->config['cnf'] = __DIR__ . '/config.conf';
        }

        if (!is_file($this->config['cnf'])) {
            $this->cli->error('Неправильный путь к cnf-файлу: ' . $this->config['cnf']);
            exit();
        }
    }

    /**
     * Восстановление базы данных из резервной копии.
     *
     * Загружает SQL-дамп из файла и выполняет его в базе данных.
     *
     * @return void
     */
    public function restoreDB(): void
    {
        $filename = Connect::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/Backup/files/db.sql';

        if (!is_file($filename)) {
            $this->cli->error('Неправильный путь к файлу базы данных');
            exit();
        }

        $this->cli->success('Заполнение базы данных. Ждите.');
        $cmd = "mysql --defaults-extra-file={$this->config['cnf']} {$this->config['dbname']} < $filename";
        $this->cli->cmd($cmd, false);
        $this->cli->success('База данных заполнена.');
    }

    /**
     * Изменение пароля администратора.
     *
     * Запрашивает у пользователя новый логин и пароль администратора,
     * проверяет их корректность и обновляет в базе данных.
     *
     * @return void
     */
    public function changePassword(): void
    {
        while (true) {
            $this->cli->warning("Введите логин администратора (email): ");
            $login = trim(strtolower(fgets(STDIN)));
            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $this->cli->error("Неверный формат email");
                continue;
            }
            break;
        }

        while (true) {
            $this->cli->warning("Введите новый пароль администратора (минимум 8 символов): ");
            $password = $this->getHiddenInput();

            if (empty($password)) {
                $this->cli->error("Пароль не может быть пустым.");
                continue;
            }
            if (strlen($password) < 8) {
                $this->cli->error("Пароль должен содержать не менее 8 символов. Введено: " . strlen($password));
                continue;
            }
            $this->cli->warning("Введите пароль повторно для подтверждения: ");
            $passwordConfirm = $this->getHiddenInput();

            if (empty($passwordConfirm)) {
                $this->cli->error("Подтверждение пароля не может быть пустым.");
                continue;
            }
            if ($password !== $passwordConfirm) {
                $this->cli->error("Пароли не совпадают.");
                continue;
            }
            break;
        }

        $row = [
            'Login' => $login,
            'Password' => password_hash($password, PASSWORD_BCRYPT),
            'Email' => $login,
            'Phone' => '',
            'CreateDate' => date('Y-m-d H:i:s'),
            'JCart' => '',
            'JFav' => '',
            'JData' => ''
        ];

        $prepare = Connect::$instance->prepare($row);
        $sql = "UPDATE s_Users SET {$prepare['update']} WHERE Id = 1";
        Connect::$instance->fetch($sql, $prepare['row']);
        $this->cli->success("Логин и пароль администратора успешно обновлены");
    }

    /**
     * Получение скрытого ввода с командной строки.
     *
     * Отключает отображение вводимых символов в терминале.
     *
     * @return string Введенная строка
     */
    private function getHiddenInput(): string
    {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        return $password;
    }

    /**
     * Отображение финального сообщения после завершения установки.
     *
     * Выводит инструкции по завершению установки, включая информацию
     * о необходимости перемещения конфигурационного файла и удаления
     * установщика.
     *
     * @return void
     */
    public function displayFinalMessage(): void
    {
        $this->cli->success('Установка завершена.');

        if ($this->config['cnf'] === __DIR__ . '/config.conf') {
            $this->cli->warning("Скопируйте файл {$this->config['cnf']} за пределы корневой директории (../config.cnf)");
            $this->cli->warning('Укажите путь к этому файлу в настройках config.php (DB->cnf)');
        }
        $this->cli->warning('Удалите файл install.php.');
    }
}

/**
 * Создание экземпляра класса Install и выполнение установки.
 */
$obj = new Install();
$obj->restoreDB();
$obj->changePassword();
$obj->displayFinalMessage();