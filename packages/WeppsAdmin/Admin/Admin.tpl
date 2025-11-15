<!DOCTYPE html>
<html>

<head>
	<title>{$content.MetaTitle|default:$contenttop.Name|strip_tags}</title>
	<meta name="keywords" content="{$contenttop.MetaKeyword|strip_tags}" />
	<meta name="description" content="{$contenttop.MetaDescription|strip_tags}" />
	<meta name="author" content="Aleksei Petrov" />
	<meta name="viewport" content="initial-scale=1, maximum-scale=1" />
	{$headers.meta}
	{$headers.cssjs}
</head>

<body>
	<div class="page header w_flex w_flex_col">
		<div class="page2">
			<div class="nav w_flex w_flex_row">
				<div>
					<div class="w_flex w_flex_row w_flex_start">
						<div class="item">
							<a href="/">Сайт</a>
						</div>
						{foreach name="out" item="item" key="key" from=$navtop}
							<div class="item{if $contenttop.Alias==$item.Alias} active{/if}">
								<a href="/_wepps/{$item.Alias}/">{$item.Name}</a>
							</div>
						{/foreach}
					</div>
				</div>
				{if $user.Id}
					<div>
						<div class="item">
							<a href="#" id="sign-out">Выйти</a>
						</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
	<div class="page main w_flex_max w_flex w_flex_col">
		<div class="page2">
			<h1 class="">{$content.NameNavItem|default:$contenttop.Name}</h1>
			{$extension}
		</div>
	</div>
	{$horizontalBottomTpl}
	<div class="page footer w_flex w_flex_row">
		<div class="page2  w_flex w_flex_row">
			<div>© 2019–{$smarty.now|date_format:"%Y"} <a href="//wepps.dev">Wepps Project</a></div>
			<button id="theme-switcher" title="Переключить тему">
				<i class="bi bi-sun theme-icon theme-icon-light"></i>
				<i class="bi bi-moon theme-icon theme-icon-dark"></i>
				<i class="bi bi-circle-half theme-icon theme-icon-auto"></i>
			</button>
		</div>
	</div>
	<div id="dialog" title="dialog title" class="w_hide">
		<p></p>
	</div>
</body>

</html>