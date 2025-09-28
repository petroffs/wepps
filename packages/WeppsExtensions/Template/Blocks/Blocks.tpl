<div class="w_panel blockstest" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.Id}
	<div class="wrapper">
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
			<div class="w_block blockstest {$block.LayoutCSS}" id="w_block_{$panel.Id}_{$block.Id}" data-id="{$block.Id}">
				{$block.Id|wepps:'s_Blocks'}
				{$block.Name}
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</div>
</div>