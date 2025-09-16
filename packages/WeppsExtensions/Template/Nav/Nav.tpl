<div class="page header">
	<section>
		<section class="header-wrapper header-wrapper-top">
			<a href="" id="header-nav"><i class="bi bi-list"></i></a>
			<div class="header-logo">
				<a href="/"><img src="/ext/Template/files/wepps.svg" /></a>
			</div>
			<form action="javascript:void(0)">
				<label class="pps pps_input">
					<input type="text" id="search-input" placeholder="Поиск..." name="text" value="{$smarty.get.text|default:''|escape:'html'}" autocomplete="off"/>
				</label>
			</form>
			{if $user.Id}
				<a href="/profile/" id="header-profile" data-auth="1"><i class="bi bi-person"></i><span>Привет, {$user.NameFirst}</span></a>
			{else}
				<a href="/profile/" id="header-profile" data-auth="0"><i class="bi bi-person"></i><span>Войти</span></a>
			{/if}
			<a href="/cart/" id="header-cart" data-metrics="{$cartMetrics.count}"><i class="bi bi-cart2"></i><span>Корзина</span></a>
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
<div class="page footer-nav">
	<section>
		<a href="/"><i class="bi bi-house-door"></i></a>
		<a href="" id="footer-nav"><i class="bi bi-list"></i></a>
		<a href="/profile/fav.html"><i class="bi bi-heart"></i></a>
		<a href="/cart/" id="footer-cart" data-metrics="{$cartMetrics.count}"><i class="bi bi-cart2"></i></a>
		<a href="/profile/"><i class="bi bi-person"></i></a>
	</section>
</div>