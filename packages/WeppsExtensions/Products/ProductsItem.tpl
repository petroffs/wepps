{assign var="images" value=$element.Images_FileUrl|strarr}
<div class="page product">
	<section>
		<div class="product-wrapper">
			<div class="pps_flex pps_flex_row pps_flex_row_str">
				<div class="pps_flex_12">
					<div class="img-carousel carousel">
						{foreach name="out" item="item" from=$images}
						<div class="img">
							<img src="/pic/catbigv{$item}" class="pps_image" />
						</div>
						{/foreach}
					</div>
				</div>
				<div class="pps_flex_14">
					<h1>{$element.Name}</h1>
					{if $element.PStatus}
					<div class="status status{$element.PStatus}" title="{$element.PStatus_Name}" data-uk-tooltip></div>
					{/if}
				</div>
				<div class="pps_flex_14">
					<div class="prices-wrapper">
						<div class="prices">
							<div class="price">
								<span>{$element.Price}</span>
							</div>
							{if $element.PriceOld}
							<div class="price-before">
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
		</div>
	</section>
</div>