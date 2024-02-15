<div class="controls-area pps_border">
	<div class="pps_flex pps_flex_row pps_flex_start pps_flex_row_str pps_flex_margin_large pps_padding">
		{if $element}
		{if $ppsUrl}
		{if !$ppsUrl|@strstr:"addNavigator"}
		<a class="list-item-add" href="/_pps/{$ppsPath}{$ppsUrl}addNavigator/" title="Добавить раздел"><i class="fa fa-2x fa-plus"></i></a>
		{/if}
		{else}
		<a class="list-item-add" href="/_pps/{$ppsPath}/{$listSettings.TableName}/add/" title="Добавить элемент"><i class="fa fa-2x fa-plus"></i></a>
		{/if}
		<a class="list-item-save" href="" title="Сохранить изменения"><i class="fa fa-2x fa-save"></i></a>
		<a class="list-item-copy" href="" title="Копировать элемент"><i class="fa fa-2x fa-copy"></i></a>
		<a class="list-item-refresh" href="" title="Обновить страницу"><i class="fa fa-2x fa-refresh"></i></a>
		<a class="list-item-remove" href="" data-path="{$ppsPath}" title="Удалить"><i class="fa fa-2x fa-remove"></i></a>
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
</div>