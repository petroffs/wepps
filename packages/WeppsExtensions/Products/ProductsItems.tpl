<div class="products-items-wrapper">
	<div class="products-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
		{foreach name="out" item="item" from=$products}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section class="pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small">
			{$item.Id|pps:"Products"}
			{if $item.PStatus}
			<div class="status status{$item.PStatus}" title="{$item.PStatus_Name}"></div>
			{/if}
			<a href="{$item.Url}">
				<span class="img">
					{if $images.0}
					<img src="/pic/catbigv{$images.0}" class="pps_image"/>
					{else}
					<img src="/ext/Template/files/noimage640.png" class="pps_image"/>
					{/if}
				</span>
				<span class="title">{$item.Name}</span>
			</a>
			<div class="prices-wrapper pps_flex_12 pps_flex pps_flex_row">
				<div class="prices">
					<div class="price">{$item.Price|money}</div>
					{if $item.PriceOld}
					<div class="price price-before">{$item.PriceOld|money}</div>
					{/if}
				</div>
				<label class="pps pps_button"> <input type="button"
					class="addCart" value="В корзину" data-id="{$item.Id}" />
				</label>
			</div>
		</section>
		{/foreach}
	</div>
	{if $paginatorTpl}
	<div class="pps_interval_medium"></div>
	<div class="content-block">
		{$paginatorTpl}
	</div>
	{/if}
</div>