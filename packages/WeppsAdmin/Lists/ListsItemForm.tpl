<div class="lists-items-item pps_border pps_flex_max pps_padding">
	<h2>{$content.Name}</h2>
	<form class="list-data" action="javascript:formWepps.send('save','list-data-form','/packages/WeppsAdmin/Lists/Request.php')"
		id="list-data-form">
		<input type="hidden" name="pps_tablename" value="{$listSettings.TableName}"/>
		<input type="hidden" name="pps_tablename_id" value="{$element.Id|default:'add'}"/>
		<input type="hidden" name="pps_tablename_mode" value="{$listMode}"/>
		<input type="hidden" name="pps_path" value="{$ppsPath}"/>
		{foreach name="out" item="item" key="key" from=$listScheme}
		{if $item.0.$listMode=="hidden" || $item.0.$listMode=="disabled"}
		<input type="hidden" name="{$key}" value="{$element.$key|escape:'html'}"/>
		{/if}
		{/foreach}
		<div class="pps_hide"><input type="submit"/></div>
		{$controlsTpl}
		<div class="pps_interval"></div>
		{if $tabs|@count>1 || $listSettings.ActionShowIdAddons}
		<div class="controls-area controls-tabs pps_border">
			<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large pps_padding">
				<a href="" data-id="FieldAll"><i class="fa fa-caret-down"></i> Все</a>
				{foreach name="out" item="item" key="key" from=$tabs}
				<a href="" data-id="{$key}"><i class="fa fa-caret-right"></i> {$translate.$item|default:$item}</a>
				{/foreach}
				{foreach name="out" item="item" key="key" from=$listSettings.ActionShowIdAddons}
				<a href="" data-id="{$item.group}"><i class="fa fa-caret-right"></i> {$item.title}</a>
				{/foreach}
			</div>	
		</div>
		<div class="pps_interval"></div>
		{/if}
		{foreach name="out" item="item" key="key" from=$listScheme}
		{if $item.0.$listMode!="hidden"}
		<div class="item pps_flex pps_flex_row pps_flex_start pps_flex_row_str" data-id="{$key}" data-group="{$item.0.FGroup}">
			<div class="title pps_flex_13">
				<div class="title2">
				{if $permFields==1}
				<a href="/_pps/lists/s_ConfigFields/{$item.0.Id}/">{$item.0.Name}</a>
				{else}
				{$item.0.Name}
				{/if}
				{if $element[$key|cat:"_Table"]}
				<a href="/_pps/lists/{$element[$key|cat:"_Table"]}/" target="_blank"><i class="fa fa-external-link"></i></a>
				{/if}
				</div>
				<div class="descr pps_flex_23">
					{if $permFields==1}
					<div>{$key}</div>
					{/if}
					{if $item.0.Description}<div>{$item.0.Description}</div>{/if}
					{if $key=="Alias"}
					<div><a href="" class="field-translit">траслит</a></div>
					{elseif $item.0.Type=="area"}
					<div><a href="" class="field-ve">визуальный редактор</a></div>
					{elseif $item.0.Type=='file'}
					<div class="field-file">
						<a href="" class="field-file-select" data-status="0">выделить</a>
						<a href="" class="field-file-action field-file-edit pps_hide"><i class="fa fa-edit"></i></a>
						<a href="" class="field-file-action field-file-remove pps_hide"><i class="fa fa-remove"></i></a>
					</div>
					{/if}
				</div>
			</div>
			<div class="field pps_flex_23">
				{if $item.0.$listMode=="disabled"}
				{$element.$key}
				{elseif $item.0.Type=="digit"}
				<label class="pps pps_input list-item-int">
					<input type="text" name="{$key}" value="{$element.$key}"/>
				</label>
				{elseif $item.0.Type=="int"}
				<label class="pps pps_input list-item-int">
					<input type="number" name="{$key}" value="{$element.$key}"/>
				</label>
				{elseif $item.0.Type=="date"}
				<label class="pps pps_input list-item-date">
					<input type="datetime" name="{$key}" value="{$element.$key|escape:'html'}"/>
				</label>
				{elseif $item.0.Type=="password"}
				<label class="pps pps_input">
					<input type="text" name="{$key}" value="{$element.$key|escape:'html'}"/>
				</label>
				{elseif $item.0.Type=="flag"}
				<label class="pps pps_checkbox">
					<input type="checkbox" name="{$key}" value="1"{if $element.$key==1} checked="true"{/if}/> <span>{$item.0.Name}</span>
				</label>
				{elseif $item.0.Type=="area"}
				<label class="pps pps_area">
					<textarea name="{$key}" id="formArea{$key}">{$element.$key}</textarea>
				</label>
				{elseif $item.0.Type=="blob"}
				<label class="pps pps_area">
					<textarea name="{$key}">{$element.$key}</textarea>
				</label>
				{elseif $item.0.Type=="date"}
				<label class="pps pps_input list-item-date">
					<input type="datetime" name="{$k}" value="{$element.$key}"/>
				</label>
				{elseif $item.0.Type=="file"}
				{if $element.$key.0}
				<div class="files pps_animate_none pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_small">
					{foreach name="o" item="i" key="k" from=$element.$key}
					<div class="files-item files-item-2 pps_flex_16 pps_flex_13_view_medium pps_flex_12_view_medium" data-title="{$i.Name}" data-id="{$i.Id}">
						<div>
						{if $i.Name|@stristr:"jpg" || $i.Name|@stristr:"jpeg" || $i.Name|@stristr:"png"}
						<a href="/f{$i.FileUrl}" class="files-upload"><img src="/pic/lists{$i.FileUrl}" class="pps_image"/></a>
						{else}
						<a href="/f{$i.FileUrl}" class="files-upload">{$i.Name}</a>
						{/if}
						</div>
						<div class="descr{if $i.FileDescription} descr-fill{/if}">
							<div class="input">
								<label class="pps pps_input">
									<input type="text" value="/pic/full{$i.FileUrl}"/>
								</label>
							</div>
							<div>
								{$i.FileDescription}
							</div>
						</div>
						<div>
							<div class="files-controls pps_flex pps_flex_row">
								<a href="" class="files-item-copy-link"><i class="fa fa-copy"></i></a>
								<a href="/_pps/lists/s_Files/{$i.Id}/"><i class="fa fa-edit"></i></a>
								<a href="" class="files-item-remove-link"><i class="fa fa-remove"></i></a>
							</div>
						</div>
					</div>
					{/foreach}
				</div>
				{/if}
				<label class="pps pps_upload">
					<input type="file" name="{$key}" multiple="multiple"/> <span>Загрузить</span>
				</label>
				<div class="pps_interval"></div>
				{foreach name="o" item="i" key="k" from=$uploaded[$key]}
					<p class="fileadd pps_flex_13">
						{$i.title} <a href="" class="file-remove" rel="{$i.url}"><i class="fa fa-remove"></i></a>
					</p>
				{/foreach}
				{elseif $item.0.Type|strstr:"select_multi" || $item.0.Type|strstr:"dbtable_multi"}
				{assign var="optionsCounter" value=$element[$key|cat:"_SelectOptionsSizeView"]}
				<label class="pps pps_select pps_select_multi">
					<select name="{$key}[]" multiple="multiple" size="{$optionsCounter}">
					{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"] multiple="multiple"}
					</select>
				</label>
				{elseif $item.0.Type|strstr:"select" || $item.0.Type|strstr:"dbtable"}
				<label class="pps pps_select">
					<select name="{$key}">
					{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"]}
					</select>
				</label>
				{elseif $item.0.Type|strstr:"remote"}
				{if $item.0.Type|strstr:"remote_multi"}
				<label class="pps pps_select">
					<select name="{$key}[]" id="remote_{$key}" multiple="multiple">
					{foreach name="o" item="i" key="k" from=$element[$key|cat:"_SelectChecked"]}
					<option value="{$k}" selected="selected">{$i}</option>
					{/foreach}
					</select>
				</label>
				{else}
				<label class="pps pps_select">
					<select name="{$key}" id="remote_{$key}">
					{foreach name="o" item="i" key="k" from=$element[$key|cat:"_SelectChecked"]}
					<option value="{$k}" selected="selected">{$i}</option>
					{/foreach}
					</select>
				</label>
				{/if}
				<script>
				$(document).ready(function() { getSelectRemote({ id:"#remote_{$key}",url:"/rest/v1.0/getList/{$item.0.TableName}/{$item.0.Id}/" })});
				</script>
				{elseif $item.0.Type|strstr:"minitable"}
				<div class="minitable" data-field="{$key}">
					<div class="minitable-headers minitable-row pps_flex pps_flex_row pps_flex_start">
						{foreach name="o" item="i" from=$element[$key|cat:"_Headers"]}
						<div class="minitable-cell pps_flex_16 pps_flex_14_view_small">{$i}</div>
						{/foreach}
						<div class="minitable-cell pps_flex_fix">
							<a class="minitable-add" href="" title="Добавить"><i class="fa fa-plus"></i></a>
						</div>
					</div>
					<div class="minitable-body-tpl minitable-row pps_flex pps_flex_row pps_flex_row_str pps_flex_start">
						{foreach name="o" item="i" from=$element[$key|cat:"_Headers"]}
						<div class="minitable-cell pps_flex_16 pps_flex_14_view_small" contenteditable="true"></div>
						{/foreach}
						<div class="minitable-min minitable-cell pps_flex_fix">
							<a class="minitable-remove" href="" title="Удалить"><i class="fa fa-remove"></i></a>
						</div>
					</div>
					{if $element[$key|cat:"_Rows"]}
					{foreach name="o1" item="i1" from=$element[$key|cat:"_Rows"]}
					<div class="minitable-body minitable-row pps_flex pps_flex_row pps_flex_row_str pps_flex_start">
						{foreach name="o" key="k" item="i" from=$element[$key|cat:"_Headers"]}
						<div class="minitable-cell pps_flex_16 pps_flex_14_view_small" contenteditable="true">{$i1.$k}</div>
						{/foreach}
						<div class="minitable-min minitable-cell pps_flex_fix">
							<a class="minitable-remove" href="" title="Удалить"><i class="fa fa-remove"></i></a>
						</div>
					</div>
					{/foreach}
					{/if}
				</div>
				<div class="pps_hide">
					<div class="pps_interval"></div>
					<label class="pps pps_area">
						<textarea name="{$key}" id="formArea{$key}">{$element.$key}</textarea>
					</label>
				</div>
				{elseif $item.0.Type|strstr:"properties"}
				<label class="pps pps_select list-item-properties">
					<select name="{$key}">
					{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"]}
					</select>
				</label>
				<div class="properties pps_shadow">
					{foreach name="o" item="i" key="k" from=$element[$key|cat:"_Properties"]}
					<div>
						<div class="title2">
							{$i.Name}{if $i.PExt} <span>{$i.PExt}</span>{/if} <a href="/_pps/lists/s_Properties/{$i.Id}/" target="_blank"><i class="fa fa-external-link"></i></a>
						</div>
						{if $i.PDescr}
						<div class="descr2">{$i.PDescr}</div>
						{/if}
						<div class="labels2">
							{if $i.PType=='select'}
							<label class="pps pps_select pps_select_multi">
								<select multiple="multiple" name="pps_property_{$key}_{$i.Id}[]">
								<option>-</option>
								{html_options options=$element[$key|cat:"_PropertiesOptions"][$i.Id] selected=$element[$key|cat:"_PropertiesSelected"][$i.Id] multiple="multiple"}
								</select>
							</label>
							<div class="pps_interval"></div>
							<div class="pps_flex pps_flex_row pps_flex_end pps_flex_margin">
							<label class="pps pps_input pps_flex_12">
								<input type="text" placeholder="новая опция" data-id="{$i.Id}"/> 
							</label>
							<div class="pps_flex_fix"><a href="" class="properties-item-option-add"><i class="fa fa-plus"></i></a></div>
							</div>
							{elseif $i.PType=='text-multi'}
							<label class="pps pps_area">
								<textarea name="pps_property_{$key}_{$i.Id}">{$element[$key|cat:"_PropertiesSelected"][$i.Id]}</textarea>
							</label>
							{/if}
						</div>
					</div>
					{/foreach}
				</div>
				{else}
				<label class="pps pps_input{if $item.0.Required==1} pps_require{/if} list-item-text">
					<input type="text" name="{$key}" value="{$element.$key|escape:'html'}"/>
				</label>
				{/if}
			</div>
		</div>
		{/if}
		{/foreach}
		{foreach name="out" item="item" key="key" from=$listSettings.ActionShowIdAddons}
		{$item.tpl}
		{/foreach}
		<div class="pps_interval"></div>
		{$controlsTpl}
	</form>
</div>