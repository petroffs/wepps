<section class="childs-wrapper w_animate w_grid w_4col w_2col_view_medium w_1col_view_small w_gap_large">
	{foreach name="out" item="item" from=$elements}
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section>
		<a href="{$language.link}{$item.UrlMenu|default:$item.Url}">
			<div class="childs-img">
				{if $images.0}
				<img src="/pic/catdir{$images.0}" class="w_image"/>
				{else}
				<img src="/ext/Template/files/noimage.png" class="w_image"/>
				{/if}
			</div>
			<div class="childs-title w_flex_23 w_flex_12_view_medium w_flex_11_view_small">
				{$item.Name}
			</div>
		</a>
	</section>
	{/foreach}
</section>
<div class="w_interval_medium"></div>