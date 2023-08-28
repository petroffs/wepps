<div class="page header">
	<div class="page2">
		<div class="elements pps_flex pps_flex_row">
			<div class="logo">
				<a href="/"><img src="/ext/Template/files/logo.jpg" /></a>
			</div>
			<div class="navico pps_hide pps_show_view_small">
				<i class="fa fa-navicon"></i>
			</div>
		</div>
		<div class="elements pps_flex pps_flex_row">
			<div class="nav pps_hide_view_small">
				<ul class="nav pps_list pps_flex pps_flex_row pps_flex_center">
					{foreach name="out" key="key" item="item" from=$nav.groups.2}
					<li class="pps_flex_11_view_small{if $way.1.Id==$item.Id} active{/if}{if $nav.subs[$item.Id]} hasChilds{/if}"><a
						href="{$item.UrlMenu|default:$item.Url}">{$item.NameMenu|default:$item.Name}</a>
						{if $nav.subs[$item.Id]}
						<ul class="pps_hide">
							{foreach name="o" item="i" key="k" from=$nav.subs[$item.Id]}
							<li class="{if $dirWay[2].Id==$i.Id}active{/if}"><a
								href="{$i.UrlMenu|default:$i.Url}">{$i.NameMenu|default:$i.Name}</a></li>
							{/foreach}
						</ul> {/if}</li> {/foreach}
				</ul>
			</div>
		</div>
	</div>
</div>


