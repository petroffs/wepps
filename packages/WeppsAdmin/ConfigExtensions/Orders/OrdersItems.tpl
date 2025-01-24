<div class="pps_border pps_padding">
	<div class="statuses pps_flex pps_flex_row pps_flex_start pps_flex_margin_large1">
		{foreach name="out" item="item" from=$statuses}
		<div class="item pps_flex_14 pps_flex_12_view_medium{if $item.Id==$statusesActive} active{/if}">
			<a href="/_pps/extensions/Orders/orders.html?status={$item.Id}">{$item.Name}</a> ({$item.Co})
		</div>
		{/foreach}
		<div class="item item-search pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small">
			<form action="{$url}">
			<input type="hidden" name="status" value="-1">
			<label class="pps pps_input">
				<input type="text" name="search" placeholder="Поиск" value="{$smarty.get.search|escape}">
			</label>
			<div class="pps_hide">
				<input type="submit">
			</div>
			</form>
		</div>
	</div>
	<div class="orders">
		{foreach name="out" item="item" from=$orders}
		<div class="item pps_flex pps_flex_row pps_flex_start pps_flex_margin_large1" data-id="{$item.Id}">
			<div class="item-field id pps_flex_16 pps_flex_13_view_small pps_order_1_view_small">{$item.Id}</div>
			<div class="item-field title pps_flex_13 pps_flex_11_view_small pps_order_4_view_small">{$item.Name}</div>
			<div class="item-field date pps_flex_14 pps_flex_13_view_small pps_order_2_view_small">{$item.ODate|date_format:"%d.%m.%Y"}</div>
			<div class="item-field price pps_flex_14 pps_flex_13_view_small pps_order_3_view_small pps_right"><span>{$item.OSum|money:2}</span></div>
			<div class="order-wrapper pps_hide pps_flex_11 pps_order_4_view_small" id="view{$item.Id}"></div>
		</div>
		{/foreach}
	</div>
	{if $paginatorTpl}
	<div class="paginator-wrapper">
		{$paginatorTpl}
	</div>
	{/if}
</div>