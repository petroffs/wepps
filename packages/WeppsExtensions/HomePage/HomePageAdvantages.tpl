<div class="page advantages">
	<div class="page2 pps_overflow_auto">
		<div class="items pps_flex pps_inline_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
			{foreach name="out" key="key" item="item" from=$services}
			<div class="item pps_flex_12">
				<div class="img">
					<img src="/pic/catprev/{$item.Images_FileUrl}" class="pps_image"/>
				</div>
				<div>{$item.Name}</div>
			</div>
			{/foreach}
		</div>
	</div>
</div>

