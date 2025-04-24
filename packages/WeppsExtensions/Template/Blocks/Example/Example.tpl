<div class="page pps_panel exampletest" id="pps_panel_{$panel.Id}">
	{$content.Id|wepps:"panels":$panel.Id}
	<section class="exampletest-wrapper pps_overflow_auto">
		<div class="pps_blocks{if $user.ShowAdmin} pps_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
			<div class="pps_block example {$block.LayoutCSS}" id="pps_block_{$panel.Id}_{$block.Id}" data-id="{$block.Id}">
				{$block.Id|wepps:"s_Blocks"}
				{$block.Name}
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</section>
</div>