<h2>Выберите способ оплаты</h2>
{foreach name="out" item="item" from=$payments}
	<label class="pps pps_radio">
		<input type="radio" name="payments" value="{$item.Id}" data-price="0" />
		<span>{$item.Name}</span>
	</label>
{/foreach}
<script>
	cartPayments();
</script>
{$get.cssjs}