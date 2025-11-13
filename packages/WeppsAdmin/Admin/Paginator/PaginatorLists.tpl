{if $paginator.pages}
<div class="paginator w_flex w_flex_row w_flex_start w_flex_margin_small">
	{if $paginator.prev}
	<div class="item next w_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}{if $smarty.get.filter}&filter={$smarty.get.filter}{/if}&{if $smarty.get.search}&search={$smarty.get.search}{/if}{/if}page={$paginator.prev}" data-page="{$paginator.prev}">&lt;</a>
	</div>
	{/if} {if $paginator.current>10}
	<div class="item w_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}{if $smarty.get.filter}&filter={$smarty.get.filter}{/if}{if $smarty.get.search}&search={$smarty.get.search}{/if}&{/if}page=1" data-page="1">1</a>
	</div>
	<div class="item next w_flex_fix"><span>...</span></div>
	{/if} {foreach name="out" item="item" from=$paginator.pages}{if $paginator.current+5>$item && $paginator.current-5<$item}
	<div class="item w_flex_fix{if $paginator.current==$item} active{/if}">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}{if $smarty.get.filter}&filter={$smarty.get.filter}{/if}{if $smarty.get.search}&search={$smarty.get.search}{/if}&{/if}page={$item}" data-page="{$item}">{$item}</a>
	</div>
	{/if} {/foreach} {if $paginator.pages|@count-5>=$paginator.current}
	<div class="item next w_flex_fix"><span>...</span></div>
	<div class="item w_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}{if $smarty.get.filter}&filter={$smarty.get.filter}{/if}{if $smarty.get.search}&search={$smarty.get.search}{/if}&{/if}page={$paginator.pages|@count}"
			data-page="{$paginator.pages|@count}">{$paginator.pages|@count}</a>
	</div>
	{/if} {if $paginator.next}
	<div class="item next w_flex_fix">
		<a href="{$paginatorUrl}?{if $smarty.get.orderby}orderby={$smarty.get.orderby}&{/if}{if $smarty.get.field}field={$smarty.get.field}{if $smarty.get.filter}&filter={$smarty.get.filter}{/if}{if $smarty.get.search}&search={$smarty.get.search}{/if}&{/if}page={$paginator.next}" data-page="{$paginator.next}">&gt;</a>
	</div>
	{/if}
</div>
{/if}