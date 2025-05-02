<div class="w_grid w_3col w_gap_medium">
	<div class="w_2scol w_3scol_view_medium">
		<div class="content-block">
			<h1>Корзина</h1>
		</div>
		<div class="content-block cart-city">
			<h2>Выберите ваш регион доставки</h2>
			<label class="pps pps_input">
				<input type="text" name="citiesId" id="cart-city"
					placeholder="Начните вводить город, и выберите из подсказки" value="{$cartCity.Title}"
					data-id="{$cartCity.Id}" autocomplete="off" />
			</label>
		</div>
		<div class="content-block cart-variants cart-delivery{if !$delivery} w_hide{/if}" id="cart-delivery-checkout">
			<h2>Выберите способ доставки</h2>
			{foreach name="out" item="item" from=$delivery}
				<label class="pps pps_radio">
					<input type="radio" name="delivery" value="{$item.Id}" data-price="0" {if $item.Id==$deliveryActive}
						checked{/if} autocomplete="off"/>
					<span class="title">{$item.Name}</span>
					<span class="period"><span>{$item.Addons.tariff.period} дн</span></span>
					<span class="price"><span>{$item.Addons.tariff.price}</span></span>
				</label>
			{/foreach}
		</div>
		<div class="content-block cart-variants cart-payments{if !$payments} w_hide{/if}" id="cart-payments-checkout">
			<h2>Выберите способ оплаты {if !$payments}w_hide{/if}</h2>
			{foreach name="out" item="item" from=$payments}
				<label class="pps pps_radio">
					<input type="radio" name="payments" value="{$item.Id}" data-price="0" {if $item.Id==$paymentsActive}
						checked{/if} autocomplete="off"/>
					<span class="title">{$item.Name}</span>
				</label>
			{/foreach}
		</div>
	</div>
	<div class="w_3scol_view_medium">
		<div class="content-block cart-total">
			<h2>Детали заказа</h2>
			<div class="w_grid w_3col">
				<div class="w_2scol title">{$cartSummary.quantityActive}
					{$cartText.goodsCount}</div>
				<div class="pps_right">
					<div class="price">
						<span>{$cartSummary.sumBefore|money}</span>
					</div>
				</div>
			</div>
			<div class="w_grid w_3col">
				<div class="w_2scol title">Скидка</div>
				<div>
					<div class="price">
						<span>{$cartSummary.sumSaving|money}</span>
					</div>
				</div>
			</div>
			<div class="w_grid w_3col">
				<div class="w_2scol title">Итого</div>
				<div>
					<div class="price">
						<span>{$cartSummary.sumActive|money}</span>
					</div>
				</div>
			</div>
			<label class="pps pps_button pps_button_important">
				<button id="cart-btn-confirm" {if !$deliveryActive || !$paymentsActive} disabled{/if}>
					Разместить заказ
				</button>
			</label>
		</div>
	</div>
</div>
<script>
	$(document).ready(() => {
		cart.initCheckout();
	});
</script>