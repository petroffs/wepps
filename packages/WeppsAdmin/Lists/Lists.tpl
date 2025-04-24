<div class="way">
	<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/lists/">Списки данных</a></li>
	</ul>
</div>
<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_animate">
	{$listsNavTpl}
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col">
		<div class="pps_flex_max">
			<h2>{$content.Name}</h2>
			<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
				{foreach name="out" item="item" key="key" from=$lists}
				<div class="pps_flex_13 pps_flex_11_view_small">
					<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
						<div class="pps_flex_max pps_padding">
							<div class="title">{$translate.$key|default:$key}</div>
							<div class="descr"></div>
							{foreach name="o" item="i" key="k" from=$item}
							<div class="container pps_flex pps_flex_row pps_flex_row_str">
								<div class="pps_flex_23">
									<div class="container-title"><a href="/_wepps/lists/{$i.TableName}/">{$i.Name}</a></div>
									<div class="container-descr">{$i.TableName}</div>
								</div>
								<div class="pps_flex_13 pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
									<div class="container-descr pps_flex_12 pps_right">{$i.FieldsCount}</div>
									<div class="container-descr pps_flex_12 pps_right">{$i.RowsCount}</div>
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
