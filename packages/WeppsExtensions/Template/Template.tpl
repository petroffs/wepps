<!DOCTYPE html>
<html>
<head>
<title>{$content.MetaTitle|default:$content.Name|strip_tags}</title>
<meta name="keywords" content="{$content.MetaKeyword|strip_tags}" />
<meta name="description" content="{$content.MetaDescription|strip_tags}" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1" />
{$headers.meta}
{$headers.cssjs}
</head>
<body>
	<header>
		{$navTpl}
	</header>
	<main>
		{$content.Url|wepps:'navigator'}
		{$extensionTop}
		{$carouselTpl}
		{if $normalView==1}
		<div class="page w_flex_max w_flex w_flex_col">
			<section>
				<div class="content-block main">
					<h1>{$content.Name}</h1>
					{if $content.Text1}
					<div class="w_interval_small"></div>
					<div class="text">{$content.Text1}</div>
					{/if}
				</div>
				{$extension}
			</section>
		</div>
		{else}
		{$extension}
		{/if}
		{$blocks}
		{$extensionFooter}
	</main>
	<footer>
		<div class="page footer w_flex w_flex_col w_flex_center">
			<section>
				<div class="footer-wrapper">
					<div class="w_flex w_flex_row w_flex_end w_flex_margin w_padding">
						<div class="w_flex_fix w_flex_12_view_small">
							<button id="theme-switcher" title="Переключить тему">
								<i class="bi bi-sun theme-icon theme-icon-light"></i>
								<i class="bi bi-moon theme-icon theme-icon-dark"></i>
								<i class="bi bi-circle-half theme-icon theme-icon-auto"></i>
							</button>
						</div>
						<div class="footer-logo w_flex_fix w_flex_12_view_small">
							<a href="/"><img src="/ext/Template/files/wepps.svg" class="w_theme_block_light" alt="logo"/></a>
							<a href="/"><img src="/ext/Template/files/wepps-white.svg" class="w_theme_block_dark" alt="logo"/></a>
						</div>
						<div class="text w_flex_fix w_flex_12_view_small">
							<ul class="footer-company w_list">
								<li>©  {$org.Name}</li>
								<li>{mailto address=$org.Email}</li>
								<li>{$org.Phone}</li>
							</ul>
						</div>
						<div class="text w_flex_fix w_flex_11_view_small">
							<ul class="footer-socials w_list w_flex w_flex_row w_flex_center w_flex_margin">
								{foreach name=out item=item from=$socials}
								<li><a class="bi bi-{$item.Alias}" href="{$item.Field1}" title="{$item.Name}"></a></li>
								{/foreach}
							</ul>
						</div>
					</div>
				</div>
			</section>
		</div>
	</footer>
</body>
</html>