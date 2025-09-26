<div class="w_grid w_3col w_gap_medium">
	<div class="w_2scol w_3scol_view_medium">
		<div class="content-block">
			<h1>Корзина</h1>
			<label class="pps pps_checkbox"><input type="checkbox" id="cart-check-all" checked="checked"
					autocomplete="off" /><span>Выбрать все</span></label>
		</div>
		<div class="content-block cart-items">
			{foreach item="item" from=$cartSummary.items}
				<section data-id="{$item.id}-{$item.idv}">
					<div class="cart-checkbox"><label class="pps pps_checkbox"><input type="checkbox" name="cart-check"
								value="{$item.id}-{$item.idv}" {if $item.active==1}checked {/if}
								autocomplete="off" /><span></span></label></div>
					<div class="cart-image"><img src="/pic/lists{$item.image}" /></div>
					<div class="cart-title">
						<a href="{$item.url}" class="title">{$item.name}</a>
						{if $item.stocks<=0}
							<div class="warning">⚠️ Нет товара для заказа</div>
						{elseif $item.stocks<$item.quantity}
							<div class="warning">⚠️ Превышен лимит ({$item.stocks} шт.) для заказа</div>
						{/if}
						<div class="btn"><a href="" class="cart-remove"><i class="bi bi-trash3"></i> <span>Удалить</span></a></div>
						<div class="btn"><a href="" class="cart-favorite{if $item.id|in_array:$cartFavorites} active{/if}"><i class="bi bi-bookmarks"></i> <span>Избранное</span></a></div>
					</div>
					<div class="cart-quantity">
						{if $item.stocks>0}
						<div class="pps pps_minmax" data-value="{$item.quantity}" data-name="quantity">
							<button class="sub">
								<span></span>
							</button>
							<input type="text" name="quantity" value="{$item.quantity}" maxlength="3" min="1" max="{$item.stocks}"
								autocomplete="off" />
							<button class="add">
								<span></span>
							</button>
						</div>
						<div class="cart-price">
							<span class="price"><span>{$item.price|money}</span>
								за&nbsp;1&nbsp;шт.</span>
						</div>
						{/if}
					</div>
					<div class="cart-sum">
						{if $item.stocks>0}
						<div class="price"><span>{$item.sum|money}</span></div>
						{if $item.sumBefore>0}
							<div class="price price-before"><span>{$item.sumBefore|money}</span></div>
						{/if}
					{/if}
					</div>
				</section>
			{/foreach}
		</div>
	</div>
	<div class="w_3scol_view_medium">
		<div class="content-block cart-total">
			<h2>Детали заказа</h2>
			<div class="w_grid w_3col">
				<div class="w_2scol title">{$cartSummary.quantityActive} {$cartText.goodsCount}</div>
				<div class="pps_right">
					<div class="price"><span>{$cartSummary.sumBefore|money}</span></div>
				</div>
			</div>
			<div class="w_grid w_3col">
				<div class="w_2scol title">Скидка</div>
				<div>
					<div class="price"><span>{$cartSummary.sumSaving|money}</span></div>
				</div>
			</div>
			<div class="w_grid w_3col">
				<div class="w_2scol title">Итого</div>
				<div>
					<div class="price"><span>{$cartSummary.sumActive|money}</span></div>
				</div>
			</div>
			<label class="pps pps_button pps_button_important">
				<button id="cart-btn-checkout" data-auth="{if $user.Id}1{else}0{/if}"
				{if $cartSummary.quantityActive==0 || $cartSummary.isSumActiveEnough==0 || $cartSummary.stocksErrors==1}disabled="disabled"{/if}>
					Перейти к оформлению
				</button>
			</label>
			{if $cartSummary.isSumActiveEnough==0}
			<div class="warning">
			⚠️ Минимальная сумма для&nbsp;заказа:&nbsp;{$cartMetrics.settings.orderAmountMin}&nbsp;₽
			</div>
			{/if}
		</div>
	</div>
</div>
<script>
$(document).ready(() => {
	//window.history.pushState({ 'push':1 }, '', '/cart/');
	//console.log('pushed?')
	cart.init();
	cart.metrics({
		items: '{$cartSummary.quantity}'
	});
});
</script>