<div class="page w_panel panel-accordion" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper">
		{if $panel.Name || $panel.Descr}
			<div class="panel-header">
				<h2>{$panel.Name|format}</h2>
				{$panel.Descr|format}
			</div>
		{/if}
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="w_block block-accordion {$block.LayoutCSS}" data-id="{$block.Id}">
					{$block.Id|wepps:'s_Blocks'}
					<h3>{$block.Name|format}</h3>
					<div class="text w_hide">{$block.Descr|format}</div>
				</div>
			{/foreach}
		</div>
	</section>
</div>