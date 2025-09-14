<h2>Заказ №{$order.Id}</h2>
<div class="order w_grid w_2col w_gap">
	<div class="w_2scol">
		<h3>Товары</h3>
		<section class="orders-table">
			<table class="w_table">
				<tbody>
					<tr class="w_table_active">
						<th class="w_table_12">Товары</th>
						<th class="w_table_16">Цена</th>
						<th class="w_table_16">Кол.</th>
						<th class="w_table_16">Сумма</th>
					</tr>
					{foreach name="out" key="key" item="item" from=$order.W_Positions}
					<tr>
						<td>{$item.name}</td>
						<td>{$item.price|money:2}</td>
						<td>{$item.quantity}</td>
						<td>{$item.sum|money:2}</td>
					</tr>
					{/foreach}
					<tr class="w_table_active">
						<th class="w_table_12">Сервис</th>
						<th class="w_table_16"></th>
						<th class="w_table_16"></th>
						<th class="w_table_16"></th>
					</tr>
					<tr>
						<td>Доставка Тариф ({$order.ODelivery_Name})</td>
						<td>{$order.ODeliveryTariff}</td>
						<td>1</td>
						<td>{$order.ODeliveryTariff}</td>
					</tr>
					{if $order.ODeliveryDiscount>0}
					<tr>
						<td>Скидка на товары за способ доставки</td>
						<td>- {$order.ODeliveryDiscount}</td>
						<td>1</td>
						<td>- {$order.ODeliveryDiscount}</td>
					</tr>
					{/if}
					{if $order.OPaymentTariff>0}
					<tr>
						<td>Оплата Тариф ({$order.OPayment_Name})</td>
						<td>{$order.OPaymentTariff}</td>
						<td>1</td>
						<td>{$order.OPaymentTariff}</td>
					</tr>
					{/if}
					{if $order.OPaymentDiscount>0}
					<tr>
						<td>Скидка на товары за способ оплаты</td>
						<td>- {$order.OPaymentDiscount}</td>
						<td>1</td>
						<td>- {$order.OPaymentDiscount}</td>
					</tr>
					{/if}
					<tr class="w_table_active">
							<td colspan="2"></td>
							<th>Итого:</th>
							<th>{$order.OSum|money:2}</th>
						</tr>
				</tbody>
			</table>
		</section>
	</div>
	<div class="">
		<h3>Информация</h3>
		<section class="orders-table">
			<table class="w_table">
				<tbody>
					<tr>
						<th class="w_table_12">Номер</th>
						<td class="w_table_12">{$order.Id}</td>
					</tr>
					<tr>
						<th class="w_table_12">Дата</th>
						<td class="w_table_12">{$order.ODate|date_format:'%d.%m.%Y'}</td>
					</tr>
					<tr>
						<th class="w_table_12">Доставка</th>
						<td class="w_table_12">{$order.ODelivery_Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Оплата</th>
						<td class="w_table_12">{$order.OPayment_Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Клиент</th>
						<td class="w_table_12">{$order.Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Телефон</th>
						<td class="w_table_12">{$order.Phone}</td>
					</tr>
					<tr>
						<th class="w_table_12">E-mail</th>
						<td class="w_table_12">{$order.Email}</td>
					</tr>
				</tbody>
			</table>
		</section>
	</div>
	<div class="">
		<h3>Сообщения</h3>
		<section class="orders-table">
			<table class="w_table">
				<tbody>
					<tr>
						<th class="w_table_12">Номер</th>
						<td class="w_table_12">{$order.Id}</td>
					</tr>
					<tr>
						<th class="w_table_12">Дата</th>
						<td class="w_table_12">{$order.ODate|date_format:'%d.%m.%Y'}</td>
					</tr>
					<tr>
						<th class="w_table_12">Доставка</th>
						<td class="w_table_12">{$order.ODelivery_Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Оплата</th>
						<td class="w_table_12">{$order.OPayment_Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Клиент</th>
						<td class="w_table_12">{$order.Name}</td>
					</tr>
					<tr>
						<th class="w_table_12">Телефон</th>
						<td class="w_table_12">{$order.Phone}</td>
					</tr>
					<tr>
						<th class="w_table_12">E-mail</th>
						<td class="w_table_12">{$order.Email}</td>
					</tr>
				</tbody>
			</table>
		</section>
	</div>
</div>