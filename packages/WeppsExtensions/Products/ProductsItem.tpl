{assign var="images" value=$element.Images_FileUrl|strarr}
<div class="page product pps_flex_max">
	{$element.Id|wepps:"Products"}
	<section>
		<div class="product-wrapper">
			<div class="product-media content-block">
				{if $element.PStatus}
				<div class="status status{$element.PStatus}" title="{$element.PStatus_Name}" data-uk-tooltip></div>
				{/if}
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
			</div>
			<div class="product-attributes content-block">
				{foreach item="item" from=$element.W_Attributes}
				<section class="w_grid w_3col">
					<div class="title w_1scol">{$item[0]['PropertyName']}</div>
					<div class="text w_2scol">
						{foreach item="i" from=$item}
						<span>{$i.PValue}</span>
						{/foreach}
					</div>
				</section>
				{/foreach}
			</div>
			<div class="product-price">
				<div class="prices content-block">
					<div class="price price-current">
						<span>{$element.Price}</span>
					</div>
					{if $element.PriceBefore>0}
					<div class="price price-before">
						<span>{$element.PriceBefore}</span>
					</div>
					{/if}
					
					{if $element.W_VariantsGroup}
					{foreach from=$element.W_VariantsGroup item="item" key="key" name="out"}
						<div>{$key}</div>
						{foreach from=$item item="i" name="o"}
						<div>--- {$i.Sku}</div>
						{/foreach}
					{/foreach}
					<label class="pps pps_button">
						<input type="button" value="Купить V" class="cart-vars-add" data-id="{$element.Id}"/>
					</label>	
					{else}
					<label class="pps pps_button">
						<input type="button" value="Купить" class="cart-add" data-id="{$element.Id}"/>
					</label>
					{/if}
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