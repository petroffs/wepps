<div class="w_rounded w_border w_padding">
	<div class="statuses w_flex w_flex_row w_flex_start w_flex_margin_large1">
		{foreach name="out" item="item" from=$statuses}
		<div class="item w_flex_14 w_flex_12_view_medium{if $item.Id==$statusesActive} active{/if}">
			<a href="/_wepps/extensions/Orders/orders.html?status={$item.Id}">{$item.Name}</a> ({$item.Co})
		</div>
		{/foreach}
		<div class="item item-search w_flex_14 w_flex_12_view_medium w_flex_11_view_small">
			<form action="{$url}">
			<input type="hidden" name="status" value="-1">
			<label class="w_label w_input">
				<input type="text" name="search" placeholder="Поиск" value="{$smarty.get.search|escape}">
			</label>
			<div class="w_hide">
				<input type="submit">
			</div>
			</form>
		</div>
	</div>
	<div class="orders">
		{foreach name="out" item="item" from=$orders}
		<div class="item w_flex w_flex_row w_flex_start w_flex_margin_large1" data-id="{$item.Id}">
			<div class="item-field id w_flex_16 w_flex_13_view_small w_order_1_view_small">{$item.Id}</div>
			<div class="item-field title w_flex_13 w_flex_11_view_small w_order_4_view_small">{$item.Name}</div>
			<div class="item-field date w_flex_14 w_flex_13_view_small w_order_2_view_small">{$item.ODate|date_format:"%d.%m.%Y"}</div>
			<div class="item-field price w_flex_14 w_flex_13_view_small w_order_3_view_small w_right"><span>{$item.OSum|money:2}</span></div>
			<div class="order-wrapper w_hide w_flex_11 w_order_4_view_small" id="view{$item.Id}"></div>
		</div>
		{/foreach}
	</div>
	{if $paginatorTpl}
	<div class="paginator-wrapper">
		{$paginatorTpl}
	</div>
	{/if}
</div>