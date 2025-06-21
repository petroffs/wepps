<div class="pps_flex pps_flex_row pps_flex_margin_large1">
	<div class="pps_flex_11">
		<div class="title">Товары</div>
		<div class="products">
			<div class="item pps_flex pps_flex_row">
				<div class="title2 pps_flex_13 pps_flex_12_view_small pps_order_1_view_small">Наименование</div>
				<div class="price pps_flex_16 pps_flex_14_view_small pps_order_3_view_small">Цена</div>
				<div class="quantity pps_flex_16 pps_flex_14_view_small pps_order_4_view_small">Кол.</div>
				<div class="price sum pps_flex_16 pps_flex_14_view_small pps_order_5_view_small pps_right_view_small">Сумма</div>
				<div class="options pps_right pps_flex_16 pps_flex_12_view_small pps_order_2_view_small">Действия</div>
			</div>
			{foreach name="out" key="key" item="item" from=$products}
			{assign var="options" value=$item.options|json_decode:true}
			<div class="item pps_flex pps_flex_row" data-index="{$key}" data-products="{$item.id}" data-order="{$order.Id}">
				<div class="title2 pps_flex_13 pps_flex_45_view_small pps_order_1_view_small">{$item.name}
					<div class="options">{if $options.name}{$options.name} : {$options.value}{/if}</div>
				</div>
				<div class="price pps_flex_16 pps_flex_13_view_small pps_order_3_view_small">
					<label class="pps pps_input">
						<input type="text" name="price" value="{$item.price}"/>
					</label>
				</div>
				<div class="quantity pps_flex_16 pps_flex_16 pps_flex_13_view_small pps_order_4_view_small">
					<label class="pps pps_select">
						<select class="quantity" data-id="{$item.id}" data-option="">
							{for $qty=1 to 100}
							<option value="{$qty}" {if $item.quantity==$qty} selected="selected"{/if}>{$qty}</option>
							{/for}
						</select>
					</label>
				</div>
				<div class="price sum pps_flex_16 pps_flex_16 pps_flex_13_view_small pps_order_5_view_small pps_right_view_small"><span>{$item.sum|money:2}</span></div>
				<div class="options pps_right pps_flex_16 pps_flex_15_view_small pps_order_2_view_small">
					<a class="pps_button list-item-save pps_hide" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
					<a class="pps_button list-item-remove" href="" data-path="position" title="Удалить"><i class="fa fa-remove"></i></a>
				</div>
			</div>
			{/foreach}
			<div class="item item-add pps_flex pps_flex_row pps_flex_row_str" data-order="{$order.Id}" data-name="">
				<div class="title2 pps_flex_13 pps_flex_45_view_small pps_order_1_view_small">
					<label class="pps pps_select">
						<select id="add-products"></select>
					</label>
					<div id="add-products-options" class="options pps_hide"></div>
				</div>
				<div class="pps_flex_16 pps_flex_13_view_small pps_order_3_view_small">
					<label class="pps pps_input">
						<input type="text" id="add-products-price" value=""/>
					</label>
				</div>
				<div class="quantity pps_flex_16 pps_flex_13_view_small pps_order_4_view_small">
					<label class="pps pps_select">
						<select class="quantity" id="add-products-quantity">
							{for $quantity=1 to 100}
							<option value="{$quantity}">{$quantity}</option>
							{/for}
						</select>
					</label>
				</div>
				<div class="price sum pps_flex_16 pps_flex_16 pps_flex_13_view_small pps_order_5_view_small pps_right_view_small"></div>
				<div class="options pps_right pps_flex_16 pps_flex_15_view_small pps_order_2_view_small">
					<a class="pps_button list-item-add" href="" title="Добавить"><i class="fa fa-plus"></i></a>
				</div>
			</div>
		</div>
		<div class="settings-wrapper pps_overflow" data-order="{$order.Id}">
			<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large">
				<div class="settings pps_flex_12 pps_flex_11_view_small">
					<div class="title">Опции</div>
					<div class="status item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_14">Статус</div>
						<div class="dd pps_flex_12">
							<label class="pps pps_select">
								<select class="status-select"> 
								{foreach name="out" item="item" from=$statuses}
								<option value="{$item.Id}" {if $item.Id==$order.OStatus} selected="selected"{/if}>{$item.Name}</option>
								{/foreach}
								</select>
							</label>
						</div>
						<div class="dd pps_flex_14 pps_right">
							<a class="pps_button list-item-save" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
						</div>
					</div>
					<div class="payments item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_14">Оплата</div>
						{if $order.Payments}
						<div class="dd pps_flex_34">
							{assign var="payments" value=$order.Payments|split:";;;"}
							{foreach item="item" from=$payments}
							{assign var="pay" value=$item|strarr}
								<div class="even pps_flex pps_flex_row pps_flex_row_str">
									<div class="pps_flex_12">
										{$pay.1} <a href="/_wepps/lists/Payments/{$pay.0}/" target="_blank"><i class="fa fa-link"></i></a>
										<div class="date">{$pay.3}</div>
									</div>
									<div class="price pps_right pps_flex_12"><span>{$pay.2|money:2}</span></div> 
								</div>
							{/foreach}
						</div>
						<div class="dt pps_flex_14"></div>
						{/if}
						<div class="dd pps_flex_12">
							<label class="pps pps_input">
								<input type="text" id="add-payments" placeholder="Сумма оплаты" value="{$order.OSumPay}"/>
							</label>
						</div>
						<div class="dd pps_flex_14 pps_right">
							<a class="pps_button list-item-add" href="" title="Добавить платеж"  data-order="{$order.Id}"><i class="fa fa-plus"></i></a>
						</div>
					</div>
					<div class="messages item pps_flex pps_flex_row pps_flex_row_top pps_flex_start">
						<div class="dt pps_flex_14">Сообщения</div>
						{if $order.Messages}
						<div class="dd pps_flex_34">
							{foreach item="item" from=$order.Messages} 
							<div class="even">
								<div class="text">{$item.text|@nl2br}</div>
								<div class="date">{$item.date} {$item.user}</div>
							</div> 
							{/foreach}
							 
						</div>
						<div class="dt pps_flex_14"></div>
						{/if}
						<div class="dd pps_flex_12">
							<label class="pps pps_area">
								<textarea id="add-messages"></textarea>
							</label>
							{*
							<label class="pps pps_checkbox">
								<input type="checkbox" value="1" id="add-messages-products"/>
								<span>прикрепить состав заказа</span>
							</label>
							<label class="pps pps_checkbox">
								<input type="checkbox" value="1" id="addPaymentLink"/>
								<span>прикрепить ссылку на оплату</span>
							</label>
							*}
						</div>
						<div class="dd pps_flex_14 pps_right">
							<a class="pps_button list-item-add" href="" title="Добавить"><i class="fa fa-plus"></i></a>
						</div>
					</div>
				</div>
				<div class="settings client pps_flex_12 pps_flex_11_view_small">
					<div class="title">Заказ <a href="/_wepps/lists/Orders/{$order.Id}/" target="_blank"><i class="fa fa-link"></i></a></div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Номер</div>
						<div class="dd pps_flex_23">{$order.Id}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Дата</div>
						<div class="dd pps_flex_23">{$order.ODate}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Доставка</div>
						<div class="dd pps_flex_23">{$order.ODelivery_Name}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13 pps_right">Тариф</div>
						<div class="dd pps_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="pps pps_input"><input type="text" name="delivery-tariff" value="{$order.ODeliveryTariff}"></label>
							</div>
							<div class="pps_right">
								<a class="pps_button list-item-tariff" data-target="delivery-tariff" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13 pps_right">Скидка <i class="fa fa-question-circle-o" title="Меняет цену товаров"></i></div>
						<div class="dd pps_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="pps pps_input"><input type="text" name="delivery-discount" value="{$order.ODeliveryDescount}"></label>
							</div>
							<div class="pps_right">
								<a class="pps_button list-item-tariff" data-target="delivery-discount" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Оплата</div>
						<div class="dd pps_flex_23">{$order.OPayment_Name}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13 pps_right">Тариф  <i class="fa fa-question-circle-o" title="Меняет цену товаров"></i></div>
						<div class="dd pps_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="pps pps_input"><input type="text" name="payment-tariff" value="{$order.OPaymentTariff}"></label>
							</div>
							<div class="pps_right">
								<a class="pps_button list-item-tariff" data-target="payment-tariff" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13 pps_right">Скидка  <i class="fa fa-question-circle-o" title="Меняет цену товаров"></i></div>
						<div class="dd pps_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="pps pps_input"><input type="text" name="payment-discount" value="{$order.OPaymentDiscount}"></label>
							</div>
							<div class="pps_right">
								<a class="pps_button list-item-tariff" data-target="payment-discount" href="" title="Сохранить изменения"><i class="fa fa-save"></i></a>
							</div>
						</div>
					</div>
					<div class="pps_interval"></div>
					<div class="title">Клиент <a href="/_wepps/lists/s_Users/{$order.UserId}/" target="_blank"><i class="fa fa-link"></i></a></div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Номер</div>
						<div class="dd pps_flex_23">{$order.UserId}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Имя</div>
						<div class="dd pps_flex_23">{$order.Name}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">Телефон</div>
						<div class="dd pps_flex_23">{$order.Phone}</div>
					</div>
					<div class="item pps_flex pps_flex_row pps_flex_start">
						<div class="dt pps_flex_13">E-mail</div>
						<div class="dd pps_flex_23">{$order.Email}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
var orderId = '{$order.Id}';
var orderSum= '{$order.OSum}';
</script>

{$get.cssjs}