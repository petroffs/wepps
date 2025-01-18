<div class="pps_border pps_padding">
	<div class="statuses pps_flex pps_flex_row pps_flex_start pps_flex_margin_large1">
		{foreach name="out" item="item" from=$statuses}
		<div class="item pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small{if $item.Id==$statusesActive} active{/if}">
			<a href="/_pps/extensions/Orders/orders.html?status={$item.Id}">{$item.Name}</a> ({$item.Co})
		</div>
		{/foreach}
	</div>
	<div class="orders">
		{foreach name="out" item="item" from=$orders}
		<div class="item pps_flex pps_flex_row pps_flex_start pps_flex_margin_large1" data-id="{$item.Id}">
			<div class="itm id pps_flex_16">{$item.Id}</div>
			<div class="itm title pps_flex_13">{$item.Name}</div>
			<div class="itm date pps_flex_14">{$item.ODate|date_format:"%d.%m.%Y"}</div>
			<div class="itm price pps_flex_14 pps_right"><span>{$item.OSum|money:2}</span></div>
			<div class="order-wrapper pps_hide pps_flex_11" id="view{$item.Id}"></div>
		</div>
		{/foreach}
	</div>
</div>