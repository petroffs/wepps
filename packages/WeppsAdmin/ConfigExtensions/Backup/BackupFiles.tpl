<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('files','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php')"
	id="list-data-form">
	<input type="hidden" name="add" value="1"/>
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<label class="pps w_checkbox w_flex_11"><input type="checkbox" name="add-git" value="1"/> <span>Включить папку .git</span></label>
		<label class="pps w_button w_flex_11"><input type="submit" value="Создать бекап файлов"/></label>
	</div>
</form>
{if $backups}
<div class="w_interval_small"></div>
<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area">
	<div class="w_flex_23 w_flex_11_view_medium w_border">
		<div class="title">Последние бекапы</div>
		<ul class="w_list">
			{foreach name="out" item="item" from=$backups}
			<li class="w_pointer" data-file="{$item}" data-restore="files-restore" data-remove="files-remove"><i class="fa fa-file-o"></i> {$item}</li>
			{foreachelse}
			<li class=""><i class="fa fa-close"></i> Файлов нет.</li>
			{/foreach}
		</ul>
	</div>
</form>
{/if}

{*
<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top" 
	action="javascript:formWepps.send('files','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php')"
	id="list-data-form">
	<input type="hidden" name="add" value="1"/>
	<div class="w_flex_13 w_flex_12_view_medium w_flex_11_view_small w_flex w_flex_row w_flex_start w_flex_margin">
		<label class="pps w_checkbox w_flex_11"><input type="checkbox" name="add-git" value="1"/> <span>Включить папку .git</span></label>
		<label class="pps w_button w_flex_11"><input type="submit" value="Создать бекап файлов"/></label>
	</div>
	<div class="w_flex_13 w_flex_12_view_medium w_flex_11_view_small">
		<div class="title">Последние бекапы</div>
		<ul class="w_list">
			{foreach name="out" item="item" from=$backups}
			<li class="w_pointer" data-file="{$item}" data-restore="files-restore" data-remove="files-remove"><i class="fa fa-file-o"></i> {$item}</li>
			{foreachelse}
			<li class=""><i class="fa fa-close"></i> Файлов нет.</li>
			{/foreach}
		</ul>
	</div>
</form>
*}