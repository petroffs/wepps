<div class="page w_panel panel-hero" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper">
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="w_block block-hero {$block.LayoutCSS}" data-id="{$block.Id}">
					{$block.Id|wepps:'s_Blocks'}
					{if $block.Descr}
						{$block.Descr|format}
					{/if}
				</div>
			{/foreach}
		</div>
	</section>
</div>