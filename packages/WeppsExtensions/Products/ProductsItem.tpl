{assign var="images" value=$element.Images_FileUrl|strarr}
<div class="page product w_flex_max">
	{$element.Id|wepps:"Products"}
	<section>
		<div class="product-wrapper">
			<div class="product-media content-block">
				{if $element.PStatus}
					<div class="status status{$element.PStatus}" title="{$element.PStatus_Name}"></div>
				{/if}
				<div class="swiper swiper-gallery-main">
					<div class="swiper-wrapper">
						{foreach name="out" item="item" from=$images}
							<div class="swiper-slide" data-color="default">
								<img src="/pic/mediumv{$item}" class="w_image" />
							</div>
						{/foreach}
					</div>
					{if count($images) > 1}
						<div class="swiper-button-prev"></div>
						<div class="swiper-button-next"></div>
					{/if}
				</div>
				{if count($images) > 1}
					<div class="swiper swiper-gallery-thumbs">
						<div class="swiper-wrapper">
							{foreach name="out" item="item" from=$images}
								<div class="swiper-slide" data-color="default">
									<img src="/pic/mediumv{$item}" class="w_image" />
								</div>
							{/foreach}
						</div>
					</div>
					<!-- Переключатель галерей по цветам -->
					<div class="gallery-switcher content-block">
						{* <span class="gallery-label">{$element.Name}</span> *}
						<div class="gallery-colors-menu w_flex w_flex w_flex_row w_flex_center w_flex_margin_small"></div>
					</div>
				{/if}
			</div>
			<div class="product-title content-block">
				<h1>{$element.Name}</h1>
			</div>
			<div class="product-attributes content-block">
				{foreach item="item" from=$element.W_Attributes}
					<section class="w_grid w_3col">
						<div class="title w_1scol">{$item[0]['PName']}</div>
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
					{if $element.W_Variations}
						{assign var="isDefaultGroup" value=false}
						{foreach from=$element.W_Variations item="group" name="variations"}
							{assign var="firstInGroup" value=$group[0]}
							{if empty($firstInGroup.color)}
								{assign var="isDefaultGroup" value=true}
								<div class="w_interval"></div>
								<label class="w_label w_button">
									{if $element.Id|in_array:$cartMetrics.items}
										<a href="/cart/" class="cart-exists"></a>
									{/if}
									<input type="button" value="В корзину" class="cart-add" data-id="{$element.Id}"
										data-idv="{$firstInGroup.id}" {if $firstInGroup.stocks<=0} disabled{/if}
										autocomplete="off" />
								</label>
							{else}
								{if $group@first}
									<div class="w_interval"></div>
								{/if}
								<section data-color="{$firstInGroup.color|escape}">
									<div class="price-title">{$firstInGroup.color}</div>
									{foreach from=$group item="item"}
										<a href=""
											class="w_button cart-add-v{if $item.stocks<=0} w_disabled{/if}{if $item.id|in_array:$cartMetrics.itemsv} cart-add-v-exists{/if}"
											data-id="{$item.id}" data-color="{$firstInGroup.color}">{$item.size}</a>
									{/foreach}
								</section>
							{/if}
						{/foreach}
						{if !$isDefaultGroup}
							<div class="w_interval"></div>
							<label class="w_label w_button">
								{if $element.Id|in_array:$cartMetrics.items}
									<a href="/cart/" class="cart-exists"></a>
								{/if}
								<input type="button" value="В корзину" class="cart-add" data-id="{$element.Id}" data-idv="-1"
									disabled autocomplete="off" />
							</label>
						{/if}
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