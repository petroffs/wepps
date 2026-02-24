<div class="page w_panel panel-tabs" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper">
		<div class="panel-header">
			<h2>{$panel.Name|format}</h2>
			{$panel.Descr|format}
		</div>
		<div class="w_blocks {$panel.LayoutCSS}">
			<ul class="block-tabs-list{if $user.ShowAdmin} w_sortable{/if}">
			{foreach name="blocks" item="block" from=$blocks}
				<li class="w_block li-tabs {$block.LayoutCSS}" data-id="{$block.Id}">
					{$block.Id|wepps:'s_Blocks'}
					<a class="w_button" href="">{$block.Name}</a>
				</li>
			{/foreach}
			</ul>
			<div class="block-tabs-content">
			{foreach name="blocks" item="block" from=$blocks}
				<div class="block-tabs {$block.LayoutCSS}">
					{$block.Descr}
				</div>
			{/foreach}
			</div>
		</div>
	</section>
</div>