<section class="legal-wrapper w_animate">
	{foreach name="out" item="item" from=$elements}
	<section>
		{$item.Id|wepps:"Legal"}
		<a href="{$item.Url}">
			<div class="legal-text">
				<div class="title">{$item.Name}</div>
			</div>
		</a>
	</section>
	{/foreach}
</section>
{if $paginatorTpl}
	<div class="w_interval_medium"></div>
	{$paginatorTpl}
{/if}
