<form class="list-data pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formSenderWepps.send('list','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php')"
	id="list-data-form">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_start pps_border">
		<label class="pps pps_select listsBox"> <select id="lists">
				{foreach name="out" item="item" key="key" from=$lists}
				<optgroup label="{$translate.$key|default:$key}">
					{foreach name="o" item="i" key="k" from=$item}
					<option value="{$i.TableName}">{$i.Name}</option>
					{/foreach}
				</optgroup> {/foreach}
		</select>
		</label>
		<label class="pps pps_button"><input type="button"
			value="Структура" id="backupListStructure"/></label> <label class="pps pps_button"><input
			type="button" value="Данные" id="backupListData"/></label>
	</div>
</form>