<label class="pps pps_input"><div>Доставка5050</div></label>
{foreach name="out" item="item" from=$get.delivery}
<label class="pps pps_radio"> <input type="radio" name="delivery"
	value="{$item.Id}" data-city="{$get.city}" data-price="0"/> <span>{$item.Name}</span>
</label>
{/foreach}

<script>
/* $('.delivery-payment').css('opacity',1);
$('input[name="delivery"]').on('change',function(event) {
	event.stopPropagation();
	layoutWepps.request('action=payment&city='+$(this).data('city')+'&delivery='+$(this).attr('value'), '/ext/Cart/Request.php',$('#payment'));
}); */
</script>
{$get.cssjs}
