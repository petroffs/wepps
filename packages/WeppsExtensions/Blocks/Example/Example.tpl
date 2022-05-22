<div class="pps_panel exampletest" id="pps_panel_{$panel.Id}">
	{$content.Id|pps:"panels":$panel.Id}
	<div class="wrapper">
		<div class="pps_blocks{if $user.ShowAdmin} pps_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
			<div class="pps_block example {$block.LayoutCSS}" id="pps_block_{$panel.Id}_{$block.Id}" data-id="{$block.Id}">
				{$block.Id|pps:"s_Blocks"}
				{$block.Name}
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</div>
</div>