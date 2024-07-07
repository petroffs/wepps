{if $paginator.pages}
<div class="paginator">
	{if $paginator.prev}
	<div class="item next">
		<a href="{$paginatorUrl}?page={$paginator.prev}" data-page="{$paginator.prev}">&lt;</a>
	</div>
	{/if}
	{if $paginator.current>10}
	<div class="item">
		<a href="{$paginatorUrl}?page=1" data-page="1">1</a>
	</div>
	<div class="item next">...</div>
	{/if}
	{foreach name="out" item="item" from=$paginator.pages} {if
	$paginator.current+5>$item && $paginator.current-5<$item}
	<div class="item{if $paginator.current==$item} active{/if}">
		<a href="{$paginatorUrl}?page={$item}" data-page="{$item}">{$item}</a>
	</div>
	{/if}
	{/foreach}
	{if $paginator.pages|@count-5>=$paginator.current}
	<div class="item next">...</div>
	<div class="item">
		<a href="{$paginatorUrl}?page={$paginator.pages|@count}"
			data-page="{$paginator.pages|@count}">{$paginator.pages|@count}</a>
	</div>
	{/if} {if $paginator.next}
	<div class="item next">
		<a href="{$paginatorUrl}?page={$paginator.next}" data-page="{$paginator.next}">&gt;</a>
	</div>
	{/if}
</div>
{/if}