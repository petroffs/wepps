<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('database','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php')"
	id="list-data-form">
	<input type="hidden" name="action" value="database">
	<input type="hidden" name="add" value="1">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<label class="pps w_input"><input name="list" type="text" value="" placeholder="Название таблицы БД"/></label>
		<label class="pps w_input"><input name="comment" type="text" value="" placeholder="Комментарий"/></label>
		<label class="pps w_button"><input type="submit" value="Создать бекап"/></label>
	</div>
</form>
{if $backups}
<div class="w_interval_small"></div>
<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area">
	<div class="w_flex_23 w_flex_11_view_medium w_border">
		<div class="title">Последние бекапы</div>
		<div class="w_interval_small"></div>
		<ul class="w_list">
			{foreach name="out" item="item" from=$backups}
			<li class="w_pointer" data-file="{$item}" data-restore="database-restore" data-remove="database-remove"><i class="fa fa-file-o"></i> {$item}</li>
			{foreachelse}
			<li class=""><i class="fa fa-close"></i> Файлов нет.</li>
			{/foreach}
		</ul>
	</div>
</form>
{/if}