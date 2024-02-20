<table border="0" width="100%">
	<tr>
		<td class="s000">
			Внимание! Оплата данного счета означает согласие с
				условиями поставки товара. Уведомление об оплате обязательно, в
				противном случае не гарантируется наличие товара на складе. Товар
				отпускается по факту прихода денег на р/с Поставщика, самовывозом,
				при наличии доверенности и паспорта.
		</td>
	</tr>
	<tr>
		<td class="s001">
			<div>Образец заполнения платежного поручения</div>
		</td>
	</tr>
</table>


<table width="100%" cellpadding="2" cellspacing="2"
	class="invoice_bank_rekv">
	<tr>
		<td colspan="2" rowspan="2"
			style="width: 105mm; border: 1px solid">
			<table width="100%" border="0" cellpadding="0" cellspacing="0"
				style="height: 13mm;">
				<tr>
					<td valign="top">
						<div>{$shopInfo.UrBank}</div>
					</td>
				</tr>
				<tr>
					<td valign="bottom" style="height: 3mm;">
						<div style="font-size: 8pt;">Банк получателя</div>
					</td>
				</tr>
			</table>
		</td>
		<td style="height: 50%;
	width: 25mm;
	border: 1px solid">
			<div>БИK</div>
		</td>
		<td rowspan="2" valign="top" style="width: 60mm;
	border: 1px solid">
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td>{$shopInfo.UrBIK}</td>
				</tr>
				<tr>
					<td style="padding-top: 2mm">{$shopInfo.UrCorSchet}</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td style="width: 25mm;
	border: 1px solid">
			<div>Сч. №</div>
		</td>
	</tr>
	<tr>
		<td
			style="min-height: 6mm;
	height: auto;
	width: 50mm;
	border: 1px solid">
			<div>ИНН {$shopInfo.UrINN}</div>
		</td>
		<td
			style="min-height: 6mm;
	height: auto;
	width: 55mm;
	border: 1px solid">
			<div>КПП {$shopInfo.UrKPP}</div>
		</td>
		<td rowspan="2"
			style="min-height: 19mm;
	height: auto;
	vertical-align: top;
	width: 25mm;
	border: 1px solid">
			<div>Сч. №</div>
		</td>
		<td rowspan="2"
			style="min-height: 19mm;
	height: auto;
	vertical-align: top;
	width: 60mm;
	border: 1px solid">
			<div>{$shopInfo.UrRaschShet}</div>
		</td>
	</tr>
	<tr>
		<td colspan="2"
			style="min-height: 13mm;
	height: auto;
	border: 1px solid">

			<table border="0" cellpadding="0" cellspacing="0"
				style="height: 13mm;
	width: 105mm;">
				<tr>
					<td valign="top">
						<div>{$shopInfo.Name}</div>
					</td>
				</tr>
				<tr>
					<td valign="bottom" style="height: 3mm;">
						<div style="font-size: 8pt;">Получатель</div>
					</td>
				</tr>
			</table>

		</td>
	</tr>
</table>
<br />

<div style="font-weight: bold;
	font-size: 16pt;
	padding-left: 5px;">Счет № {$order.Id|number} от {$order.ODate|date_format:"%d.%m.%Y"}</div>
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
	
	{if $orderNDS}
	<tr>
		<td colspan="2">В том числе НДС:</td>
		<td style="width: 27mm;">{$orderNDS|money}</td>
	</tr>
	{else}
	<tr>
		<td colspan="2">НДС не облагается</td>
		<td style="width: 27mm;"></td>
	</tr>
	{/if}
	
	
</table>

<br />
<div>
	Всего наименований {$orderPositionsCount|money} на сумму {$order.Summ|money} рублей.<br /> 
	{$orderSummLetter}
</div>
<br />
<br />
<div
	style="background-color: #000000;
	width: 100%;
	font-size: 1px;
	height: 2px;">&nbsp;</div>
<br />

<div>Руководитель ______________________ (Фамилия И.О.)</div>
<br />

<div>Главный бухгалтер ______________________ (Фамилия И.О.)</div>
<br />

<div style="width: 85mm;
	text-align: center;">М.П.</div>
<br />


<div style="font-size: 8pt;">Счет действителен к оплате в течении трех дней.</div>
	