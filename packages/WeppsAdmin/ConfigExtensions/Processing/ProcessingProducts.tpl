{assign var="alias" value="resetproducts"}
<form class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_start pps_border">
		<div class="pps_flex_12 pps_flex_11_view_medium">
			<label class="pps pps_button"><input type="submit" value="Очистить каталог" disabled="disabled"/></label>
		</div>
		<div class="pps_flex_12 pps_flex_11_view_medium">
			Удалить раздел с товарами. Удаляется раздел, товар, изоображения, атрибуты. Раздел для удаления указывается в коде.
		</div>
	</div>
</form>