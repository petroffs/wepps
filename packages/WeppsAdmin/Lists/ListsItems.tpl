<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_row_str pps_animate">
	<div class="pps_flex_11 way">
		<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
			<li><a href="/_pps/">Главная</a></li>
			<li><a href="/_pps/lists/">Списки данных</a></li>
			<li><a href="/_pps/lists/{$listSettings.TableName}/">{$listSettings.Name}</a></li>
		</ul>
	</div>
	{$listsNavTpl}
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col pps_padding centercontent">
		<div class="pps_border pps_flex_max pps_padding">
			<h2>{$content.Name}</h2>
			<div class="controls-area pps_flex pps_flex_row pps_flex_margin pps_border">
				<div class="pps_flex_12 pps_flex_11_view_small pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large">
					<a href="/_pps/lists/{$listSettings.TableName}/add/" title="Добавить"><i class="fa fa-2x fa-plus"></i></a>
					{if $permConfig==1}
					<a href="/_pps/lists/s_Config/{$listSettings.Id}/" title="Конфигурация"><i class="fa fa-2x fa-gear"></i></a>
					<a href="/_pps/lists/s_ConfigFields/?field=TableName&filter={$listSettings.TableName}" title="Настройки полей"><i class="fa fa-2x fa-gears"></i></a>
					<a title="Экспорт данных" href="#" id="export"  data-list="{$listSettings.TableName}"><i class="fa fa-2x fa-download"></i></a>
					
					{/if}
				</div>
				<div class="pps_flex_12 pps_flex_11_view_small pps_right">
					<form class="pps_flex pps_flex_row pps_flex_margin_small"
						action="javascript:function ret() { return false }">
						<label class="pps pps_select pps_flex_13"> <select>
								{foreach name="o" item="i" key="k" from=$listScheme} {if
								$i.0.Type!='file' && $i.0.Type!='flag' &&
								!$i.0.Type|strstr:"select"}
								<option value="{$k}"{if $smarty.get.field==$k} selected="selected"{/if}>{$i.0.Name}</option> {/if} {/foreach}
						</select>
						</label> <label class="pps pps_input pps_flex_23"> <input
							type="text" class="search" value="{$smarty.get.search}" placeholder="поиск" data-list="{$listSettings.TableName}" data-orderby="{$smarty.get.orderby}"/>
						</label>
					</form>
				</div>
				
			</div>
			{if $paginatorTpl}
			<div class="pps_interval_small"></div>
				<div class="controls-area pps_flex pps_flex_row pps_flex_margin pps_border">
				<div class="pps_flex_11">{$paginatorTpl}</div>
			</div>
			{/if}
			<div class="pps_interval_small"></div>
			<div class="lists-items-list draggable">

				<table class="">
					<thead>
						<tr class="titles">
							{foreach name="o" item="i" key="k" from=$listScheme}
							<th class="{$i.0.Type}" valign="top" align="left"><div>
									{$i.0.Name}
								</div></th> {/foreach}
						</tr>
						<tr class="filters">
							{foreach name="o" item="i" key="k" from=$listScheme}
							<th class="{$i.0.Type}" valign="top"><div class="pps_nowrap">
									<a href="{$paginatorUrl}?orderby={if $smarty.get.orderby==$k}{$k}+desc{else}{$k}{/if}{if $smarty.get.field}&field={$smarty.get.field}{/if}{if $smarty.get.filter}&filter={$smarty.get.filter}{elseif $smarty.get.search}&search={$smarty.get.search}{/if}" class="sort{if $orderField==$k} active{/if}"><i class="fa {if $smarty.get.orderby==$k|cat:' desc'}fa-sort-amount-desc{else}fa-sort-amount-asc{/if}"></i></a>
									{if $i.0.Type != 'file' && $i.0.Type != 'area'} <a
										href="" class="filter{if $smarty.get.field==$k} active{/if}" data-list="{$i.0.TableName}"
										data-field="{$k}" data-orderby="{$smarty.get.orderby}">
										<i class="fa fa-filter"></i></a> {/if}
								</div></th> {/foreach}
						</tr>
					</thead>
					<tbody>
						{foreach name="out" item="item" key="key" from=$listItems}
						<tr class="data{if $smarty.foreach.out.last} data-last{/if}{if $item.DisplayOff==1} hidden{/if}"
							data-url="/_pps/lists/{$listSettings.TableName}/{$item.Id}/"
							data-id="{$item.Id}">
							{foreach name="o" item="i" key="k" from=$listScheme}
							{if $i.0.Type|@strstr:"select"}
							<td class="{$i.0.Type}">
							{foreach name="o2" item="i2" from=$item[$k|cat:'_Name']|strarr}
							<div>{$i2}</div>
							{/foreach}
							</td>
							{elseif $i.0.Type=='file'}
							<td class="{$i.0.Type}">
							{foreach name="o2" key="k2" item="i2" from=$item[$k|cat:"_FileUrl"]|strarr}
							{if $i2|stristr:"jpg" || $i2|stristr:"png"}
							<div class="{if $k2>=3}pps_hide{/if}">
							<a href="/f{$i2}"><img src="/pic/lists{$i2}"/></a>
							</div>
							{elseif $i2!=''}
							<div>
							<a href="/f{$i2}">Открыть</a>
							</div>
							{/if}
							
							{/foreach}
							</td>
							{else}
							<td class="{$i.0.Type}"><div>{$item.$k|strip_tags|nl2br|truncate:50}</div></td>
							{/if}
							{/foreach}
						</tr>
						{/foreach}
					</tbody>
				</table>

			</div>
			{if $paginatorTpl}
			<div class="pps_interval_small"></div>
			<div class="controls-area pps_flex pps_flex_row pps_border">
				<div class="pps_flex_11_view_small">{$paginatorTpl}</div>
			</div>
			{/if}
		</div>
	</div>
</div>