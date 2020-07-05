<div class="elements Example">
	<div class="items">
		{foreach name="out" item="item" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div
			class="item pps_flex pps_flex_row pps_flex_row_center pps_flex_margin pps_animate">
			<div
				class="img pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small ">
				<img
					src="/pic/catbig{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
					class="pps_image" />
			</div>
			<div
				class="descr-wrapper pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">
				<div class="title">{$item.Name}</div>
				<div class="descr">{$item.Descr}</div>
			</div>

		</div>
		{/foreach}
	</div>
</div>