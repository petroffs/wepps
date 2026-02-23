<div class="page w_panel panel-blocks" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper w_overflow_auto">
		{if $panel.Name || $panel.Descr}
			<div class="panel-header">
				{if $panel.Name}
					<h2>{$panel.Name|format}</h2>
				{/if}
				{if $panel.Descr}
					{$panel.Descr|format}
				{/if}
			</div>
		{/if}
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="w_block block-blocks {$block.LayoutCSS}" data-id="{$block.Id}">
					{$block.Id|wepps:'s_Blocks'}
					{if $block.Descr}
						{$block.Descr|format}
					{/if}
				</div>
			{/foreach}
		</div>
	</section>
</div>