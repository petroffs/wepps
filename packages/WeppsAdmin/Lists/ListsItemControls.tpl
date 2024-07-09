<div class="controls-area">
	{if $element}
	{if $ppsUrl}
	{if !$ppsUrl|@strstr:"addNavigator"}
	<a class="pps_button list-item-add" href="/_pps/{$ppsPath}{$ppsUrl}addNavigator/" title="Добавить раздел"><i class="fa fa-plus"></i> Добавить</a>
	{/if}
	{else}
	<a class="pps_button list-item-add" href="/_pps/{$ppsPath}/{$listSettings.TableName}/add/" title="Добавить элемент"><i class="fa fa-plus"></i> Добавить</a>
	{/if}
	<a class="pps_button list-item-save" href="" title="Сохранить изменения"><i class="fa fa-save"></i> Сохранить</a>
	<a class="pps_button list-item-copy" href="" title="Копировать элемент"><i class="fa fa-copy"></i> Копировать</a>
	<a class="pps_button list-item-refresh" href="" title="Обновить страницу"><i class="fa fa-refresh"></i> Обновить</a>
	<a class="pps_button list-item-remove" href="" data-path="{$ppsPath}" title="Удалить"><i class="fa fa-remove"></i> Удалить</a>
	{/if}
	{if $language && $element.TableId|isset && $element.LanguageId|isset}
	<label class="pps pps_select list-item-language">
		<select name="list-item-language" data-minimum-results-for-search="Infinity">
			{foreach name="out" item="item" from=$language}
			<option value="{$item.Id}">{$item.Name}</option>
			{/foreach}
		</select>
	</label>
	{/if}
</div>