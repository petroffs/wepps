<section class="news-wrapper pps_animate w_grid w_3col w_2col_view_medium w_1col_view_small w_gap_large">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section>
		{$item.Id|pps:"News"}
		<a href="{$item.Url}">
			<div class="news-img">
				{if $images.0}
				<img src="/pic/catbig{$images.0}"/>
				{else}
				<img src="/ext/Template/files/noimage640.png"/>
				{/if}
			</div>
			<div class="news-text">
				<div class="title">{$item.Name}</div>
				{if $item.Announce}<div class="text">{$item.Announce}</div>{/if}
			</div>
		</a>
	</section>
	{/foreach}
</section>
{$paginatorTpl}
