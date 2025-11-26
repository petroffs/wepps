<?php
namespace WeppsCore;
/**
 * Класс NavigatorData расширяет функциональность класса Data для работы с навигацией по сайту.
 */
class NavigatorData extends Data
{
	public $backOffice = 0;
	private $way = [];
	private $nav = [];
	private $navLevel = 0;
	private $rchild = [];

	/**
	 * Получение навигации по сайту.
	 *
	 * @param integer $navLevel Уровень вложенности для групп навигации.
	 * @return array Массив с группами и подразделами навигации.
	 */
	public function getNav($navLevel)
	{
		$condition = ($this->backOffice == 1) ? "t.IsHidden in (0,1)" : "t.IsHidden = 0";
		if ($this->navLevel == 0) {
			$this->nav[$this->navLevel] = $this->fetch("{$condition} and t.ParentDir in (1,0) and t.NGroup!=0 and t.TableId=0", 100, 1, "t.NGroup,t.Priority");
			$this->navLevel++;
			return $this->getNav($navLevel);
		} elseif ($navLevel <= $this->navLevel) {
			$arr = [];
			foreach ($this->nav[0] as $value) {
				$arr[$value['NGroup']][] = $value;
			}
			unset($this->nav[0]);
			$sub = [];
			foreach ($this->nav as $value) {
				foreach ($value as $v) {
					if (isset($v['ParentDir']))
						$sub[$v['ParentDir']][] = $v;
				}
			}
			return ['groups' => $arr, 'subs' => $sub];
		} else {
			$res = $this->nav[$this->navLevel - 1];
			$res2 = Utils::array($res);
			unset($res2[1]);
			$res2Keys = implode(",", array_keys($res2));
			if ($res2Keys == "") {
				$this->navLevel++;
				return $this->getNav($navLevel);
			}
			$res = $this->fetch("{$condition} and t.ParentDir in ({$res2Keys}) and t.TableId=0", 100, 1, "t.Priority");
			$this->nav[$this->navLevel] = $res;
			$this->navLevel++;
			return $this->getNav($navLevel);
		}
	}

	/**
	 * Путь до раздела (Хлебные крошки)
	 *
	 * @param integer $id Идентификатор раздела.
	 * @return array Массив с путём до раздела.
	 */
	public function getWay($id)
	{
		$res = $this->fetch($id);
		array_push($this->way, $res[0]);
		if ($res[0]['ParentDir'] == 0)
			return array_reverse($this->way);
		return $this->getWay($res[0]['ParentDir']);
	}

	/**
	 * Получение подраздела
	 *
	 * @param integer $id Идентификатор родительского раздела.
	 * @return array Массив с подразделами.
	 */
	public function getChild($id)
	{
		$condition = ($this->backOffice == 1) ? "" : "and t.IsHidden = 0";
		$this->setConcat("if (t.NameMenu!='',t.NameMenu,t.Name) as NameMenu");
		$this->setParams([]);
		$res = $this->fetch("t.ParentDir='{$id}' $condition");
		return $res;
	}

	/**
	 * Получение подраздела в рекурсии
	 * @param integer $id Идентификатор родительского раздела.
	 * @return array Массив с подразделами в рекурсии.
	 */
	public function getRChild($id)
	{
		$res = $this->fetchmini("ParentDir='{$id}' and IsHidden=0");
		if (isset($res[0]['Id'])) {
			foreach ($res as $value) {
				$this->rchild[] = $value['Id'];
				$this->getRChild($value['Id']);
			}
		}
		return $this->rchild;
	}

	/**
	 * Получение дерева подразделов
	 *
	 * @param array $res Массив с подразделами.
	 * @param integer $parent Идентификатор родительского раздела.
	 * @return array Массив с деревом подразделов.
	 */
	public function getChildTree($res = array(), $parent = 1)
	{
		if ($parent == 1) {
			$sql = "select if(ParentDir=0,1,ParentDir) as ParentDir,Id,Name,NameMenu,Url,NGroup,IsHidden 
                    from s_Navigator
                    order by ParentDir,Priority";
			$res = Connect::$instance->fetch($sql, array(), "group");
		}
		$tree = [];
		if (isset($res[$parent])) {
			foreach ($res[$parent] as $value) {
				if ($value['Id'] != $parent) {
					$node = ['element' => $value, 'child' => $this->getChildTree($res, $value['Id'])];
				} else {
					$node = ['element' => $value, 'child' => array()];
				}
				if ($parent == 1) {
					$tree[$value['NGroup']][$value['Id']] = $node;
				} else {
					$tree[$value['Id']] = $node;
				}
			}
		}
		return $tree;
	}
}