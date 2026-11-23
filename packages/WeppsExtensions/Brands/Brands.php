<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Brands;

use WeppsCore\Connect;
use WeppsCore\Exception;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;

class Brands extends Extension
{
	#[\Override]
	public function request(): void
	{
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Brands/Brands.tpl';
				$sql = "SELECT distinct substring(pv.PValue,1,1) FirstLetter,pv.Name,pv.Alias,pv.PValue, concat('{$this->navigator->content['Url']}',pv.Alias,'.html') Url
						from s_PropertiesValues pv
						join Products p on p.Id = pv.TableNameId and p.IsHidden=0
						where pv.Name=1 and pv.IsHidden=0";
				$res = Connect::$instance->fetch($sql, [], 'group');
				$smarty->assign('brands', $res);
				break;
			default:
				$this->tpl = 'packages/WeppsExtensions/Brands/BrandsItem.tpl';
				self::getItem('');
				break;
		}
		$this->headers->css("/ext/Brands/Brands.{$this->rand}.css");
		$this->headers->js("/ext/Brands/Brands.{$this->rand}.js");
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
	#[\Override]
	public function getItem($tableName, $condition = '')
	{
		$this->extensionData['element'] = 1;
		$sql = "SELECT pv.Name,pv.Alias,pv.PValue, concat('{$this->navigator->content['Url']}',pv.Alias,'.html') Url
				from s_PropertiesValues pv
				join Products p on p.Id = pv.TableNameId and p.IsHidden=0
				where pv.Alias=? and pv.IsHidden=0 limit 1";
		$res = Connect::$instance->fetch($sql, [Navigator::$pathItem]);
		if (empty($res[0])) {
			Exception::error404();
		}
		$this->navigator->content['Name'] .= ' / ' . $res[0]['PValue'];
		// if (!empty($res['MetaTitle'])) {
		// 	$this->navigator->content['MetaTitle'] = $res['MetaTitle'];
		// } else {
		// 	$this->navigator->content['MetaTitle'] = $res['Name'];
		// }
		// if (!empty($res['MetaKeywords'])) {
		// 	$this->navigator->content['MetaKeywords'] = $res['MetaKeywords'];
		// }
		// if (!empty($res['MetaDescription'])) {
		// 	$this->navigator->content['MetaDescription'] = $res['MetaDescription'];
		// }
		// return $res;
		return [];
	}
}