<?
//------------------------------//
function debug($arg)
{
	echo '<pre>'.print_r($arg,true).'</pre>';
}
//------------------------------//

include ('class.php');

$login = "login"; //логин (можно и без @yandex.ru)
$password = "password"; // и пароль соответственно

$disk = new yandex_disk($login , $password);

// за один раз можно вызвать только один метод, то есть если вы все тут раз комментируете сработает только первый метод

// получить список файлов в указанной директории
if ($ls = $disk->ls('/')) {echo '<P>OK</P>'; debug($ls);} else {echo '<P><font color="red">ERROR</font></P>'; echo $disk->ansver; }

// создать папку
//if ($disk->mkdir('/dir')) echo '<P>OK</P>'; else echo '<P><font color="red">ERROR</font></P>';

// закачать файл в хранилище
//if ($disk->put('test.zip','/dir/test.zip')) echo '<P>OK</P>'; else echo '<P><font color="red">ERROR</font></P>';

// скачать файл из хранилища
//if ($data = $disk->get('/dir/test.zip')) {echo '<P>OK</P>'; file_put_contents('~test.zip',$data);} else echo '<P><font color="red">ERROR</font></P>';

// удалить файл или папку
//if ($disk->delete('/dir/test.zip')) echo '<P>OK</P>'; else echo '<P><font color="red">ERROR</font></P>';
//if ($disk->delete('/pictures')) echo '<P>OK</P>'; else echo '<P><font color="red">ERROR</font></P>';



//d($disk->info); // вернувшиеся заголовки
//echo $disk->ansver; // ответ сервера
?>