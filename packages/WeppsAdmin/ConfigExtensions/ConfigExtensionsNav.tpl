<div class="sidebar w_flex_14 w_flex_11_view_medium w_flex w_flex_col w_hide w_flex_view_medium">
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
		{foreach name="out" item="item" from=$exts}
		<li>
			<div class="title">{$item.Name}</div>
			<ul class="w_list dir">
				{foreach name="o" item="i" from=$item.ENavArr}
				<li class="{if $extsActive==$i.1}active{/if}"><a
					href="/_wepps/extensions/{$item.Alias}/{$i.1}.html">{$i.0}</a></li>
				{/foreach}
			</ul>
		</li>
		{/foreach}
	</ul>
</div>