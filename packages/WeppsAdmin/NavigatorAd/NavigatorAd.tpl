<div class="way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/navigator/">Навигатор</a></li>
		{foreach name="out" item="item" key="key" from=$way}
		<li><a href="/_wepps/navigator{$item.Url}">{$item.Name}</a></li>
		{/foreach}
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str w_flex_margin">
	<div class="sidebar w_flex_15 w_flex_11_view_medium w_flex w_flex_col w_hide w_flex_view_medium">
		<ul class="w_list w_flex_max">
			<li>
				<div class="title">
					<a href="" id="sidebar-show"><i class="bi bi-list"></i></a>
				</div>
			</li>
		</ul>
	</div>
	<div class="sidebar w_flex_15 w_flex_11_view_medium w_flex w_flex_col w_hide_view_medium">
		<ul class="w_list w_flex_max">
			<li>
				<div class="title title-search">
					<label class="w_label w_input list-search">
						<input type="text" placeholder="Поиск раздела" id="navigator-search"/>
					</label>
				</div>
			</li>
			{foreach name="out" item="item" key="key" from=$nav.groups}
			<li>
				<div class="title">{$item.0.NGroup_Name}</div>
				{function menu level=0}
				  <ul class="w_list dir level{$level}" data-parent="{$entry.element.Id}" data-level="{$level}">
				  {foreach $data as $entry}
				    {if count($entry.child)>0}
				      <li class="{if $entry.element.IsHidden==1}hidden {/if}{if $content.Id==$entry.element.Id}active{/if}" title="Id: {$entry.element.Id}" data-id="{$entry.element.Id}"><a href="/_wepps/navigator{$entry.element.Url}">{$entry.element.Name}</a>&nbsp;<i class="bi bi-folder"></i></li>
				      {menu data=$entry.child level=$level+1}
				    {else}
				      <li class="{if $entry.element.IsHidden==1}hidden {/if}{if $content.Id==$entry.element.Id}active{/if}" title="Id: {$entry.element.Id}"><a href="/_wepps/navigator{$entry.element.Url}">{$entry.element.Name}</a></li>
				    {/if}
				  {/foreach}
				  </ul>
				{/function}
				{menu data=$navtree[$key]}				
				<div class="w_interval_small"></div>
			</li>
			{/foreach}
		</ul>
	</div>
	<div class="w_flex_45 w_flex_11_view_medium w_flex w_flex_col centercontent">
		{$listItemFormTpl}
	</div>
</div>
