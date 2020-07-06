{$js}
<div
	class="cart default pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
	<div class="cartabout pps_flex_23 pps_flex_11_view_medium">
		<div class="block-bg1 pps_padding">
			<div class="block-bg2 title pps_padding">Информация о покупателе</div>
			<div class="descr">
				<ul>
					<li>{$user.Name} <a href="/profile/personal.html"><i
							class="fa fa-edit"></i></a></li>
				</ul>
			</div>
			<form
				action="javascript:formSenderWepps.send('addOrder','addOrderForm','/ext/Cart/Request.php')"
				id="addOrderForm">
				<div class="cartForm">
					<!-- Город -->
					<div class="city pps_bg_silver">
						<label class="pps pps_input">
							<div>Город</div> <input type="text" id="cities" name="city"
							value="{$cityChecked}" />
						</label>
					</div>
					<div class="pps_interval_small"></div>
					<!-- Доставка, Оплата -->
					<div
						class="delivery-payment pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
						<div class="pps_flex_12 pps_flex_11_view_small" id="delivery"></div>
						<div class="pps_flex_12 pps_flex_11_view_small" id="payment"></div>
					</div>
					<div class="pps_interval_small"></div>
					<!-- Адрес -->
					<div
						class="cart-other pps_flex pps_flex_row pps_flex_str pps_flex_margin">
						<div class="pps_flex_14">
							<label class="pps pps_input">
								<div>Индекс</div> <input type="text" name="addressIndex"
								value="{$user.AddressIndex}" />
							</label>
						</div>
						<div class="pps_flex_34">
							<label class="pps pps_input">
								<div>Полный адрес</div> <input type="text" name="address"
								value="{$user.Address}" />
							</label>
						</div>
					</div>
					<div class="pps_interval_small"></div>
					<!-- Контакты -->
					<div
						class="cart-other pps_flex pps_flex_row pps_flex_str pps_flex_margin">
						<div class="pps_flex_12">
							<label class="pps pps_input"><div>Телефон</div> <input
								type="text" value="{$user.Phone}" name="phone" /> </label>
						</div>
						<div class="pps_flex_12">
							<label class="pps pps_input"><div>Email</div> <input type="text"
								name="email" value="{$user.Email}" readonly /> </label>
						</div>
					</div>

					<div class="pps_interval_small"></div>
					<!-- Комментарий -->
					<div
						class="cart-other pps_flex pps_flex_row pps_flex_str pps_flex_row_bottom pps_flex_margin">
						<div class="pps_flex_12 pps_flex_11_view_small">
							<label class="pps pps_area">
								<div>Комментарий</div> <textarea rows="" cols="" name="comment"></textarea>
							</label>
						</div>
						<div class="pps_flex_12 pps_flex_11_view_small pps_right">
							<div class="pps_interval_small"></div>
							<div class="final-info">


								<div>Итоговая стоимость заказа</div>
								<span id="priceDeliveryPayment2">(без учета доставки)</span>&nbsp;&ndash;&nbsp;<span
									class="price"><span id="priceTotal2">{$cartSummary.priceAmount|money}</span></span>


							</div>
							<label class="pps pps_button pps_button2"> <input type="submit"
								value="Оформить заказ" disabled="disabled" id="submitOrder" />
							</label>
						</div>
					</div>
				</div>
			</form>
		</div>
		{$profileStaffTpl}
	</div>
	{$cartAboutTpl}
</div>
