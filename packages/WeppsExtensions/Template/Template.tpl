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
		{$content.Url|pps:"navigator"}
		{$extensionTop}
		{if $normalView==1}
		<div class="page pps_flex_max pps_flex pps_flex_col">
			<section>
				<h1>{$content.Name}</h1>
				{if $content.Text1}
				<div class="text">{$content.Text1}</div>
				{/if}
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
		<div class="page footer pps_flex pps_flex_col pps_flex_center">
			<section>
				<div class="footer-wrapper pps_overflow_auto">
					<div class="pps_flex pps_flex_row pps_flex_end pps_flex_margin pps_padding">
						<div class="footer-logo pps_flex_fix pps_flex_12_view_small">
							<a href="/"><img src="/ext/Template/files/wepps-logo.svg" alt="logo"/></a>
						</div>
						<div class="text pps_flex_fix pps_flex_12_view_small">
							<ul class="footer-company pps_list">
								<li>©  {$org.Name}</li>
								<li>{mailto address=$org.Email}</li>
								<li>{$org.Phone}</li>
							</ul>
						</div>
						<div class="text pps_flex_fix pps_flex_11_view_small">
							<ul class="footer-socials pps_list pps_flex pps_flex_row pps_flex_center pps_flex_margin">
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