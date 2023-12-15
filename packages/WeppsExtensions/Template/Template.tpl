<!DOCTYPE html>
<html>
<head>
<title>{$content.MetaTitle|default:$content.Name|strip_tags}</title>
<meta name="keywords" content="{$content.MetaKeyword|strip_tags}" />
<meta name="description" content="{$content.MetaDescription|strip_tags}" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1" />
{$headers.meta}
	<!--                                   programming by:
	██████████████████████████████████████████████████████
	█────█───█───█────█────█───█───█───███────█────█─███─█
	█─██─█─████─██─██─█─██─█─███─███─█████─██─█─██─█──█──█
	█────█───██─██────█─██─█───█───█───███─████─██─█─█─█─█
	█─████─████─██─█─██─██─█─███─█████─███─██─█─██─█─███─█
	█─████───██─██─█─██────█─███─███───█─█────█────█─███─█
	██████████████████████████████████████████████████████
	-->
{$headers.cssjs}
</head>
<body>
	<header>
		{$navTpl}
	</header>
	<main>
		{$content.Url|pps:"navigator"}
		{$extensionTop}
		{$blocks}
		{if $normalView==1}
		<div class="page main pps_flex_max pps_flex pps_flex_col">
			<div class="page2">
				<h1>{$content.Name}</h1>
				{if $content.Text1}
				<div class="text">{$content.Text1}</div>
				{/if}
				{$extension}
			</div>
		</div>
		{else}
		{$extension}
		{/if}
		{$extensionFooter}
	</main>
	<footer>
		<div class="page footer pps_flex pps_flex_col pps_flex_center">
			<div class="page2 pps_flex pps_flex_col pps_flex_center">
				<div class="copyrights pps_flex pps_flex_row pps_flex_center">
					<div class="item pps_flex_fix pps_padding pps_center">© {$shopInfo.Name}</div>
					<div class="item pps_flex_fix pps_padding pps_center">{*mailto
						address=$shopInfo.Email*}</div>
					{foreach name=out item=item from=$shopInfo.Phone|explode}
					<div class="item pps_flex_fix pps_padding pps_center">{$item}</div>
					{/foreach}
				</div>
				<div class="links pps_flex pps_flex_row pps_flex_center">
					{foreach name=out item=item from=$socials}
					<div class="item pps_flex_fix pps_padding">
						<a class="fa fa-stack fa-lg fa-{$item.Alias}" href="{$item.Field1}" title="{$item.Name}"></a>
					</div>
					{/foreach}
				</div>
			</div>
		</div>
	</footer>
</body>
</html>