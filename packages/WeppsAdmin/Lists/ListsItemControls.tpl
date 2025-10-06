<div class="controls-area">
	{if $element}
	<a class="w_button list-item-save" href="" title="Сохранить изменения"><i class="fa fa-save"></i> Сохранить</a>
		{if $element.Id}
		<a class="w_button list-item-copy" href="" title="Копировать элемент"><i class="fa fa-copy"></i> Копировать</a>
		<a class="w_button list-item-refresh" href="" title="Обновить страницу"><i class="fa fa-refresh"></i> Обновить</a>
		{/if}
	
		{if $ppsUrl}
			{if !$ppsUrl|@strstr:"addNavigator"}
				<a class="w_button list-item-add" href="/_wepps/{$weppspath}{$ppsUrl}addNavigator/" title="Добавить раздел"><i class="fa fa-plus"></i> Добавить</a>
				<a class="w_button list-item-remove" href="" data-path="{$weppspath}" title="Удалить"><i class="fa fa-remove"></i> Удалить</a>
			{/if}
		{elseif $element.Id}
			<a class="w_button list-item-add" href="/_wepps/{$weppspath}/{$listSettings.TableName}/add/" title="Добавить элемент"><i class="fa fa-plus"></i> Добавить</a>
			<a class="w_button list-item-remove" href="" data-path="{$weppspath}" title="Удалить"><i class="fa fa-remove"></i> Удалить</a>
		{/if}
	{/if}
	{if $language && $element.TableId|isset && $element.LanguageId|isset}
		<label class="w_label w_select list-item-language">
			<select name="list-item-language" data-minimum-results-for-search="Infinity">
				{foreach name="out" item="item" from=$language}
				<option value="{$item.Id}">{$item.Name}</option>
				{/foreach}
			</select>
		</label>
	{/if}
</div>