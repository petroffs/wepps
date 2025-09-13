<h2>Заказ №{$order.Id}</h2>
<div class="order w_grid w_2col w_gap">
	<div class="w_2scol">
		<h3>Товары</h3>
		<section class="orders-table">
			<table class="w_table">
				<tbody>
					<tr class="w_table_active">
						<th>Наименование</th>
						<th>Цена</th>
						<th>Кол.</th>
						<th>Сумма</th>
						<th>Сумма итог</th>
					</tr>
					{foreach name="out" key="key" item="item" from=$products}
						<tr>
							<td>{$item.name}</td>
							<td>{$item.price|money:2}</td>
							<td>{$item.quantity}</td>
							<td>{$item.sum|money:2}</td>
							<td>{$item.sumTotal|money:2}</td>
						</tr>
					{/foreach}
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