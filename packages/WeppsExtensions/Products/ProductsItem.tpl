{assign var="images" value=$element.Images_FileUrl|strarr}
<div class="page product pps_flex_max">
	{$element.Id|pps:"Products"}
	<section>
		<div class="product-wrapper">
			<div class="product-media content-block">
				<div class="img-carousel carousel">
					{foreach name="out" item="item" from=$images}
					<div class="img">
						<img src="/pic/catbigv{$item}" class="pps_image" />
					</div>
					{/foreach}
				</div>
			</div>
			<div class="product-title content-block">
				<h1>{$element.Name}</h1>
				{if $element.PStatus}
				<div class="status status{$element.PStatus}" title="{$element.PStatus_Name}" data-uk-tooltip></div>
				{/if}
			</div>
			<div class="product-attributes content-block">attributes attributes attributes attributes attributes </div>
			<div class="product-price">
				<div class="prices content-block">
					<div class="price">
						<span>{$element.Price}</span>
					</div>
					{if $element.PriceOld}
					<div class="price price-before">
						<span>{$element.PriceOld}</span>
					</div>
					{/if}
					<label class="pps pps_button">
						<input type="button" value="Купить"/>
					</label>
				</div>
				
			</div>
			{if $element.Descr}
			<div class="product-text content-block">
				{$element.Descr}
			</div>
			{/if}
		</div>
	</section>
</div>