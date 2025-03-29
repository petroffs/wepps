<div class="page products">
	<section class="content-wrapper w_grid w_4col w_gap_medium">
		<section class="sidebar w_hide_view_medium w_1scol" data-url="/ext/Products/Request.php" data-search="{$smarty.get.text|escape:'url'}">
			<div class="nav pps_animate w_4scol w_2scol_view_medium w_1scol_view_small">
				<ul>
					{foreach name="out" item="item" from=$childsNav}
					<li class="{if $content.Id==$item.Id}active{/if}">
						<a href="{$item.Url}">{$item.Name}</a>
					</li>
					{/foreach}
				</ul>		
			</div>
			{foreach name="out" item="item" key="key" from=$filtersNav}
			{assign var="hide" value=""}
			<div class="nav-filters nav-filters-{$key}" data-id='{$key}'>
				<div class="title">{$item.0.PropertyName}</div>
				<ul>
					{foreach name="o" item="i" from=$item}
					{if $smarty.foreach.o.iteration>10} {assign var="hide" value="pps_hide"}
					{/if}
					<li class="{$hide}">
						<label class="pps pps_checkbox">
							<input type="checkbox" name="{$i.Alias}" /> <span>{$i.PValue} <span>{$i.Co}</span></span>
						</label>
					</li>
					{/foreach}
					{if $hide!=""}
					<li class="pps_expand"><a href="">Еще</a></li>
					{/if}
				</ul>
			</div>
			{/foreach}
			<div class="nav-filters-reset">
				<div class="title">
					<label class="pps pps_button"><input type="button" value="Очистить"/></label>
				</div>
			</div>
			<div class="nav-filters-apply">
				<div class="title">
					<label class="pps pps_button"><input type="button" value="Применить"/></label>
				</div>
			</div>
		</section>
		<section class="content w_3scol w_4scol_view_medium">
			<div class="content-block">
				<h1>{$content.Name}</h1>
				{if $content.Text1}
				<div class="text text-top">{$content.Text1}</div>
				{/if}
			</div>
			<div class="products-wrapper">
				<div class="products-options content-block pps_flex pps_flex_row">
					<div id="pps-option-filters"><i class="bi bi-sliders"></i></div>
					<div id="pps-options-count">{$productsCount}</div>
					<div id="pps-options-sort">
						<label class="pps pps_select w_select">
							<select data-minimum-results-for-search="Infinity">
								{foreach name="out" key="key" item="item" from=$productsSorting}
								<option value="{$key}" {if $productsSortingActive==$key} selected="selected"{/if}>{$item}</option>
								{/foreach}
							</select>
						</label>
					</div>
				</div>
				<div id="pps-rows-wrapper">
				{$productsTpl}
				</div>
			</div>
		</section>
	</section>
</div>
{if $filtersJS}
<script>
$(document).ready(function() {
	{$filtersJS}
});
</script>
{/if}