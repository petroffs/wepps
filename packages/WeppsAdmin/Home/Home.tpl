<div class="way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li>Wepps</li>
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str">
	<div class="w_flex_11 w_flex w_flex_col centercontent">
		<div class="w_flex_max w_padding">
			<h2>{$content.Name}</h2>
			<div class="w_overflow_auto">
				<div class="lists-items w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin">
					{foreach name="out" item="item" key="key" from=$navhome}
					<div class="block-rounded w_flex_14 w_flex_12_view_medium w_flex_11_view_small" data-url="/_wepps/{$key}/">
						<div class="w_flex w_flex_col w_bg_silver w_height">
							<div class="w_flex_max w_padding">
								<div class="title">{$item.Name}</div>
							</div>
						</div>
					</div>
					{/foreach}
				</div>
			</div>
		</div>
	</div>
</div>
