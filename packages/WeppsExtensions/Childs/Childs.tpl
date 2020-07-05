<div class="elements Childs">
	<div class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_row_center pps_flex_margin_medium pps_animate">
		{foreach name="out" item="item" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div
			class="item pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small pps_flex pps_flex_col">
			<div class="item2 pps_flex_max">
				<div class="img">
					<img
						src="/pic/catdir{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
						class="pps_image" />
					<div
						class="descr-wrapper pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">
						<div class="title">
							<a href="{$language.link}{$item.UrlMenu|default:$item.Url}">{$item.Name}</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		{/foreach}
	</div>
</div>