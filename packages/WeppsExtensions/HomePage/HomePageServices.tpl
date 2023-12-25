
<div class="page services">
	<div class="page2">
		<div class="items pps_overflow_auto">
			<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
			{foreach name="out" key="key" item="item" from=$services}
				<div class="item pps_flex_14">
					<div class="img">
						<img src="/pic/catprev/{$item.Images_FileUrl}" class="pps_image"/>
					</div>
					<div>{$item.Name}</div>
				</div>
			{/foreach}
			</div>
		</div>
	</div>
</div>

