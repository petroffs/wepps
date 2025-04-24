<div class="sidebar pps_flex_14 pps_flex_11_view_medium pps_flex pps_flex_col pps_hide pps_flex_view_medium">
	<ul class="pps_list pps_flex_max">
		<li>
			<div class="title">
				<a href="" id="sidebar-show"><i class="fa fa-reorder"></i></a>
			</div>
		</li>
	</ul>
</div>
<div class="sidebar pps_flex_15 pps_flex_11_view_medium pps_flex pps_flex_col pps_hide_view_medium">
	<ul class="pps_list pps_flex_max">
		{foreach name="out" item="item" from=$exts}
		<li>
			<div class="title">{$item.Name}</div>
			<ul class="pps_list dir">
				{foreach name="o" item="i" from=$item.ENavArr}
				<li class="{if $extsActive==$i.1}active{/if}"><a
					href="/_wepps/extensions/{$item.Alias}/{$i.1}.html">{$i.0}</a></li>
				{/foreach}
			</ul>
		</li>
		{/foreach}
	</ul>
</div>