{assign var=images value=$element.Images_FileUrl|strarr}
<div class="product-container">
	<div
		class="summary pps_flex pps_flex_row pps_flex_str pps_flex_row_top pps_flex_margin">
		<div class="fotos pps_flex_12 pps_flex_11_view_medium">
			<div class="fotos-container">
				{foreach name="out" item="item" from=$images}
				<div class="item">
					<img src="/pic/catbig{$item}" class="pps_image" />
				</div>
				{/foreach}
			</div>
			<div class="fotos-nav">
				{foreach name="out" item="item" from=$images}
				<div class="item">
					<img src="/pic/catprev{$item}" class="pps_image" />
				</div>
				{/foreach}
			</div>
		</div>
		<div class="descr pps_flex_12 pps_flex_11_view_small">
			<h1>{$element.Name}</h1>
			{if $element.PStatus}
				<div class="status status{$element.PStatus}"
					title="{$element.PStatus_Name}" data-uk-tooltip></div>
			{/if}
			<div class="prices">
				<div class="price">
					<span>{$element.Price}</span>
				</div>
				{if $element.PriceOld}
				<div class="priceOld">
					<span>{$element.PriceOld}</span>
				</div>
				{/if}
			</div>
			<label class="pps pps_button">
				<input type="button" value="Купить"/>
			</label>
		</div>
	</div>
</div>