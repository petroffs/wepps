<?
function autoLoader($name)
{
	$namef = $name;
	if (strstr($name, 'WeppsCore')) {
		$name = str_replace('\\', '/', $name);
		$name = substr($name, 0, strrpos($name, "/")) . '.php';
		require_once __DIR__ . '/packages/' . $name;
	} elseif (strstr($name, '123WeppsExtensions\\')) {
		//     	$name = str_replace('\\', '/', $name);
		//     	$name = str_replace('/Extension', '/', $name);
		//     	$name = substr($name, 0, strrpos($name, "PPS")) . '.php';
		//         require_once __DIR__ . '/packages/' . $name;
	} elseif (strstr($name, '123WeppsAdmin\\')) {
		//     	$name = str_replace('\\', '/', $name);
		//     	$name = str_replace('/Request', '/', $name);
		//     	$name = substr($name, 0, strrpos($name, "PPS")) . '.php';
		//         require_once __DIR__ . '/packages/' . $name;
	}  elseif (strstr($name, 'Wepps')) {
		$name = str_replace('\\', '/', $name);
		$name = substr($name, 0, strrpos($name, "Wepps")) . '.php';
		$name = __DIR__ . '/packages/' . $name;
		if (is_file($name)) {
			require_once $name;
		} else {
			echo "$namef - $name<br/><br/>";
			var_dump(debug_backtrace()[0]);
			exit();
		}
	}
    require_once __DIR__ . '/packages/vendor/autoload.php';
}
spl_autoload_register('autoLoader');
?>