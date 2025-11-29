<section class="_Example11-wrapper w_animate w_grid w_3col w_2col_view_medium w_1col_view_small w_gap_medium">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="">
		{$item.Id|wepps:"News"}
		<a href="{$item.Url}">
			<div class="_Example11-img">
				{if $images.0}
				<img src="/pic/medium{$images.0}"/>
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
{if $paginatorTpl}
	<div class="w_interval_medium"></div>
	{$paginatorTpl}
{/if}
