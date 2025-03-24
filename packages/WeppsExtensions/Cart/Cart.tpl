<div class="page cart">
	<section>
		<div class="w_grid w_3col w_gap_medium">
			<div class="w_2scol w_3scol_view_medium">
				<div class="content-block">
					<h1>Корзина</h1>
					<label class="pps pps_checkbox"><input type="checkbox"/><span>Выбрать все</span></label>
				</div>
				<div class="content-block cart-items">
					{foreach item="item" from=$cartSummary.items}
					<section data-id="{$item.id}">
						<div class="cart-checkbox"><label class="pps pps_checkbox"><input type="checkbox" name="item" value={$item.id}/><span></span></label></div>
						<div class="cart-image"><img src="/pic/lists{$item.image}"/></div>
						<div class="cart-title">
							<a href="{$item.url}" class="title">{$item.name}</a>
							<a href=""><i class="bi bi-trash3"></i> Удалить</a>
							<a href=""><i class="bi bi-bookmarks"></i> В избранное</a>
						</div>
						<div class="cart-quantity">
							<div class="pps pps_minmax" data-value="{$item.quantity}" data-name="quantity">
								<button>
									<span></span>
								</button>
								<input type="text" name="quantity" value="{$item.quantity}" maxlength="3"/>
								<button>
									<span></span>
								</button>
							</div>
							<div class="cart-price">
								<span class="price"><span>{$item.price|money}</span></span><span> за 1 шт.</span>
							</div> 
						</div>
						
						<div class="cart-sum price"><span>{$item.sum|money}</span></div>
					</section>
					{/foreach}
				</div>
			</div>
			<div class="w_3scol_view_medium">
				<div class="content-block cart-total"><h2>Детали заказа</h2>
					<label class="pps pps_button"><input type="button" value="Перейти к оформлению"></label>
				</div>
			</div>
		</div>
	</section>
</div>