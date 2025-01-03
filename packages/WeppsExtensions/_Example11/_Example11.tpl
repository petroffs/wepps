<section class="_Example11-wrapper pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large pps_animate">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="pps_flex_13 pps_flex_12_view_medium">
		{$item.Id|pps:"_Example11"}
		<a href="{$item.Url}">
			<div class="_Example11-img">
				{if $images.0}
				<img src="/pic/catbig{$images.0}"/>
				{else}
				<img src="/ext/Template/files/noimage640.png"/>
				{/if}
			</div>
			<div class="_Example11-text">
				<div class="title">{$item.Name}</div>
				{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
			</div>
		</a>
	</section>
	{/foreach}
</section>
{$paginatorTpl}
