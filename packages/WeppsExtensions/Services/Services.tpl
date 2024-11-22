<div class="services-wrapper pps_overflow_auto">
	<section class="services-wrapper pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_large pps_padding">
		{foreach name="out" item="item" from=$elements} 
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section class="pps_flex_12">
			<a href="{$item.Url}">
				<div class="services-img">
					<img src="{if $images.0}/pic/catbig{$images.0}{else}/ext/Template/files/noimage.png{/if}" class="pps_image"/>
				</div>
				<div class="services-title">
					{$item.Name}
				</div>
			</a>
		</section>
		{/foreach}
	</section>
</div>
{$paginatorTpl}
<div class="pps_interval_medium"></div>