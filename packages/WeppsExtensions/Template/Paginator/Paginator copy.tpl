{if $paginator.pages}
<section class="paginator-wrapper">
	<ul class="pps_list pps_flex pps_flex_row pps_flex_start pps_flex_margin_small">
		{if $paginator.prev}
		<li class="next"><a href="{$paginatorUrl}?page={$paginator.prev}" data-page="{$paginator.prev}">&lt;</a></li>
		{/if}
		{if $paginator.current>7}
		<li><a href="{$paginatorUrl}?page=1" data-page="1">1</a></li>
		<li class="next"><span>...</span></li>
		{/if}
		{foreach name="out" item="item" from=$paginator.pages}
		{if ($paginator.current+5>$item || $paginator.current+5>$paginator.count) && $paginator.current-5<$item}
		<li class="{if $paginator.current==$item}active{/if}"><a href="{$paginatorUrl}?page={$item}" data-page="{$item}">{$item}</a></li>
		{/if}
		{/foreach}
		{if $paginator.count-5>=$paginator.current}
		<li class="next"><span>...</span></li>
		<li><a href="{$paginatorUrl}?page={$paginator.count}" data-page="{$paginator.count}">{$paginator.count}</a></li>
		{/if}
		{if $paginator.next}
		<li class="next"><a href="{$paginatorUrl}?page={$paginator.next}" data-page="{$paginator.next}">&gt;</a></li>
		{/if}
	</ul>
</section>
{/if}