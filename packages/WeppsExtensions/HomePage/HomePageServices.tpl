<div class="page services">
	<div class="page2">
	<div class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
	{foreach name="out" key="key" item="item" from=$services}
		<div class="item">
			<div class="img">
				<img src="/pic/catprev/{$item.Images_FileUrl}"/>
			</div>
			{$item.Name}
		</div>
	{/foreach}
	</div>
	</div>
</div>

