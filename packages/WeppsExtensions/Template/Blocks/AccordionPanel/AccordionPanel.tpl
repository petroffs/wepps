<div class="page pps_panel panel-accordion" id="pps_panel_{$panel.Id}">
	{$content.Id|wepps:"panels":$panel.Id}
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
		<div class="pps_blocks pps_overflow_auto {if $user.ShowAdmin} pps_sortable{/if} {$panel.LayoutCSS}">
			{foreach name="blocks" item="block" from=$blocks}
			<div class="pps_block block-accordion {$block.LayoutCSS}" id="pps_block_{$panel.Id}_{$block.Id}" data-id="{$block.Id}">
				{$block.Id|wepps:"s_Blocks"}
				<div class="title">{$block.Name}</div>
				<div class="text pps_hide">{$block.Descr}</div>
				{$block.Template}
			</div>
			{/foreach}
		</div>
	</section>
</div>