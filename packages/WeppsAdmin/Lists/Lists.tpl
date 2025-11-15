<div class="way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/lists/">Списки данных</a></li>
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str w_flex_margin w_animate">
	{$listsNavTpl}
	<div class="w_flex_45 w_flex_11_view_medium w_flex w_flex_col">
		<div class="w_flex_max">
			<h2>{$content.Name}</h2>
			<div class="lists-items w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin">
				{foreach name="out" item="item" key="key" from=$lists}
				<div class="w_rounded w_flex_13 w_flex_11_view_small">
					<div class="w_flex w_flex_col w_bg_silver w_height">
						<div class="w_flex_max w_padding">
							<div class="title">{$translate.$key|default:$key}</div>
							<div class="descr"></div>
							{foreach name="o" item="i" key="k" from=$item}
							<div class="container w_flex w_flex_row w_flex_row_str">
								<div class="w_flex_11">
									<div class="container-title"><a href="/_wepps/lists/{$i.TableName}/">{$i.Name}</a></div>
									<div class="container-descr">{$i.TableName}</div>
								</div>
							</div>
							{/foreach}
							
						</div>
					</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>
