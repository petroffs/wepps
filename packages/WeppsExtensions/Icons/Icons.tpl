<div class="page pageIcons">
{if $title}
<div class="h2">{$title}</div>
{/if}
<div class="page2">
<div class="elements Icons">
	<div class="items pps_flex pps_flex_row pps_flex_center">
	{foreach name="out" item="item" from=$elements}
	<div class="item pps_flex_13 pps_flex_12_view_medium pps_center pps_zoom_img">
		<div class="img pps_animate pps_flex pps_flex_row pps_flex_center">
			<img src="{$item.Images_FileUrl}" class="pps_zoom"/>
		</div>
		<div class="title">{$item.Name}</div>
	</div>
	{/foreach}
	</div>
</div>
</div>
</div>

