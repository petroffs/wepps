<form class="list-data pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formSenderWepps.send('database','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php')"
	id="list-data-form">
	<input type="hidden" name="action" value="database">
	<input type="hidden" name="add" value="1">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_start pps_border">
		<label class="pps pps_input"><input name="list" type="text" value="" placeholder="Название таблицы БД"/></label>
		<label class="pps pps_button"><input type="submit" value="Создать бекап"/></label>
	</div>
</form>
{if $backups}
<div class="pps_interval_small"></div>
<form class="list-data pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_border">
		<div class="title">Последние бекапы</div>
		<ul class="pps_list">
			{foreach name="out" item="item" from=$backups}
			<li class="pps_pointer" data-file="{$item}" data-restore="database-restore" data-remove="database-remove"><i class="fa fa-file-o"></i> {$item}</li>
			{foreachelse}
			<li class=""><i class="fa fa-close"></i> Файлов нет.</li>
			{/foreach}
		</ul>
	</div>
</form>
{/if}