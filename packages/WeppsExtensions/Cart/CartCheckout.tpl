<div class="w_grid w_3col w_gap_medium">
	<div class="w_2scol w_3scol_view_medium">
		<div class="content-block">
			<h1>Корзина</h1>
			<label class="pps pps_checkbox"><input type="checkbox" id="cart-check-all" checked="checked" autocomplete="off"/><span>Выбрать все</span></label>
		</div>
		<div class="content-block cart-items">
			{foreach item="item" from=$cartSummary.items}
			<section data-id="{$item.id}">
				<div class="cart-checkbox"><label class="pps pps_checkbox"><input type="checkbox" name="cart-check" value="{$item.id}" {if $item.active==1}checked="checked"{/if} autocomplete="off"/><span></span></label></div>
				<div class="cart-image"><img src="/pic/lists{$item.image}"/></div>
				<div class="cart-title">
					<a href="{$item.url}" class="title">{$item.name}</a>
					<a href="" class="cart-remove"><i class="bi bi-trash3"></i> <span>Удалить</span></a>
					<a href="" class="cart-favorite{if $item.id|in_array:$cartFavorites} active{/if}"><i class="bi bi-bookmarks"></i> <span>Избранное</span></a>
				</div>
				<div class="cart-quantity">
					<div class="pps pps_minmax" data-value="{$item.quantity}" data-name="quantity">
						<button class="sub">
							<span></span>
						</button>
						<input type="text" name="quantity" value="{$item.quantity}" maxlength="3" min="1" max="20" autocomplete="off"/>
						<button class="add">
							<span></span>
						</button>
					</div>
					<div class="cart-price">
						<span class="price"><span>{$item.price|money}</span> за&nbsp;1&nbsp;шт.</span>{*<span> за 1 шт.</span>*}
					</div> 
				</div>
				<div class="cart-sum price"><span>{$item.sum|money}</span></div>
			</section>
			{/foreach}
		</div>
	</div>
	<div class="w_3scol_view_medium">
		<div class="content-block cart-total"><h2>Детали заказа</h2>
			<label class="pps pps_button pps_button_important"><input type="button" value="Перейти к оформлению"></label>
			<div class="w_interval"></div>
			<label class="pps pps_button"><input type="button" value="Перейти к оформлению"></label>
			<div class="w_interval"></div>
			<label class="pps pps_button"><button>Перейти к оформлению</button></label>
		</div>
	</div>
</div>