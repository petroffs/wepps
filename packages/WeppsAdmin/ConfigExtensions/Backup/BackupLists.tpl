<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area" 
	action="javascript:formWepps.send('list','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php')"
	id="list-data-form">
	<div class="w_flex_23 w_flex_11_view_medium w_flex w_flex_row w_flex_start w_border">
		<label class="pps w_select w_flex_12 listsBox"> <select id="lists">
				{foreach name="out" item="item" key="key" from=$lists}
				<optgroup label="{$translate.$key|default:$key}">
					{foreach name="o" item="i" key="k" from=$item}
					<option value="{$i.TableName}">{$i.Name} ({$i.TableName})</option>
					{/foreach}
				</optgroup> {/foreach}
		</select>
		</label>
		<label class="pps w_button"><input type="button"
			value="Структура" id="backupListStructure"/></label> <label class="pps w_button"><input
			type="button" value="Данные" id="backupListData"/></label>
	</div>
</form>