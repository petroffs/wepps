<label class="pps pps_input"><div>Способ оплаты</div></label>
{foreach name="out" item="item" from=$get.payment}
<label class="pps pps_radio"> <input type="radio" name="payment"
	value="{$item.Id}" data-city="{$get.city}" data-price="0"/> <span>{$item.Name}</span>
</label>
{/foreach}

<script>
$('input[name="payment"]').on('change',function(event) {
	event.stopPropagation();
	layoutPPS.request('action=shipping&city='+$(this).data('city')+'&delivery={$get.deliveryChecked}&payment='+$(this).attr('value'), '/ext/Cart/Request.php');
});
{$get.jscode}
</script>