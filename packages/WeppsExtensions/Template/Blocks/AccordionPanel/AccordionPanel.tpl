<div class="page w_panel panel-accordion" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.Id}
	<section class="panel-accordion-header">
		{if $panel.Name}
		<div class="title">{$panel.Name}</div>
		{/if}
		{if $panel.Descr}
		<div class="text">{$panel.Descr}</div>
		{/if}
		{if $panel.Images_FileUrl}
		<div class="img">
			<div class="img">
				<img src="{$panel.Images_FileUrl}"/>
			</div>
		</div>
		{/if}
	</section>
	<section class="panel-accordion-wrapper">
		<div class="w_blocks w_overflow_auto {if $user.ShowAdmin} w_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
			<div class="w_block block-accordion {$block.LayoutCSS}" id="w_block_{$panel.Id}_{$block.Id}" data-id="{$block.Id}">
				{$block.Id|wepps:'s_Blocks'}
				<div class="title">{$block.Name}</div>
				<div class="text w_hide">{$block.Descr}</div>
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</section>
</div>