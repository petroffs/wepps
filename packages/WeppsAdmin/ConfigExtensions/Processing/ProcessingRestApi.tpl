<div class="w_interval"></div>
{assign var="alias" value="mappingtypes"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area"
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')" id="form-{$alias}">
	<div class="w_rounded w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="w_label w_button"><input type="submit" value="Выполнить маппинг типов" /></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Заполнение ApiFieldType в <a href="/_wepps/lists/s_ConfigFields/">s_ConfigFields</a>. <br />Маппинг типов БД
			на REST API типы: <br />
			int → int<br />
			flag → int<br />
			guid → guid<br />
			date → date<br />
			email → email<br />
			digit → float<br />
			остальные → string</div>
	</div>
</form>
<div class="w_interval"></div>
{assign var="alias" value="mappingnames"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area"
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')" id="form-{$alias}">
	<div class="w_rounded w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="w_label w_button"><input type="submit" value="Выполнить маппинг наименований" /></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Заполнение ApiMapping в <a href="/_wepps/lists/s_ConfigFields/">s_ConfigFields</a>. <br />
			Преобразует имена полей БД в camelCase формат для REST API: <br />
			Product_Name → productName<br />
			Order_Status → orderStatus<br />
			OStatus → status (удаляет однобуквенный префикс)
		</div>
</form>