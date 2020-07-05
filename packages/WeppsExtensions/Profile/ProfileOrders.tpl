<div class="orders">
	<div
		class="items pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin">
		{foreach name="out" item="item" from=$orders}
		<div
			class="item pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_medium pps_bg_silver pps_shadow_inset">
			<div class="itemheader pps_flex pps_flex_row pps_flex_start pps_pointer" data-order="{$item.Id|number}">
				<div class="ordermore">{$item.OText}</div>
				<div class="number pps_flex_13 pps_padding"><span>№{$item.Id|number}</span> <div class="uk-badge">{$item.TStatus_Name}</div></div>
				<div class="date pps_flex_13  pps_padding pps_flex_23_view_medium"><span>{$item.ODate|date_format:'%d.%m.%Y'}</span></div>
				<div class="amount price pps_flex_13 pps_flex_11_view_medium pps_padding">
					<span>{$item.Summ|money}</span>&nbsp;<i class="uk-icon-rouble"></i>
				</div>
			</div>
			<div class="pps_padding">
				<div class="delivery">{$item.ODelivery_Name}</div>
				<div class="payment">{$item.OPayment_Name}</div>

				{if $item.IsCompany==1}
				<div class="">
					<a href="/pdf/Invoice/{$item.Id}.pdf">Счет на оплату</a>
				</div>
				{else}
				<div class="">
					<a href="/pdf/Receipt/{$item.Id}.pdf">Квитанция</a>
				</div>
				{/if}
				<div class="">
					<a href="/pdf/Order/{$item.Id}.pdf">Заказ (PDF)</a>
				</div>
			</div>
			
		</div>
		{/foreach}
	</div>
</div>