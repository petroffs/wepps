<div class="w_grid w_3col w_gap_medium">
	<div class="w_2scol w_3scol_view_medium">
		<div class="content-block">
			<h1>Корзина</h1>
		</div>
		<div class="content-block cart-city">
			<h2>Выберите ваш регион доставки</h2>
			<label class="pps pps_input">
				<i class="pps_field_empty"></i>
				<input type="text" name="citiesId" id="cart-city"
					placeholder="Начните вводить город, и выберите из подсказки" value="{$cartCity.Title}" data-city="{$cartCity.Name}" data-region="{$cartCity.RegionsName}"
					data-id="{$cartCity.Id}" autocomplete="off" />
			</label>
		</div>
		<div class="content-block cart-variants cart-delivery{if !$delivery} w_hide{/if}" id="cart-delivery-checkout">
			<h2>Выберите способ доставки</h2>
			<div class="header">
				<div class="title">Наименование</div>
				<div class="period">Сроки</div>
				<div class="price">Тариф</div>
			</div>
			{foreach name="out" item="item" from=$delivery}
				<label class="pps pps_radio">
					<input type="radio" name="delivery" value="{$item.Id}" data-price="0" {if $item.Id==$deliveryActive}
						checked{/if} autocomplete="off"/>
					<span class="title">{$item.Name}</span>
					<span class="period"><span>{$item.Addons.tariff.period} дн</span></span>
					<span class="price"><span>{$item.Addons.tariff.price|money}</span></span>
					{if $item.Addons.discount.price>0}
					<span class="text attention">{$item.Addons.discount.text}</span>
					<span class="price attention"><span>-{$item.Addons.discount.price|money}</span></span>
					{/if}
				</label>
			{/foreach}
		</div>
		{$deliveryOperationsTpl}
		<div class="content-block cart-variants cart-payments{if !$payments} w_hide{/if}" id="cart-payments-checkout">
			<h2>Выберите способ оплаты {if !$payments}w_hide{/if}</h2>
			<div class="header">
				<div class="title">Наименование</div>
			</div>
			{foreach name="out" item="item" from=$payments}
				<label class="pps pps_radio">
					<input type="radio" name="payments" value="{$item.Id}" data-price="0" {if $item.Id==$paymentsActive}
						checked{/if} autocomplete="off"/>
					<span class="title">{$item.Name}</span>
					{if $item.Addons.tariff.price>0}
					<span class="text attention">{$item.Addons.tariff.text}</span>
					<span class="price attention"><span>{$item.Addons.tariff.price|money}</span></span>
					{/if}
					{if $item.Addons.discount.price>0}
					<span class="text attention">{$item.Addons.discount.text}</span>
					<span class="price attention"><span>-{$item.Addons.discount.price|money}</span></span>
					{/if}
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
			{if $cartSummary.sumSaving|money}
			<div class="w_grid w_3col">
				<div class="w_2scol title">Скидка</div>
				<div>
					<div class="price">
						<span>- {$cartSummary.sumSaving|money}</span>
					</div>
				</div>
			</div>
			{/if}
			{if $cartSummary.delivery.tariff.status}
			<div class="w_grid w_3col">
				<div class="w_2scol title">Доставка</div>
				<div>
					<div class="price">
						<span>{$cartSummary.delivery.tariff.price|money}</span>
					</div>
				</div>
			</div>
			{/if}
			{if $cartSummary.delivery.discount.status==200}
			<div class="w_grid w_3col">
				<div class="w_2scol title">{$cartSummary.delivery.discount.title}</div>
				<div>
					<div class="price">
						<span>-{$cartSummary.delivery.discount.price|money}</span>
					</div>
				</div>
			</div>
			{/if}
			{if $cartSummary.payments.tariff.status==200}
			<div class="w_grid w_3col">
				<div class="w_2scol title">{$cartSummary.payments.tariff.text}</div>
				<div>
					<div class="price">
						<span>{$cartSummary.payments.tariff.price|money}</span>
					</div>
				</div>
			</div>
			{/if}
			{if $cartSummary.payments.discount.status==200}
			<div class="w_grid w_3col">
				<div class="w_2scol title">{$cartSummary.payments.discount.text}</div>
				<div>
					<div class="price">
						<span>-{$cartSummary.payments.discount.price|money}</span>
					</div>
				</div>
			</div>
			{/if}
			<div class="w_grid w_3col">
				<div class="w_2scol title">Итого</div>
				<div>
					<div class="price">
						<span>{$cartSummary.sumTotal|money}</span>
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