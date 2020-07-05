
<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_row_str pps_animate">
	<div class="pps_flex_11 way">
		<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
			<li><a href="/_pps/">Главная</a></li>
			<li><a href="/_pps/lists/">Списки данных</a></li>
			<li><a href="/_pps/lists/{$listSettings.TableName}/">{$listSettings.Name}</a></li>
			<li><a href="/_pps/lists/{$listSettings.TableName}/{$element.Id}/">{$element.Name}</a></li>
		</ul>
	</div>
	{$listsNavTpl}
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col pps_padding centercontent">
		{$listItemFormTpl}
	</div>
</div>