<div class="page w_panel panel-altblocks" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper">
		<div class="panel-header">
			<h2>{$panel.Name|format}</h2>
			{$panel.Descr|format}
		</div>
		<div class="w_blocks{if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="w_block block-altblocks {$block.LayoutCSS} w_grid w_2col w_1col_view_small w_ai_center w_gap_large"
					data-id="{$block.Id}">
					<div class="text">
						{$block.Id|wepps:'s_Blocks'}
						<h3>{$block.Name}</h3>
						{$block.Descr}
					</div>
					<div class="img w_center">
						{if $block.Icon}
							<i class="bi {$block.Icon}"></i>
						{/if}
						{if $block.BField1}
							<div class="altblocks-video-wrap">
								<video controls preload="metadata">
									<source src="{$block.BField1}" type="video/mp4">
								</video>
							</div>
						{/if}
					</div>
				</div>
			{/foreach}
		</div>
	</section>
</div>