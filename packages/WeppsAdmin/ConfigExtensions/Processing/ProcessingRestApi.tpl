<div class="w_interval"></div>
{assign var="alias" value="mappingtypes"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area"
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')" id="form-{$alias}">
	<div class="w_rounded w_flex_23 w_flex_11_view_medium w_grid w_2col w_1col_view_medium w_gap_large w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="w_label w_button"><input type="submit" value="Выполнить маппинг типов" /></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Заполнение ApiFieldType в <a href="/_wepps/lists/s_ConfigFields/">s_ConfigFields</a>. <br /><br />Маппинг
			типов БД
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
	<div class="w_rounded w_flex_23 w_flex_11_view_medium w_grid w_2col w_1col_view_medium w_gap_large w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="w_label w_button"><input type="submit" value="Выполнить маппинг наименований" /></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Заполнение ApiMapping в <a href="/_wepps/lists/s_ConfigFields/">s_ConfigFields</a>. <br /><br />
			Преобразует имена полей БД в camelCase формат для REST API: <br />
			Product_Name → productName<br />
			Order_Status → orderStatus<br />
			OStatus → status (удаляет однобуквенный префикс)
		</div>
	</div>
</form>
<div class="w_interval"></div>
{assign var="alias" value="addtests"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area"
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')" id="form-{$alias}">
	<div class="w_rounded w_flex_23 w_flex_11_view_medium w_grid w_2col w_1col_view_medium w_gap_large w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="w_label w_select">
				<select name="source">
					<option value="">Выбрать GET-запрос (source)</option>
					{foreach from=$sourceFiles key=group item=files}
						<optgroup label="{$group}">
							{foreach from=$files key=label item=path}
								<option value="{$path}">{$label}</option>
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</label>
			<div class="w_interval"></div>
			<label class="w_label w_select">
				<select name="destination[]" multiple>
					<option value="">Выбрать тип тестового запроса (destination)</option>
					{foreach from=$destinationFiles key=group item=files}
						<optgroup label="{$group}">
							{foreach from=$files key=label item=path}
								<option value="{$path}">{$label}</option>
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</label>
			<div class="w_interval"></div>
			<label class="w_label w_input">
				<input type="text" name="m2m_token" placeholder="Токен M2M API (Bearer)" autocomplete="off"/>
			</label>
			<div class="w_interval"></div>
			<label class="w_label w_button"><input type="submit" value="Создать тесты" /></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Сгенерировать тестовые запросы для REST API<br /><br />
			Файлы сохранятся в {$projectDev.root}/.tools/bruno/WeppsPlatformV1/clientM2M/.tests/*
		</div>
	</div>
</form>