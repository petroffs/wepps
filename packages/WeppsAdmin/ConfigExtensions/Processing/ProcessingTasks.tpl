{assign var="alias" value="searchindex"}
<form class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_start pps_border">
		<div class="pps_flex_12 pps_flex_11_view_medium">
			<label class="pps pps_button"><input type="submit" value="Построить индекс"/></label>
		</div>
		<div class="pps_flex_12 pps_flex_11_view_medium">
			Заполнение списка <a href="/_pps/lists/s_SearchKeys/">Индексы
				связей полей</a>
		</div>
	</div>
</form>
<div class="pps_interval"></div>
{assign var="alias" value="removefiles"}
<form class="pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_start pps_border">
		<div class="pps_flex_12 pps_flex_11_view_medium">
			<label class="pps pps_button"><input type="submit" value="Очистка файлов"/></label>
		</div>
		<div class="pps_flex_12 pps_flex_11_view_medium">
			Удаление файлов из папки /files/lists/* и /pic/* (только тех, которые не указаны в списке <a href="/_pps/lists/s_Files/">Файлы</a>).</div>
	</div>
</form>