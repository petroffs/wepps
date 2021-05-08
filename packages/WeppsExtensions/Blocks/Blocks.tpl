{foreach name="panels" item="panel" from=$panels}
<div class="pps_panel" id="pps_panel_{$panel.Id}">
	{$panel.Id|pps:"s_Panels"}
	<div class="wrapper">
		<div class="pps_blocks{if $user.ShowAdmin} pps_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks[$panel.Id]}
			<div class="pps_block {$block.LayoutCSS}" id="pps_block_{$panel.Id}_{$block.Id}">
				{$block.Id|pps:"s_Blocks"}
				{$block.Name}
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</div>
</div>
{/foreach}