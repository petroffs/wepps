<section class="services-wrapper w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin_large">
	{foreach name="out" item="item" from=$elements} 
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="w_flex_12">
		<a href="">
			<div class="services-img">
				<img src="{if $images.0}/pic/catbigv{$images.0}{else}/ext/Template/files/noimage.png{/if}" class="w_image"/>
			</div>
			<div class="services-title">
				{$item.Name}
			</div>
			<div class="services-text w_hide">
				<div class="title">{$item.Name}</div>
				<div class="img"><img src="{if $images.0}/pic/catbigv{$images.0}{else}/ext/Template/files/noimage.png{/if}" class="w_image"/></div>
				<div class="text">{$item.Descr}</div>
			</div>
		</a>
	</section>
	{/foreach}
</section>
<div class="w_interval_medium"></div>