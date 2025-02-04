<div class="products-wrapper">
	<div class="options pps_flex pps_flex_row pps_flex_row_str">
		<div class="optionsCount">{$productsCount} товаров</div>
		<div class="optionsSort">
			<label class="pps pps_select"><select>
					{foreach name="out" key="key" item="item" from=$productsSorting}
					<option value="{$key}" {if $productsSortingActive==$key} selected="selected"{/if}>{$item}</option>
					{/foreach}
			</select> </label>
		</div>
	</div>
	<div class="products-container">
		<div
			class="items products pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
			{foreach name="out" item="item" from=$products} {assign var=images
			value=$item.Images_FileUrl|strarr}
			<div
				class="item pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small pps_border">
				{if $item.PStatus}
				<div class="status status{$item.PStatus}"
					title="{$item.PStatus_Name}"></div>
				{/if}

				<div class="item2 pps_height pps_flex pps_flex_col pps_border">
					<div class="img">
						<img
							src="/pic/catprev{$images.0|default:'/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg'}"
							class="pps_image" />
					</div>
					<div class="title">
							<a href="{$item.Url}">{$item.Name}</a>
						</div>
					<div class="descr">
						
						<div class="itemfooter pps_flex_12 pps_flex pps_flex_row">
							<div class="prices">
								<div class="price">{$item.Price|money}</div>
								{if $item.PriceOld}
								<div class="priceOld">{$item.PriceOld|money}</div>
								{/if}
							</div>
							<div class="action">
								<label class="pps pps_button"> <input type="button"
									class="addCart" value="В корзину" data-id="{$item.Id}" />
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			{/foreach}
		</div>
		{$paginatorTpl}
	</div>
</div>