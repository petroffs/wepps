{assign var=images value=$get.product.Data.Image_FileUrl|strarr}
<div class="modalProduct pps_flex pps_flex_row pps_flex_row_str pps_flex_margin_medium">
	<div class="pps_flex_11">
		<h1><i class="fa fa-trash"></i> Подтвердите удаление из корзины</h1>
	</div>
	<div class="img pps_flex_14">
		<img src="{$images.0|default:'/pic/catprev/files/template/default.png'}" class="pps_image"/>
	</div>
	<div class="descr pps_flex_34 pps_flex pps_flex_col">
		<div class="title">{$get.product.Data.ProductType_NameOsn} {$get.product.Data.Name}</div>
		
		<div class="chars">
			<div class="item pps_flex pps_flex_row pps_flex_str pps_flex_start">
				<div class="itm pps_flex_13 pps_flex_12_view_medium">Цвет</div>
				<div class="val color pps_flex_23 pps_flex_12_view_medium">{$get.product.Data.OptionColor}</div>
			</div>
			<div class="item pps_flex pps_flex_row pps_flex_str pps_flex_start">
				<div class="itm pps_flex_13 pps_flex_12_view_medium">Размер</div>
				<div class="val size pps_flex_23 pps_flex_12_view_medium">
					{foreach name='size' item='item' from=$get.product.Data.OptionSize|explode}
					<span>{$item}</span>
					{/foreach}
				</div>
			</div>
			<div class="item pps_flex pps_flex_row pps_flex_str pps_flex_start">
				<div class="itm pps_flex_13 pps_flex_12_view_medium">Количество</div>
				<div class="val size pps_flex_23 pps_flex_12_view_medium">
					
					<label class="pps pps_select">
						<select id="qtychange" disabled="disabled">
							<option value="{$get.qty}">{$get.qty}</option>
						</select>
					</label>
				</div>
			</div>
			<div class="item pps_flex pps_flex_row pps_flex_str pps_flex_start">
				<div class="itm qtyfinal pps_flex_13 pps_flex_12_view_medium">Цена за {$get.product.Data.OptionQty * $get.qty} ед.</div>
				<div class="val price pricefinal pps_flex_23 pps_flex_12_view_medium"><span>{$get.product.PriceAmount|money}</span></div>
			</div>
		</div>
		<div class="btn pps_flex pps_flex_row pps_flex_start pps_flex_margin">
			<label class="pps pps_button">
				<input type="button" value="Отменить" id="layerClose"/>
			</label>
			<label class="pps pps_button pps_button2">
				<input type="button" value="Удалить из корзины" id="removeApply" data-id="{$get.id}"/>
			</label>
		</div>
	</div>
</div>

<script type="text/javascript">
	var id = '{$get.id}';
</script>
{$get.cssjs}