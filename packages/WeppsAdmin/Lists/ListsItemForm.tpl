<div class="lists-items-item w_flex_max">
	<h2>{$content.Name}</h2>
	<form class="list-data"
		action="javascript:formWepps.send('save','list-data-form','/packages/WeppsAdmin/Lists/Request.php')"
		id="list-data-form">
		<input type="hidden" name="w_tablename" value="{$listSettings.TableName}" />
		<input type="hidden" name="w_tablename_id" value="{$element.Id|default:'add'}" />
		<input type="hidden" name="w_tablename_mode" value="{$listMode}" />
		<input type="hidden" name="w_path" value="{$weppspath}" />
		{foreach name="out" item="item" key="key" from=$listScheme}
			{if ($item.0.$listMode=="hidden" || $item.0.$listMode=="disabled") && !$item.0.Type|strstr:'minitable'}
				<input type="hidden" name="{$key}" value="{$element.$key|escape:'html'}" />
			{/if}
		{/foreach}
		<div class="w_hide"><input type="submit" /></div>
		{$controlsTpl}
		{if $tabs|@count>1 || $listSettings.ActionShowIdAddons}
			<div class="controls-area controls-tabs">
				<a href="" class="w_button" data-id="FieldAll"><i class="bi bi-caret-down"></i> Все</a>
				{foreach name="out" item="item" key="key" from=$tabs}
					<a href="" class="w_button" data-id="{$key}"><i class="bi bi-caret-right"></i>
						{$translate.$item|default:$item}</a>
				{/foreach}
				{foreach name="out" item="item" key="key" from=$listSettings.ActionShowIdAddons}
					<a href="" class="w_button" data-id="{$item.group}"><i class="bi bi-caret-right"></i> {$item.title}</a>
				{/foreach}
			</div>
		{/if}
		<div class="item-before"></div>
		{foreach name="out" item="item" key="key" from=$listScheme}
			{if $item.0.$listMode!="hidden"}
				<div class="item w_flex w_flex_row w_flex_start w_flex_row_str" data-id="{$key}"
					data-group="{$item.0.FGroup}">
					<div class="title w_flex_13">
						<div class="title2">
							{if $permFields==1}
								<a href="/_wepps/lists/s_ConfigFields/{$item.0.Id}/">{$item.0.Name}</a>
							{else}
								{$item.0.Name}
							{/if}
							{if $element[$key|cat:"_Table"]}
								<a href="/_wepps/lists/{$element[$key|cat:"_Table"]}/" target="_blank">
									<i class="bi bi-box-arrow-up-right"></i></a>
							{/if}
						</div>
						<div class="descr w_flex_23">
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
									<a href="" class="field-file-action field-file-edit w_hide"><i class="bi bi-pencil"></i></a>
									<a href="" class="field-file-action field-file-remove w_hide"><i class="bi bi-x-circle"></i></a>
								</div>
							{/if}
						</div>
					</div>
					<div class="field w_flex_23">
						{if $item.0.$listMode=="disabled" && $item.0.Type!='properties' && !$item.0.Type|strstr:'minitable'}
							{$element.$key}
						{elseif $item.0.Type=="digit"}
							<label class="w_label w_input list-item-int{if $item.0.Required==1} w_require{/if}">
								<input type="text" name="{$key}" value="{$element.$key}" />
							</label>
						{elseif $item.0.Type=="int"}
							<label class="w_label w_input list-item-int{if $item.0.Required==1} w_require{/if}">
								<input type="number" name="{$key}" value="{$element.$key}" />
							</label>
						{elseif $item.0.Type=="date"}
							<label class="w_label w_input list-item-date{if $item.0.Required==1} w_require{/if}">
								<input type="datetime" name="{$key}" value="{$element.$key|escape:'html'}" />
							</label>
						{elseif $item.0.Type=="password"}
							<label class="w_label w_input{if $item.0.Required==1} w_require{/if}">
								<input type="text" name="{$key}" value="{$element.$key|escape:'html'}" />
							</label>
						{elseif $item.0.Type=="flag"}
							<label class="w_label w_checkbox">
								<input type="checkbox" name="{$key}" value="1" {if $element.$key==1} checked="true" {/if} />
								<span>{$item.0.Name}</span>
							</label>
						{elseif $item.0.Type=="area"}
							<label class="w_label w_area{if $item.0.Required==1} w_require{/if}">
								<textarea name="{$key}" id="formArea{$key}">{$element.$key}</textarea>
							</label>
						{elseif $item.0.Type=="blob"}
							<label class="w_label w_area">
								<textarea name="{$key}">{$element.$key}</textarea>
							</label>
						{elseif $item.0.Type=="date"}
							<label class="w_label w_input list-item-date">
								<input type="datetime" name="{$k}" value="{$element.$key}" />
							</label>
						{elseif $item.0.Type=="file"}
							{if $element.$key.0}
								<div
									class="files w_animate_none w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin_small">
									{foreach name="o" item="i" key="k" from=$element.$key}
										<div class="files-item files-item-2 w_flex_16 w_flex_13_view_medium w_flex_12_view_medium"
											data-title="{$i.Name}" data-id="{$i.Id}">
											<div>
												{if $i.Name|@stristr:"jpg" || $i.Name|@stristr:"jpeg" || $i.Name|@stristr:"png"}
													<a href="/f{$i.FileUrl}" class="files-upload"><img src="/pic/lists{$i.FileUrl}"
															class="w_image" /></a>
												{else}
													<a href="/f{$i.FileUrl}" class="files-upload">{$i.Name}</a>
												{/if}
											</div>
											<div class="descr{if $i.FileDescription} descr-fill{/if}">
												<div class="input">
													<label class="w_label w_input">
														<input type="text" value="/pic/full{$i.FileUrl}" />
													</label>
												</div>
												<div>
													{$i.FileDescription}
												</div>
											</div>
											<div>
												<div class="files-controls w_flex w_flex_row">
													<a href="" class="files-item-copy-link"><i class="bi bi-clipboard"></i></a>
													<a href="/_wepps/lists/s_Files/{$i.Id}/"><i class="bi bi-pencil"></i></a>
													<a href="" class="files-item-remove-link"><i class="bi bi-x-circle"></i></a>
												</div>
											</div>
										</div>
									{/foreach}
								</div>
							{/if}
							<label class="w_label w_upload">
								<input type="file" name="{$key}" multiple="multiple" /> <span><i class="bi bi-cloud-download"></i>
									Загрузить</span>
							</label>
							{foreach name="o" item="i" key="k" from=$uploaded[$key]}
								<p class="fileadd w_flex_13">
									{$i.title} <a href="" class="file-remove" rel="{$i.url}"><i class="bi bi-x-circle"></i></a>
								</p>
							{/foreach}
						{elseif $item.0.Type|strstr:"select_multi" || $item.0.Type|strstr:"dbtable_multi"}
							{assign var="optionsCounter" value=$element[$key|cat:"_SelectOptionsSizeView"]}
							<label class="w_label w_select w_select_multi">
								<select name="{$key}[]" multiple="multiple" size="{$optionsCounter}">
									{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"] multiple="multiple"}
								</select>
							</label>
						{elseif $item.0.Type|strstr:"select" || $item.0.Type|strstr:"dbtable"}
							<label class="w_label w_select">
								<select name="{$key}">
									{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"]}
								</select>
							</label>
						{elseif $item.0.Type|strstr:"remote"}
							{if $item.0.Type|strstr:"remote_multi"}
								<label class="w_label w_select{if $item.0.Required==1} w_require{/if}">
									<select name="{$key}[]" id="remote_{$key}" multiple="multiple">
										{foreach name="o" item="i" key="k" from=$element[$key|cat:"_SelectChecked"]}
											<option value="{$k}" selected="selected">{$i}</option>
										{/foreach}
									</select>
								</label>
							{else}
								<label class="w_label w_select">
									<select name="{$key}" id="remote_{$key}">
										{foreach name="o" item="i" key="k" from=$element[$key|cat:"_SelectChecked"]}
											<option value="{$k}" selected="selected">{$i}</option>
										{/foreach}
									</select>
								</label>
							{/if}
							<script>
								$(document).ready(function() {
									getSelectRemote({ id:"#remote_{$key}",url:"/rest/wepps/list_items?list={$item.0.TableName}&field={$item.0.Id}",token: "{$smarty.cookies.wepps_token}" });
								});
							</script>
						{elseif $item.0.Type|strstr:"minitable"}
							<div class="minitable {if $item.0.$listMode=='disabled'}minitable-disabled{else}minitable-active{/if}"
								data-field="{$key}">
								<div class="minitable-headers minitable-row w_flex w_flex_row w_flex_start">
									{foreach name="o" item="i" from=$element[$key|cat:"_Headers"]}
										<div class="minitable-cell w_flex_16 w_flex_14_view_small">{$i}</div>
									{/foreach}
									<div class="minitable-cell w_flex_fix">
										<a class="minitable-add" href="" title="Добавить"><i class="bi bi-plus-lg"></i></a>
									</div>
								</div>
								<div class="minitable-body-tpl minitable-row w_flex w_flex_row w_flex_row_str w_flex_start">
									{foreach name="o" item="i" from=$element[$key|cat:"_Headers"]}
										<div class="minitable-cell w_flex_16 w_flex_14_view_small" contenteditable="true"></div>
									{/foreach}
									<div class="minitable-min minitable-cell w_flex_fix">
										<a class="minitable-remove" href="" title="Удалить"><i class="bi bi-x-circle"></i></a>
									</div>
								</div>
								{if $element[$key|cat:"_Rows"]}
									{foreach name="o1" item="i1" from=$element[$key|cat:"_Rows"]}
										<div class="minitable-body minitable-row w_flex w_flex_row w_flex_row_str w_flex_start">
											{foreach name="o" key="k" item="i" from=$element[$key|cat:"_Headers"]}
												<div class="minitable-cell w_flex_16 w_flex_14_view_small"
													{if $item.0.$listMode!='disabled'} contenteditable="true" {/if}>{$i1.$k}</div>
											{/foreach}
											<div class="minitable-min minitable-cell w_flex_fix">
												<a class="minitable-remove" href="" title="Удалить"><i class="bi bi-x-circle"></i></a>
											</div>
										</div>
									{/foreach}
								{/if}
							</div>
							{if $item.0.$listMode!='disabled'}
								<div class="w_hide">
									<div class="w_interval"></div>
									<label class="w_label w_area">
										<textarea name="{$key}" id="formArea{$key}">{$element.$key}</textarea>
									</label>
								</div>
							{/if}
						{elseif $item.0.Type|strstr:"properties"}
							<label class="w_label w_select list-item-properties{if $item.0.Required==1} w_require{/if}">
								<select name="{$key}" {if $item.0.$listMode=="disabled"} disabled="disabled" {/if}>
									{html_options name=$colname options=$element[$key|cat:"_SelectOptions"] selected=$element[$key|cat:"_SelectChecked"]}
								</select>
							</label>
							<div class="properties w_border">
								{foreach name="o" item="i" key="k" from=$element[$key|cat:"_Properties"]}
									<div>
										<div class="title2">
											{$i.Name}{if $i.PExt} <span>{$i.PExt}</span>{/if} <a
												href="/_wepps/lists/s_Properties/{$i.Id}/" target="_blank"><i
													class="bi bi-box-arrow-up-right"></i></a>
										</div>
										{if $i.PDescr}
											<div class="descr2">{$i.PDescr}</div>
										{/if}
										<div class="labels2">
											{if $i.PType=='select'}
												<label class="w_label w_select w_select_multi{if $item.0.Required==1} w_require{/if}">
													<select multiple="multiple" name="w_property_{$key}_{$i.Id}[]"
														{if $item.0.$listMode=="disabled"} disabled="disabled" {/if}>
														<option>-</option>
														{html_options options=$element[$key|cat:"_PropertiesOptions"][$i.Id] selected=$element[$key|cat:"_PropertiesSelected"][$i.Id] multiple="multiple"}
													</select>
												</label>
												<div class="w_interval"></div>
												{if $item.0.$listMode!="disabled"}
													<div class="w_flex w_flex_row w_flex_end w_flex_margin_small">
														<label class="w_label w_input w_flex_12">
															<input type="text" placeholder="новая опция" data-id="{$i.Id}" />
														</label>
														<div class="w_flex_fix"><a href="" class="w_button properties-item-option-add"><i
																	class="bi bi-plus-lg"></i></a></div>
													</div>
												{/if}
											{elseif $i.PType=='text-multi'}
												<label class="w_label w_area">
													<textarea name="w_property_{$key}_{$i.Id}" {if $item.0.$listMode=="disabled"}
															disabled="disabled"
														{/if}>{$element[$key|cat:"_PropertiesSelected"][$i.Id]}</textarea>
												</label>
											{/if}
										</div>
									</div>
								{/foreach}
							</div>
						{else}
							<label class="w_label w_input{if $item.0.Required==1} w_require{/if} list-item-text">
								<input type="text" name="{$key}" value="{$element.$key|escape:'html'}" />
							</label>
						{/if}
					</div>
				</div>
			{/if}
		{/foreach}
		{foreach name="out" item="item" key="key" from=$listSettings.ActionShowIdAddons}
			{$item.tpl}
		{/foreach}
		<div class="w_interval"></div>
		{$controlsTpl}
	</form>
</div>