<div class="page products">
	<section>
		<section class="pps_flex pps_flex_row pps_flex_row_top pps_flex_margin">
			<section class="sidebar pps_flex_14">
				<div class="nav pps_animate">
					<ul>
						{foreach name="out" item="item" from=$extensionNav}
						<li class="{if $content.Id==$item.Id}active{/if}">
							<a href="{$item.Url}">{$item.Name}</a>
						</li>
						{/foreach}
					</ul>		
				</div>
				{foreach name="out" item="item" key="key" from=$filtersNav}
				{assign var="hide" value=""}
				<div class="nav-filters" data-id='{$key}'>
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
			</section>
			<section class="content pps_flex_34">
				<div class="content-block">
					<h1>{$content.Name}</h1>
					{if $content.Text1}
					<div class="text">{$content.Text1}</div>
					{/if}
				</div>
				<div class="products-wrapper">
					<div class="products-options content-block pps_flex pps_flex_row">
						<div class="count">{$productsCount} товаров</div>
						<div class="sort">
							<label class="pps pps_select">
								<select data-minimum-results-for-search="Infinity">
									{foreach name="out" key="key" item="item" from=$productsSorting}
									<option value="{$key}" {if $productsSortingActive==$key} selected="selected"{/if}>{$item}</option>
									{/foreach}
								</select>
							</label>
						</div>
					</div>
					{$productsTpl}
				</div>
			</section>
		</section>
	</section>
</div>