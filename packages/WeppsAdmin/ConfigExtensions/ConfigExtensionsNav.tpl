<div
	class="pps_flex_14 pps_flex_11_view_medium pps_padding leftmenu pps_flex pps_flex_col pps_hide pps_show_view_medium">
	<ul
		class="pps_list pps_border pps_flex_max">
		<li>
			<div class="title">
				<a href="" id="showleftmenu"><i class="fa fa-reorder"></i></a>
			</div>
		</li>
	</ul>
</div>
<div
	class="pps_flex_15 pps_flex_11_view_medium pps_padding leftmenu pps_flex pps_flex_col pps_hide_view_medium">
	<ul
		class="pps_list pps_border pps_flex_max">
		{foreach name="out" item="item" key="key" from=$exts}
		<li>
			<div class="title">{$item.Name}</div>
			<ul class="pps_list dir">
				{foreach name="o" item="i" key="k" from=$item.ENav|explode:"\n"}
				{assign var="extlink" value=$i|strarr}
				<li class="{if $extsActive==$extlink.1}active{/if}"><a
					href="/_pps/extensions/{$item.KeyUrl}/{$extlink.1}.html">{$extlink.0}</a></li>
				{/foreach}
			</ul>
		</li> {/foreach}
	</ul>
</div>