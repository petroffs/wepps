<div class="page cart cart-settings">
	<section>
		<div class="w_grid w_3col w_gap_medium">
			<div class="w_2scol w_3scol_view_medium">
				<div class="content-block">
					<h1>Корзина</h1>
				</div>
				<div class="content-block cart-region">
					<h2>Выберите ваш регион доставки</h2>
					<label class="pps pps_input">
						<input type="text" name="region" id="cart-region" placeholder="Начните вводить город, и выберите из подсказки"/>
					</label>
					
				</div>
				<div class="content-block cart-delivery w_hide" id="cart-delivey-settings">
					<h2>Выберите способ доставки</h2>
				</div>
				<div class="content-block cart-payment w_hide" id="cart-payment-settings">
					<h2>Выберите способ оплаты</h2>
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
					<label class="pps pps_button pps_button_important"><button
							id="order-place">Разместить заказ</button></label>
				</div>
			</div>
		</div>
	</section>
</div>