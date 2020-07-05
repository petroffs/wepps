<div class="elements Alignment1">
	<div class="items">
		{assign var=images value=$element.Images_FileUrl|strarr}
		<div
			class="item pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_animate">
			<div
				class="img pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small ">
				<img
					src="/pic/catbig{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
					class="pps_image" />
			</div>
			<div
				class="descr pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">{$element.Text2}</div>
			<div class="descr">{$element.Text1}</div>
		</div>
	</div>
</div>