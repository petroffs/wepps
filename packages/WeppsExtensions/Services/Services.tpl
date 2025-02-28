<section class="services-wrapper pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_large">
	{foreach name="out" item="item" from=$elements} 
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="pps_flex_12">
		<a href="">
			<div class="services-img">
				<img src="{if $images.0}/pic/catbig{$images.0}{else}/ext/Template/files/noimage.png{/if}" class="pps_image"/>
			</div>
			<div class="services-title">
				{$item.Name}
			</div>
			<div class="services-text w_hide">
				<div class="title">{$item.Name}</div>
				<div class="img"><img src="{if $images.0}/pic/catbig{$images.0}{else}/ext/Template/files/noimage.png{/if}" class="pps_image"/></div>
				<div class="text">{$item.Descr}</div>
			</div>
		</a>
	</section>
	{/foreach}
</section>
<div class="pps_interval_medium"></div>