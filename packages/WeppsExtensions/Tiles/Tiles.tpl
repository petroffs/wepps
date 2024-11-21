<section class="tiles-wrapper">
	{foreach name="out" item="item" from=$elements} 
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="pps_flex pps_flex_row pps_flex_center pps_flex_margin_large">
		<div class="img pps_flex_25 pps_flex_12_view_medium pps_flex_11_view_small">
			<img src="/pic/catbig{$images.0}" class="pps_image"/>
		</div>
		<div class="text pps_flex_25 pps_flex_12_view_medium pps_flex_11_view_small">
			<div class="title">{$item.Name}</div>
			<div class="text">{$item.Descr}</div>
		</div>
	</section>
	{/foreach}
</section>
<div class="pps_interval_medium"></div>