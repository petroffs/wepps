<div class="way">
	<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
		<li><a href="/_pps/">Главная</a></li>
		<li><a href="/_pps/navigator/">Навигатор</a></li>
		{foreach name="out" item="item" key="key" from=$way}
		<li><a href="/_pps/navigator{$item.Url}">{$item.Name}</a></li>
		{/foreach}
	</ul>
</div>
<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
	<div class="pps_flex_15 pps_flex_11_view_medium leftmenu pps_flex pps_flex_col pps_hide pps_flex_view_medium">
		<ul class="pps_list pps_border pps_flex_max">
			<li>
				<div class="title">
					<a href="" id="showleftmenu"><i class="fa fa-reorder"></i></a>
				</div>
			</li>
		</ul>
	</div>
	<div
		class="pps_flex_15 pps_flex_11_view_medium leftmenu pps_flex pps_flex_col pps_hide_view_medium">
		<ul class="pps_list pps_border pps_flex_max">
			<li>
				<div class="title">
					<label class="pps pps_input list-search">
						<input type="text" placeholder="Поиск раздела" id="navigator-search"/>
					</label>
				</div>
			</li>
			{foreach name="out" item="item" key="key" from=$nav.groups}
			<li>
				<div class="title">{$item.0.NGroup_Name}</div>
				{function menu level=0}
				  <ul class="pps_list dir level{$level}" data-parent="{$entry.element.Id}" data-level="{$level}">
				  {foreach $data as $entry}
				    {if count($entry.child)>0}
				      <li class="{if $entry.element.DisplayOff==1}hidden {/if}{if $content.Id==$entry.element.Id}active{/if}" title="Id: {$entry.element.Id}" data-id="{$entry.element.Id}"><a href="/_pps/navigator{$entry.element.Url}">{$entry.element.Name}</a>&nbsp;<i class="fa fa-folder-o"></i></li>
				      {menu data=$entry.child level=$level+1}
				    {else}
				      <li class="{if $entry.element.DisplayOff==1}hidden {/if}{if $content.Id==$entry.element.Id}active{/if}" title="Id: {$entry.element.Id}"><a href="/_pps/navigator{$entry.element.Url}">{$entry.element.Name}</a></li>
				    {/if}
				  {/foreach}
				  </ul>
				{/function}
				{menu data=$navtree[$key]}				
				<div class="pps_interval_small"></div>
			</li>
			{/foreach}
		</ul>
	</div>
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col centercontent">
		{$listItemFormTpl}
	</div>
</div>
