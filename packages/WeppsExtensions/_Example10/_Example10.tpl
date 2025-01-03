<section class="_Example10-wrapper pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large pps_animate">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="pps_flex_13 pps_flex_12_view_medium">
		{$item.Id|pps:"News"}
		<div class="_Example10-img">
			{if $images.0}
			<img src="/pic/catbig{$images.0}" class="pps_image"/>
			{else}
			<img src="/ext/Template/files/noimage640.png" class="pps_image"/>
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