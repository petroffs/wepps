<?php
namespace WeppsCore;

class Cli
{
	private $display = 0;
	public function __construct()
	{
		$this->display();
	}
	public function display(bool $display = true)
	{
		$this->display = $display;
	}
	public function error(string $text = '')
	{
		return self::outer(self::color("[error] $text", 'e'));
	}
	public function success(string $text = '')
	{
		return self::outer(self::color("[success] $text", 's'));
	}
	public function warning(string $text = '')
	{
		return self::outer(self::color("[warning] $text", 'w'));
	}
	public function info(string $text = '')
	{
		return self::outer(self::color("[info] $text", 'i'));
	}
	public function text(string $text = '')
	{
		return self::outer(self::color($text));
	}
	public function br()
	{
		return self::outer("\n");
	}
	public function progress($done, $total)
	{
		$perc = floor(($done / $total) * 100);
		$left = 100 - $perc;
		$rate = 0.5;
		$perc2 = floor($perc * $rate);
		$left2 = ceil($left * $rate);
		$write = sprintf("\033[0G\033[2K[%'#{$perc2}s#%-{$left2}s] $done/$total [$perc%%]", "", "");
		echo $write;
	}
	public function copy(string $source, string $destination, bool $overwrite = true): bool
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!copy($source, $destination)) {
			return false;
		}
		return true;
	}
	public function move(string $source, string $destination, bool $overwrite = true)
	{
		if ($overwrite === false && file_exists($destination)) {
			return false;
		} elseif (!file_exists($source)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!rename($source, $destination)) {
			return false;
		}
		return true;
	}
	public function put($content, $destination)
	{
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!file_put_contents($destination, $content)) {
			return false;
		}
		return true;
	}
	public function mkdir(string $dir): bool
	{
		$dir = str_replace('\\', '/', $dir);
		if (!stristr($dir, Connect::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return true;
	}
	public function rmdir(string $dir)
	{
		$dir = str_replace('\\', '/', $dir);
		if (!is_dir($dir)) {
			$this->warning('no dir');
			return false;
		} elseif (!stristr($dir, Connect::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		exec("rm $dir -rf");
		return true;
	}
	public function rmfile(string $file)
	{
		if (!file_exists($file)) {
			return false;
		}
		unlink($file);
		return true;
	}
	public function cmd(string $cmd, bool $silent = false): array
	{
		if (empty($cmd)) {
			$this->warning("cmd is empty");
		}
		$o = [];
		$v = 100;
		exec("$cmd 2>&1", $o, $v);
		if ($v != 0) {
			if (!empty($o[0])) {
				$this->error($o[0]);
			} else {
				$this->error('cmd');
			}
			exit();
		}
		if ($silent == false && !empty($o)) {
			$this->info(implode("\n", $o));
		}
		return $o;
	}
	private function color(string $str = '', string $type = ''): string
	{
		$output = '';
		switch ($type) {
			case 'e': //error
				$output = "\033[0;31;47m$str\033[0m\n";
				break;
			case 's': //success
				$output = "\033[32m$str\033[0m\n";
				break;
			case 'w': //warning
				$output = "\033[33m$str\033[0m\n";
				break;
			case 'i': //info
				$output = "\033[36m$str\033[0m\n";
				break;
			default:
				$output = "$str\n";
				break;
		}
		return $output;
	}
	private function outer(string $text = ''): string
	{
		if ($this->display == true) {
			echo $text;
		}
		return $text;
	}
}