{assign var="images" value=$blocks.0.Images_FileUrl|strarr}
<div class="page w_panel panel-minigallery" id="w_panel_{$panel.Id}">
	{$content.Id|wepps:'panels':$panel.OriginId}
	<section class="w_blocks_wrapper">
		<div class="panel-header">
			<h2>{$panel.Name|format}</h2>
			{$panel.Descr|format}
		</div>
		<div class="w_blocks {$panel.LayoutCSS}">
			{$blocks.0.Id|wepps:'s_Blocks'}
			{foreach name="blocks" item="block" from=$images}
				<div class="w_block block-minigallery {$blocks.0.LayoutCSS}">
					<img src="{$block}" alt=""/>
				</div>
			{/foreach}
		</div>
	</section>
</div>