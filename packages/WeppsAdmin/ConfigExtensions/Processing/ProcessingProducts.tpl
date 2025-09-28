{assign var="alias" value="resetproducts"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="pps w_button"><input type="submit" value="Очистить каталог" disabled="disabled"/></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Удалить раздел с товарами. Удаляется раздел, товар, изоображения, атрибуты. Раздел для удаления указывается в коде.
		</div>
	</div>
</form>