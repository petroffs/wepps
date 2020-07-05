<div class="cartabout pps_flex_13 pps_flex_11_view_medium">
	<div class="block-bg1 pps_padding">
		<div class="block-bg2 title pps_padding">Ваша корзина</div>
		<div class="descr">
			<ul>
				<li>В Вашей корзине {$cartSummary.qty} товар{$cartSummary.qty|russ2}
					на&nbsp;сумму&nbsp;&ndash;&nbsp;<span class="price"><span>{$cartSummary.priceAmount|money}</span></span>
				</li>
				<li id="priceDeliveryPaymentBlock">Итоговая стоимость заказа <span
					id="priceDeliveryPayment">(без учета доставки)</span>&nbsp;&ndash;&nbsp;<span
					class="price"><span id="priceTotal">{$cartSummary.priceAmount|money}</span></span></li>
				<li>После оформления заказа с&nbsp;Вами свяжется менеджер
					для&nbsp;уточнения деталей доставки и&nbsp;оплаты</li>
				<li>В личном кабинете Вы&nbsp;сможете: отлеживать статус заказа,
					отправлять и&nbsp;получать сообщения</li>
			</ul>
		</div>
	</div>
</div>