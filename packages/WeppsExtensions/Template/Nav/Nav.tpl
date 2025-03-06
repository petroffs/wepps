<div class="page header pps_animate">
	<section>
		<section class="header-wrapper header-wrapper-top">
			<a href="" id="header-nav"><i class="bi bi-list"></i></a>
			<div class="header-logo">
				<a href="/"><img src="/ext/Template/files/wepps-logo-paddings.svg" /></a>
			</div>
		</section>
		<section class="header-wrapper">
			<nav class="header-nav-wrapper w_hide_view_small">
				<ul class="header-nav pps_list">
					{foreach name="out" key="key" item="item" from=$nav.groups.2}
					<li class="{if $way.1.Id==$item.Id} active{/if}{if $nav.subs[$item.Id]} has-childs{/if}">
						<a href="{$item.UrlMenu|default:$item.Url}">{$item.NameMenu|default:$item.Name}</a>
						{if $nav.subs[$item.Id]}
						<ul class="w_hide">
							{foreach name="o" item="i" key="k" from=$nav.subs[$item.Id]}
							<li class="{if $dirWay[2].Id==$i.Id}active{/if}">
								<a href="{$i.UrlMenu|default:$i.Url}">{$i.NameMenu|default:$i.Name}</a></li>
							{/foreach}
						</ul>
						{/if}
					</li>
					{/foreach}
				</ul>
			</nav>
		</section>
	</section>
</div>