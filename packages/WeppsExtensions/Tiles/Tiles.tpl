<div class="elements Tiles">
	<div class="items">
		{foreach name="out" item="item" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div
			class="item pps_flex pps_flex_row pps_flex_row_str">
			<div
				class="img{if $smarty.foreach.out.iteration%2==0} img2{/if} pps_flex_12 pps_flex_11_view_small">
				<img data-uk-scrollspy="{ cls:'uk-animation-fade', delay:500 , repeat: false }"
					src="/pic/catbig{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
					class="pps_image" />
			</div>
			<div
				class="descr pps_flex_12 pps_flex_11_view_small pps_flex pps_flex_row">
				<div class="pps_flex_11 pps_center pps_padding">
				<div class="title2">{$item.Name}</div>
				<div class="descr2">{$item.Descr}</div>
				</div>
			</div>

		</div>
		{/foreach}
	</div>
</div>