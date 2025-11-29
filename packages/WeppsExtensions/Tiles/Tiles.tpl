<section class="tiles-wrapper">
	{foreach name="out" item="item" from=$elements} 
	{assign var="images" value=$item.Images_FileUrl|strarr}
	<section class="w_flex w_flex_row w_flex_center w_flex_margin_large">
		<div class="img w_flex_25 w_flex_12_view_medium w_flex_11_view_small">
			<img src="/pic/medium{$images.0}" class="w_image"/>
		</div>
		<div class="text w_flex_25 w_flex_12_view_medium w_flex_11_view_small">
			<div class="title">{$item.Name}</div>
			<div class="text">{$item.Descr}</div>
		</div>
	</section>
	{/foreach}
</section>
<div class="w_interval_medium"></div>