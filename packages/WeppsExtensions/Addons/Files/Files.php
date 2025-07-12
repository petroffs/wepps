<?php
namespace WeppsExtensions\Addons\Files;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Validator\ValidatorWepps;

class FilesWepps
{
    public function __construct()
    {
    }
    /**
     * Вывод указанного файла в браузер на сохранение или открытие на стороне клиента
     * @param string $file
     */
    public static function output(string $filename)
    {
        $filenameFull = ConnectWepps::$projectDev['root'] . $filename;
        if (!is_file($filenameFull)) {
            http_response_code(404);
            ConnectWepps::$instance->close();
        }
        $sql = "select * from s_Files where FileUrl='$filename' limit 1";
        $res = ConnectWepps::$instance->fetch($sql);
        if (count($res) == 0)
            ExceptionWepps::error404();
        $row = $res[0];
        $filetitle = $row['Name'];

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$filetitle\"");
        header("Content-Length: " . filesize($filenameFull));
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", mktime(0, 0, 0, 1, 1, 2000)) . " GMT"); // Дата в прошлом
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($filenameFull);
        exit();
    }

    /**
     * Очистка файловой ситсемы от файлов, не указанных в s_Files
     */
    public static function clear()
    {

    }

    /**
     * Запись файлов в s_Files с физическим копировнием
     */
    public static function save($file)
    {

    }

    /**
     * Загрузка файлов из формы, расчитано что за один раз грузится 1 файл,
     * в дальнешем проработать возможность мультизагрузки (или вызывать этот
     * метод необходимое кол-во раз при таком случае.
     * 
     * @param array $myFiles Массив с загруженными файлами ($_FILES)
     * @param string $filesfield Наименование html-элемента input[type="file"]
     * @param string $myform Идентификатор формы
     * @return array
     */
    public static function upload(array $myFiles, string $filesfield, string $myform): array
    {
        if (!isset($_SESSION)) {
            @session_start();
        }

        $root = ConnectWepps::$projectDev['root'];
        $errors = [];
        /*
         * Не все изображения имеют эту метку, возможны ошибки
         * Переработать таким образом, чтобы была входная информация
         * о типе загруженного файла и в зависимости от этого делать
         * валидацию
         */
        if (!strstr($myFiles[0]['type'], "image/")) {
            $errors[$filesfield] = "Неверный тип файла";
            $outer = ValidatorWepps::setFormErrorsIndicate($errors, $myform);
            return ['error' => $errors[$filesfield], 'js' => $outer['html']];
        }
        if ((int) $myFiles[0]['size'] > 10000000) {
            #1 мегабайт = 1 000 000 байт
            $errors[$filesfield] = "Слишком большой файл";
            $outer = ValidatorWepps::setFormErrorsIndicate($errors, $myform);
            return ['error' => $errors[$filesfield], 'js' => $outer['html']];
        }
        $filepathinfo = pathinfo($myFiles[0]['name']);
        $filepathinfo['filename'] = strtolower(TextTransformsWepps::translit($filepathinfo['filename'], 2));
        $filedest = "{$root}/packages/WeppsExtensions/Addons/Forms/uploads/{$filepathinfo['filename']}-" . date("U") . ".{$filepathinfo['extension']}";
        move_uploaded_file($myFiles[0]['tmp_name'], $filedest);
        if (!isset($_SESSION['uploads'][$myform][$filesfield])) {
            $_SESSION['uploads'][$myform][$filesfield] = [];
        }
        array_push($_SESSION['uploads'][$myform][$filesfield], $filedest);
        $_SESSION['uploads'][$myform][$filesfield] = array_unique($_SESSION['uploads'][$myform][$filesfield]);
        $js = "	<script>
		$('.fileadd').remove();
		$('input[name=\"{$filesfield}\"]').parent().append($('<p class=\"pps_fileadd\">Загружен файл &laquo;{$myFiles[0]['name']}&raquo;</p>'));
		$('label.{$filesfield}').siblings('.pps_error').trigger('click');
		</script>";
        $data = ['success' => 'Files uploaded', 'js' => $js];
        return $data;
    }
}