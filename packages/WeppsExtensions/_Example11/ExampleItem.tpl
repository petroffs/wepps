{assign var=images value=$element.Images_FileUrl|strarr}
<div
	class="element-wrapper Example pps_flex pps_flex_row pps_flex_row_str pps_flex_start">
	<div class="element pps_flex_23 pps_flex_11_view_small">
		{if $images.0}
		<div class="img"><img src="/pic/full{$images.0}" class="pps_image"/></div>
		{/if}
		<div class="date">{$element.NDate|date_format:"%d.%m.%Y"}</div>
		<div class="descr">{$element.Descr}</div>
	</div>
	<div class="elements Example pps_flex_13 pps_flex_11_view_small">
		<div
			class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
			{foreach name="out" item="item" from=$elements} {assign var=images
			value=$item.Images_FileUrl|strarr}
			<div class="item pps_flex_11">
				<div class="img">
					<img
						src="{if $images.0}/pic/catbig{$images.0}{else}/pic/catbig/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg{/if}"
						class="pps_image" />
				</div>
				<div class="descr pps_padding">
					<div class="title">
						<a href="{$item.Url}">{$item.Name}</a>
					</div>
					<div class="descr pps_hide_view_medium">{$item.Announce}</div>
					<div class="date">{$item.NDate|date_format:"%d.%m.%Y"}</div>
				</div>
			</div>
			{/foreach}
		</div>
	</div>
</div>