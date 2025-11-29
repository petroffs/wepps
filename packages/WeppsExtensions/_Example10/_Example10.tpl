<section class="_Example10-wrapper w_flex w_flex_row w_flex_start w_flex_row_str w_flex_margin_large w_animate">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="w_flex_13 w_flex_12_view_medium">
		{$item.Id|wepps:"News"}
		<div class="_Example10-img">
			{if $images.0}
			<img src="/pic/medium{$images.0}" class="w_image"/>
			{else}
			<img src="/ext/Template/files/noimage640.png" class="w_image"/>
			{/if}
		</div>
		<div class="_Example10-text">
			<div class="title">{$item.Name}</div>
			{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
		</div>
	</section>
	{/foreach}
</section>
{$paginatorTpl}