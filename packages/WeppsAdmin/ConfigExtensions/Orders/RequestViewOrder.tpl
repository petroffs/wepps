<div class="pps_flex pps_flex_row pps_flex_margin_large1">
	<div class="pps_flex_11">
		<div class="title">Позиции</div>
		<div class="positions">
			<div class="item pps_flex pps_flex_row">
				<div class="title2 pps_flex_13">Наименование</div>
				<div class="price pps_flex_16">Цена</div>
				<div class="qty pps_flex_16">Кол.</div>
				<div class="price sum pps_flex_16">Сумма</div>
				<div class="options pps_flex_16">Действия</div>
			</div>
			{foreach name="out" item="item" from=$positions}
			{assign var="options" value=$item.Options|json_decode:true}
			<div class="item pps_flex pps_flex_row" data-position="{$item.id}" data-order="{$item.OrderId}">
				<div class="title2 pps_flex_13">{$item.name}
				<div class="options">{if $options.Title}{$options.Title} : {$options.Value}{/if}</div>
				</div>
				<div class="price pps_flex_16">
					<label class="pps pps_input">
						<input type="text" value="{$item.price}"/>
					</label>
				
				</div>
				<div class="qty pps_flex_16">
					<label class="pps pps_select"> <select class="qtyselect"
						data-id="{$item.Data.Id}" data-option="{$item.Data.Options.Id}"> {for $qty=1 to 100}
							<option value="{$qty}" {if $item.quantity==$qty} selected="selected"{/if}>{$qty}</option>
							{/for}
					</select>
					</label>
				</div>
				<div class="price sum pps_flex_16">{$item.sum|money:1}</div>
				<div class="options pps_flex_16">
					<a class="list-item-save pps_hide" href="" title="Сохранить изменения"><i class="fa fa-2x fa-save"></i></a>
					<a class="list-item-remove" href="" data-path="position" title="Удалить"><i class="fa fa-2x fa-remove"></i></a>
				</div>
			</div>
			{/foreach}
			<div class="item itemAdd pps_flex pps_flex_row pps_flex_row_str pps_flex_start" data-order="{$get.order.Id}">
				<div class="pps_flex_13">
					<label class="pps pps_input">
						<input type="text" id="addPosition"/>
					</label>
					<div id="addPositionOptions" class="options pps_hide"></div>
				</div>
				<div class="pps_flex_16">
					<label class="pps pps_input">
						<input type="text" id="addPositionPrice" value=""/>
					</label>
				</div>
				<div class="qty pps_flex_16">
					<label class="pps pps_select"> <select class="qtyselect"
						data-id="{$item.Data.Id}" data-option="{$item.Data.Options.Id}"> {for $qty=1 to 10}
							<option value="{$qty}">{$qty}</option>
							{/for}
					</select>
					</label>
				</div>
				<div class="price sum pps_flex_16"></div>
				<div class="options pps_flex_16">
					<a class="list-item-add" href="" title="Добавить"><i class="fa fa-2x fa-plus"></i></a>
				</div>
			</div>
		</div>
		
		<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large">
			<div class="actions pps_flex_12">
				<div class="title">Опции</div>
				<div class="status item pps_flex pps_flex_row pps_flex_start">
					<div class="dt pps_flex_13">Статус</div>
					<div class="dd pps_flex_13">
						<label class="pps pps_select"> <select class="statusselect"
						data-order="{$get.order.Id}"> 
						{foreach name="out" item="item" from=$get.statuses}
							<option value="{$item.Id}" {if $item.Id==$get.order.TStatus} selected="selected"{/if}>{$item.Name}</option>
						{/foreach}
						</select>
						</label>
					</div>
					<div class="dd pps_flex_13 pps_center">
						<a class="list-item-save" href="" title="Сохранить изменения"><i class="fa fa-2x fa-save"></i></a>
					</div>
				</div>
				
				<div class="payment item pps_flex pps_flex_row pps_flex_start">
					<div class="dt pps_flex_13">Оплата</div>
					<div class="dd pps_flex_13">
						{*
						<label class="pps pps_checkbox">
							<input type="checkbox" value="1" id="addPayment"/>
							<span>оплачено</span>
						</label>
						*}
						<label class="pps pps_input">
							<input type="number" id="addPaymentValue" data-order="{$get.order.Id}" placeholder="Сумма оплаты" value="{$get.order.OBuySumm}"/>
						</label>
						{if $get.order.OBuySumm}
						<div>{$get.order.OBuyDate}</div>
						{/if}
					</div>
					<div class="dd pps_flex_13 pps_center">
						<a class="list-item-save" href="" title="Сохранить изменения"><i class="fa fa-2x fa-save"></i></a>
					</div>
				</div>
				
				<div class="messages item pps_flex pps_flex_row pps_flex_row_top pps_flex_start" id="messages">
					<div class="dt pps_flex_13">Комментарий</div>
					<div class="dd pps_flex_23">
						<div class="items">
							{foreach name="out" item="item" from=$get.messages}
							<div class="item">
								<div class="item-date">{$item.MessDate}{if $item.OrderInfo==1} <i class="fa fa-file-text-o" title="Состав заказа прикреплен"></i>{/if}</div>
								<div class="item-descr">{$item.MessBody|@nl2br}</div>
								{if $item.PaymentAdd}<div class="item-descr"><a href="/ext/MerchantSberbank/Request.php?action=form&id={$get.order.Id}">Ссылка на оплату</a></div>{/if}
								
							</div>
							{/foreach}
						</div>
						<label class="pps pps_area">
							<textarea id="addCommentValue" name="message" data-order="{$get.order.Id}"></textarea>
						</label>
						<label class="pps pps_checkbox">
							<input type="checkbox" value="1" id="addOrderPositionsToMessage"/>
							<span>прикрепить состав заказа</span>
						</label>
						{*
						<label class="pps pps_checkbox">
							<input type="checkbox" value="1" id="addPaymentLink"/>
							<span>прикрепить ссылку на оплату</span>
						</label>
						*}
					</div>
					<div class="dt pps_flex_23"></div>
					<div class="dd pps_flex_13 pps_center">
						<a class="list-item-add" href="" title="Добавить"><i class="fa fa-2x fa-plus"></i></a>
					</div>
				</div>
			</div>
			<div class="actions client pps_flex_12">
				<div class="title">Клиент</div>
				<div class="item pps_flex pps_flex_row pps_flex_start">
					<div class="dt pps_flex_13">Имя</div>
					<div class="dd pps_flex_23">
						{$get.order.Name}
					</div>
				</div>
				<div class="item pps_flex pps_flex_row pps_flex_start">
					<div class="dt pps_flex_13">Телефон</div>
					<div class="dd pps_flex_23">
						{$get.order.Phone}
					</div>
				</div>
				<div class="item pps_flex pps_flex_row pps_flex_start">
					<div class="dt pps_flex_13">E-mail</div>
					<div class="dd pps_flex_23">
						{$get.order.Email}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
var orderId = '{$get.order.Id}';
var orderSumm = '{$get.order.Summ}';
</script>

{$get.cssjs}