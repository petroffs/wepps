<div class="page w_panel exampletest" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.Id}
	<section class="exampletest-wrapper w_overflow_auto">
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="w_block example {$block.LayoutCSS}" id="w_block_{$panel.Id}_{$block.Id}"
					data-id="{$block.Id}">
					{$block.Id|wepps:'s_Blocks'}
					{$block.Name}
					{$block.Template}
				</div>
			{/foreach}
		</div>
	</section>
</div>