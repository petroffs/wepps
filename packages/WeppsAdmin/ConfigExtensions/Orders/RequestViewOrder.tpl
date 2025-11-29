<div class="w_flex w_flex_row w_flex_margin_large1">
	<div class="w_flex_11">
		<div class="title">Товары</div>
		<div class="products">
			<div class="item w_flex w_flex_row">
				<div class="title2 w_flex_13 w_flex_12_view_small w_order_1_view_small">Наименование</div>
				<div class="price w_flex_16 w_flex_14_view_small w_order_3_view_small">Цена</div>
				<div class="quantity w_flex_16 w_flex_14_view_small w_order_4_view_small">Кол.</div>
				<div class="price sum w_flex_16 w_flex_14_view_small w_order_5_view_small w_right_view_small">Сумма</div>
				<div class="options w_right w_flex_16 w_flex_12_view_small w_order_2_view_small">Действия</div>
			</div>
			{foreach name="out" key="key" item="item" from=$order.W_Positions}
			{assign var="options" value=$item.options|json_decode:true}
			<div class="item w_flex w_flex_row w_flex_row_str" data-index="{$key}" data-products="{$item.id}" data-order="{$order.Id}">
				<div class="title2 w_flex_13 w_flex_45_view_small w_order_1_view_small">{$item.name}
					<div class="options">{if $options.name}{$options.name} : {$options.value}{/if}</div>
				</div>
				<div class="price w_flex_16 w_flex_13_view_small w_order_3_view_small">
					<label class="w_label w_input">
						<input type="text" name="price" value="{$item.price}"/>
					</label>
					{if $item.priceTotal}
					<div class="price-tariff price"><span>{$item.priceTotal|money:2}</span></div>
					{/if}
				</div>
				<div class="quantity w_flex_16 w_flex_16 w_flex_13_view_small w_order_4_view_small">
					<label class="w_label w_select">
						<select class="quantity" data-id="{$item.id}" data-option="">
							{for $qty=1 to 100}
							<option value="{$qty}" {if $item.quantity==$qty} selected="selected"{/if}>{$qty}</option>
							{/for}
						</select>
					</label>
				</div>
				<div class="price sum w_flex_16 w_flex_16 w_flex_13_view_small w_order_5_view_small w_right_view_small"><span>{$item.sum|money:2}</span>
				{if $item.sumTotal}
				<div class="price-tariff price"><span>{$item.sumTotal|money:2}</span></div>
				{/if}
				</div>
				<div class="options w_right w_flex_16 w_flex_15_view_small w_order_2_view_small">
					<a class="w_button list-item-save w_hide" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
					<a class="w_button list-item-remove" href="" data-path="position" title="Удалить"><i class="bi bi-trash"></i></a>
				</div>
			</div>
			{/foreach}
			<div class="item item-add w_flex w_flex_row w_flex_row_str" data-order="{$order.Id}" data-name="">
				<div class="title2 w_flex_13 w_flex_45_view_small w_order_1_view_small">
					<label class="w_label w_select">
						<select id="add-products"></select>
					</label>
					<div id="add-products-options" class="options w_hide"></div>
				</div>
				<div class="w_flex_16 w_flex_13_view_small w_order_3_view_small">
					<label class="w_label w_input">
						<input type="text" id="add-products-price" value=""/>
					</label>
				</div>
				<div class="quantity w_flex_16 w_flex_13_view_small w_order_4_view_small">
					<label class="w_label w_select">
						<select class="quantity" id="add-products-quantity">
							{for $quantity=1 to 100}
							<option value="{$quantity}">{$quantity}</option>
							{/for}
						</select>
					</label>
				</div>
				<div class="price sum w_flex_16 w_flex_16 w_flex_13_view_small w_order_5_view_small w_right_view_small"></div>
				<div class="options w_right w_flex_16 w_flex_15_view_small w_order_2_view_small">
					<a class="w_button list-item-add" href="" title="Добавить"><i class="bi bi-plus-lg"></i></a>
				</div>
			</div>
		</div>
		<div class="settings-wrapper w_overflow" data-order="{$order.Id}">
			<div class="w_flex w_flex_row w_flex_start w_flex_row_str w_flex_margin_large">
				<div class="settings w_flex_12 w_flex_11_view_small">
					<div class="title">Опции</div>
					<div class="status item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_14">Статус</div>
						<div class="dd w_flex_12">
							<label class="w_label w_select">
								<select class="status-select"> 
								{foreach name="out" item="item" from=$statuses}
								<option value="{$item.Id}" {if $item.Id==$order.OStatus} selected="selected"{/if}>{$item.Name}</option>
								{/foreach}
								</select>
							</label>
						</div>
						<div class="dd w_flex_14 w_right">
							<a class="w_button list-item-save" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
						</div>
					</div>
					<div class="payments item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_14">Оплата</div>
						{if $order.Payments}
						<div class="dd w_flex_34">
							{assign var="payments" value=$order.Payments|split:";;;"}
							{foreach item="item" from=$payments}
							{assign var="pay" value=$item|strarr}
								<div class="even w_flex w_flex_row w_flex_row_str">
									<div class="w_flex_12">
										{$pay.1} <a href="/_wepps/lists/Payments/{$pay.0}/" target="_blank"><i class="bi bi-link"></i></a>
										<div class="date">{$pay.3}</div>
									</div>
									<div class="price w_right w_flex_12"><span>{$pay.2|money:2}</span></div> 
								</div>
							{/foreach}
						</div>
						<div class="dt w_flex_14"></div>
						{/if}
						<div class="dd w_flex_12">
							<label class="w_label w_input">
								<input type="text" id="add-payments" placeholder="Сумма оплаты" value="{$order.OSumPay}"/>
							</label>
						</div>
						<div class="dd w_flex_14 w_right">
							<a class="w_button list-item-add" href="" title="Добавить платеж"  data-order="{$order.Id}"><i class="bi bi-plus-lg"></i></a>
						</div>
					</div>
					<div class="messages item w_flex w_flex_row w_flex_row_top w_flex_start">
						<div class="dt w_flex_14">Сообщения</div>
						{if $order.W_Messages}
						<div class="dd w_flex_34">
							{foreach item="item" from=$order.W_Messages} 
							<div class="even">
								<div class="text">{$item.EText|@nl2br}</div>
								<div class="date">{$item.EDate} {$item.UsersName}</div>
							</div> 
							{/foreach}
							 
						</div>
						<div class="dt w_flex_14"></div>
						{/if}
						<div class="dd w_flex_12">
							<label class="w_label w_area">
								<textarea id="add-messages"></textarea>
							</label>
							{*
							<label class="w_label w_checkbox">
								<input type="checkbox" value="1" id="add-messages-products"/>
								<span>прикрепить состав заказа</span>
							</label>
							<label class="w_label w_checkbox">
								<input type="checkbox" value="1" id="addPaymentLink"/>
								<span>прикрепить ссылку на оплату</span>
							</label>
							*}
						</div>
						<div class="dd w_flex_14 w_right">
							<a class="w_button list-item-add" href="" title="Добавить"><i class="bi bi-plus-lg"></i></a>
						</div>
					</div>
				</div>
				<div class="settings client w_flex_12 w_flex_11_view_small">
					<div class="title">Заказ <a href="/_wepps/lists/Orders/{$order.Id}/" target="_blank"><i class="bi bi-link"></i></a></div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Номер</div>
						<div class="dd w_flex_23">{$order.Id}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Дата</div>
						<div class="dd w_flex_23">{$order.ODate}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Доставка</div>
						<div class="dd w_flex_23">{$order.ODelivery_Name}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13 w_right">Тариф</div>
						<div class="dd w_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="w_label w_input"><input type="text" name="delivery-tariff" value="{$order.ODeliveryTariff}"></label>
							</div>
							<div class="w_right">
								<a class="w_button list-item-tariff" data-target="delivery-tariff" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13 w_right">Скидка <i class="bi bi-question-circle" title="Меняет цену товаров"></i></div>
						<div class="dd w_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="w_label w_input"><input type="text" name="delivery-discount" value="{$order.ODeliveryDiscount}"></label>
							</div>
							<div class="w_right">
								<a class="w_button list-item-tariff" data-target="delivery-discount" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Оплата</div>
						<div class="dd w_flex_23">{$order.OPayment_Name}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13 w_right">Тариф  <i class="bi bi-question-circle" title="Меняет цену товаров"></i></div>
						<div class="dd w_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="w_label w_input"><input type="text" name="payment-tariff" value="{$order.OPaymentTariff}"></label>
							</div>
							<div class="w_right">
								<a class="w_button list-item-tariff" data-target="payment-tariff" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
							</div>
						</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13 w_right">Скидка  <i class="bi bi-question-circle" title="Меняет цену товаров"></i></div>
						<div class="dd w_flex_23 w_grid w_3col">
							<div class="w_2scol">
								<label class="w_label w_input"><input type="text" name="payment-discount" value="{$order.OPaymentDiscount}"></label>
							</div>
							<div class="w_right">
								<a class="w_button list-item-tariff" data-target="payment-discount" href="" title="Сохранить изменения"><i class="bi bi-save"></i></a>
							</div>
						</div>
					</div>
					<div class="w_interval"></div>
					<div class="title">Клиент <a href="/_wepps/lists/s_Users/{$order.UserId}/" target="_blank"><i class="bi bi-link"></i></a></div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Номер</div>
						<div class="dd w_flex_23">{$order.UserId}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Имя</div>
						<div class="dd w_flex_23">{$order.Name}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">Телефон</div>
						<div class="dd w_flex_23">{$order.Phone}</div>
					</div>
					<div class="item w_flex w_flex_row w_flex_start">
						<div class="dt w_flex_13">E-mail</div>
						<div class="dd w_flex_23">{$order.Email}</div>
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