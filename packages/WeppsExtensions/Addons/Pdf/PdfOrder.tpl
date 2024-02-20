
<div style="font-weight: bold;
	font-size: 16pt;
	padding-left: 5px;
	padding-top: 10mm;
	">Заказ № {$order.Id|number} от {$order.ODate|date_format:"%d.%m.%Y"}</div>
<br />

<div
	style="background-color: #000000;
	width: 100%;
	font-size: 1px;
	height: 2px;">&nbsp;</div>

<table width="100%">
	<tr>
		<td style="width: 30mm;">
			<div>Поставщик:</div>
		</td>
		<td>
			<div style="font-weight: bold;">{$shopInfo.Name}</div>
		</td>
	</tr>
	<tr>
		<td style="width: 30mm;">
			<div>Покупатель:</div>
		</td>
		<td>
			<div style="font-weight: bold;">{$order.Name}</div>
		</td>
	</tr>
</table>


<table class="invoice_items" width="100%" cellpadding="2"
	cellspacing="2">
	<thead>
		<tr>
			<th style="width: 13mm;">№</th>
			<th style="width: 20mm;">Код</th>
			<th>Товар</th>
			<th style="width: 20mm;">Кол-во</th>
			<th style="width: 17mm;">Ед.</th>
			<th style="width: 27mm;">Цена</th>
			<th style="width: 27mm;">Сумма</th>
		</tr>
	</thead>
	<tbody>
		{foreach name="out" item="item" from=$orderPositions}
		<tr>
			<td align="center">{$smarty.foreach.out.iteration}</td>
			<td align="left">{$item.ProductId}</td>
			<td align="left">{$item.Name}</td>
			<td align="right">{$item.ItemQty}</td>
			<td align="left">шт.</td>
			<td align="right">{$item.Price|money}</td>
			<td align="right">{$item.Summ|money}</td>
		</tr>
		{/foreach}
	</tbody>
</table>

<table border="0" width="100%" cellpadding="1" cellspacing="1" class="s002">
	<tr>
		<td></td>
		<td style="width: 27mm;">Итого:</td>
		<td style="width: 27mm;">{$order.Summ|money}</td>
	</tr>
</table>

<br />
<div>
	

	<table cellpadding="2" cellspacing="2" width="100%">
		<tr>
		<td colspan="2">
		Всего наименований {$orderPositionsCount|money} на сумму {$order.Summ|money} рублей.<br /> 
		{$orderSummLetter}<br/>&nbsp;
		</td>
		</tr>
		<tr>
			<td valign="top" width="50%"><b>Покупатель:</b><br /> {$order.Name}<br />
				{$order.AddressIndex}, {$order.CityName}<br /> {$order.Address}<br />
				{$user.Email}<br /> {$user.Phone}</td>
			<td valign="top" width="50%"><b>Доставка и оплата:</b><br /> {$order.ODelivery_Name}<br />
				{$order.OPayment_Name}</td>
		</tr>
		{if $order.OComment}
		<tr>
			<td valign="top" width="50%"><br/><b>Примечание:</b><br /> {$order.OComment}</td>
			<td valign="top" width="50%">&nbsp;</td>
		</tr>
		{/if}
	</table>


</div>
	