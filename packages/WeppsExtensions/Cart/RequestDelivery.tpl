<h2>Выберите способ доставки</h2>
{foreach name="out" item="item" from=$delivery}
	<label class="pps pps_radio">
		<input type="radio" name="delivery" value="{$item.Id}" data-city="{$get.cityId}" data-price="0" />
		<span>{$item.Name}</span>
	</label>
{/foreach}
{$get.cssjs}