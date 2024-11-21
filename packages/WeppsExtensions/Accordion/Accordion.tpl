<div class="pps_interval_medium"></div>
<section class="accordion-wrapper pps_flex pps_flex_row">
	<div class="accordion-items pps_animate pps_flex_23 pps_flex_11_view_medium">
		{foreach name="out" item="item" key="key" from=$elements}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section>
			<h2>{$item.Name}</h2>
			<div class="text">{$item.Descr}</div>
		</section>
		{/foreach}
	</div>
</section>
<div class="pps_interval_medium"></div>