{if $paginator.pages}
<div class="paginator pps_flex pps_flex_row pps_flex_start pps_flex_margin pps_padding">
	{if $paginator.prev}
	<div class="item next pps_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}&filter={$smarty.get.filter}&{/if}page={$paginator.prev}" data-page="{$paginator.prev}">&lt;</a>
	</div>
	{/if} {if $paginator.current>10}
	<div class="item pps_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}&filter={$smarty.get.filter}&{/if}page=1" data-page="1">1</a>
	</div>
	<div class="item next pps_flex_fix"><span>...</span></div>
	{/if} {foreach name="out" item="item" from=$paginator.pages}{if $paginator.current+5>$item && $paginator.current-5<$item}
	<div class="item pps_flex_fix{if $paginator.current==$item} active{/if}">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}&filter={$smarty.get.filter}&{/if}page={$item}" data-page="{$item}">{$item}</a>
	</div>
	{/if} {/foreach} {if $paginator.pages|@count-5>=$paginator.current}
	<div class="item next pps_flex_fix"><span>...</span></div>
	<div class="item pps_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}&filter={$smarty.get.filter}&{/if}page={$paginator.pages|@count}"
			data-page="{$paginator.pages|@count}">{$paginator.pages|@count}</a>
	</div>
	{/if} {if $paginator.next}
	<div class="item next pps_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}&filter={$smarty.get.filter}&{/if}page={$paginator.next}" data-page="{$paginator.next}">&gt;</a>
	</div>
	{/if}
</div>
{/if}