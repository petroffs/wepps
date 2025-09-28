<div class="way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/lists/">Списки данных</a></li>
		<li><a href="/_wepps/lists/{$listSettings.TableName}/">{$listSettings.Name}</a></li>
		<li><a href="/_wepps/lists/{$listSettings.TableName}/{$element.Id}/">{$element.Name}</a></li>
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str w_flex_margin w_animate">
	{$listsNavTpl}
	<div class="w_flex_45 w_flex_11_view_medium w_flex w_flex_col">
		{$listItemFormTpl}
	</div>
</div>