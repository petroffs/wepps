{assign var="alias" value="tasks"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="pps w_button"><input type="submit" value="Выполнить задачи (tasks)"/></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Выполнить <a href="/_wepps/lists/s_Tasks/">задачи (log-tasks)</a>
		</div>
	</div>
</form>
<div class="w_interval"></div>
{assign var="alias" value="searchindex"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="pps w_button"><input type="submit" value="Построить индекс"/></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Заполнение списка <a href="/_wepps/lists/s_SearchKeys/">Индексы
				связей полей</a>
		</div>
	</div>
</form>
<div class="w_interval"></div>
{assign var="alias" value="removefiles"}
<form class="w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('{$alias}','form-{$alias}','{$url}')"	id="form-{$alias}">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<div class="w_flex_12 w_flex_11_view_medium">
			<label class="pps w_button"><input type="submit" value="Очистка файлов"/></label>
		</div>
		<div class="w_flex_12 w_flex_11_view_medium">
			Удаление файлов из папки /files/lists/* и /pic/* (только тех, которые не указаны в списке <a href="/_wepps/lists/s_Files/">Файлы</a>).</div>
	</div>
</form>