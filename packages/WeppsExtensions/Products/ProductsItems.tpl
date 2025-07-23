<div class="products-items-wrapper">
	{if $paginatorTpl}
	<div class="content-block">
		{$paginatorTpl}
	</div>
	{/if}
	<div class="products-items w_grid w_3col w_2col_view_medium w_gap_medium">
		{foreach item="item" from=$products}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section>
			{$item.Id|wepps:"Products"}
			{if $item.PStatus}
			<div class="status status{$item.PStatus}" title="{$item.PStatus_Name}"></div>
			{/if}
			<a href="{$item.Url}">
				<span class="img">
					{if $images.0}
					<img src="/pic/catbigv{$images.0}" class="pps_image"/>
					{else}
					<img src="/ext/Template/files/noimage480v.png" class="pps_image"/>
					{/if}
				</span>
				<span class="title">{$item.Name}</span>
			</a>
			<div class="prices-wrapper pps_flex_12 pps_flex pps_flex_row">
				<div class="prices">
					<div class="price"><span>{$item.Price|money}</span></div>
					{if $item.PriceBefore>0}
					<div class="price price-before"><span>{$item.PriceBefore|money}</span></div>
					{/if}
				</div>
				<label class="pps pps_button">
					<input type="button" value="В корзину" class="cart-add" data-id="{$item.Id}"/>
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