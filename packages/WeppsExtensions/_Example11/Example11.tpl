<div class="elements Example">
	<div class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
		{foreach name="out" item="item" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div class="item pps_flex_13">
			<div class="img">
				<img
					src="{if $images.0}/pic/catbig{$images.0}{else}/pic/catbig/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg{/if}"
					class="pps_image" />
			</div>
			<div
				class="descr pps_padding">
				<div class="title">
					<a href="{$item.Url}">{$item.Name}</a>
				</div>
				<div class="descr2">{$item.Announce}</div>
			</div>
		</div>
		{/foreach}
	</div>
</div>

{$paginatorTpl}
