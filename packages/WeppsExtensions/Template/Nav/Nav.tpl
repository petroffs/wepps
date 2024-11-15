<div class="page header">
	<section>
		<section class="header-wrapper pps_flex pps_flex_row">
			<div class="header-logo">
				<a href="/"><img src="/ext/Template/files/wepps-logo.svg" /></a>
			</div>
			<div class="header-nav-icon pps_hide pps_flex_view_small">
				<a href="" id="header-nav"><i class="fa fa-2x fa-navicon"></i></a>
			</div>
		</section>
		<section class="header-wrapper pps_flex pps_flex_row">
			<div class="header-nav-wrapper pps_hide_view_small">
				<ul class="header-nav pps_list pps_flex pps_flex_row pps_flex_center">
					{foreach name="out" key="key" item="item" from=$nav.groups.2}
					<li class="pps_flex_11_view_small{if $way.1.Id==$item.Id} active{/if}{if $nav.subs[$item.Id]} has-childs{/if}">
						<a href="{$item.UrlMenu|default:$item.Url}">{$item.NameMenu|default:$item.Name}</a>
						{if $nav.subs[$item.Id]}
						<ul class="pps_hide">
							{foreach name="o" item="i" key="k" from=$nav.subs[$item.Id]}
							<li class="{if $dirWay[2].Id==$i.Id}active{/if}">
								<a href="{$i.UrlMenu|default:$i.Url}">{$i.NameMenu|default:$i.Name}</a></li>
							{/foreach}
						</ul>
						{/if}
					</li>
					{/foreach}
				</ul>
			</div>
		</section>
	</section>
</div>