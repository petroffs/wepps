
<div class="cart default pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
	<div class="pps_flex_23 cartabout pps_flex_11_view_medium">
		<div class="block-bg1">
			<div class="pps_padding">
				{foreach name="out" item="item" key="key" from=$cartSummary.cart} {assign
				var=images value=$item.Data.Image_FileUrl|strarr}
				<div class="item block-bg2 pps_flex pps_flex_row pps_flex_row_str pps_flex_margin_medium" id="{$key}">
					<div class="pps_flex_16 pps_flex_13_view_small pps_flex pps_flex_row pps_flex_center">
						<div class="img pps_flex_23 pps_flex_11_view_medium">
							<img
								src="{if $images.0}{$images.0}{else}/files/template/default.png{/if}"
								class="pps_image" />
						</div>
					</div>
					<div class="pps_flex_56 pps_flex_23_view_small ">
						<div
							class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
							<div class="pps_flex_34">
								<div class="title">{$item.Data.ProductType_NameOsn} {$item.Data.Name}</div>
								<div class="color">{$item.Data.OptionColor}</div>
							</div>
							<div class="pps_flex_14 pps_right">
								<a href="{$item.Data.Url}" data-id="{$item.Data.Id}" class="edit"><i class="fa fa-edit"></i></a>
								<a href="" data-id="{$key}" class="remove"><i class="fa fa-trash"></i></a>
							</div>
						</div>
						<div
							class="params pps_flex pps_flex_row pps_flex_margin">
							<div class="price priceone pps_right_view_small pps_flex_14 pps_flex_12_view_small"><span>{$item.Data.PriceAmount|money}</span></div>
							<div class="sizes pps_flex_14 pps_flex_12_view_small ">
								{foreach name='o' item='i' from=$item.Data.OptionSize|explode}
									<span>{$i}</span>
								{/foreach}
							</div>
							<div class="qty pps_flex_14 pps_flex_12_view_small ">
								<label class="pps pps_select"> <select class="qtyselect" data-id="{$key}"> {for $qty=$item.QtyMin
										to $item.QtyMax}
										<option value="{$qty}" {if $item.Qty==$qty}
											selected="selected"{/if}>{$qty}</option> {/for}
								</select>
								</label>
							</div>
							<div class="price priceall pps_right pps_flex_14 pps_flex_12_view_small"><span>{$item.PriceAmount|money}</span></div>
						</div>
					</div>
				</div>
				{/foreach}

				<div
					class="item block-bg1 pps_flex pps_flex_row pps_flex_row_str pps_flex_margin_medium">
					<div class="pps_flex_15"></div>
					<div class="pps_flex_35">
						<div
							class="pps_flex pps_flex_row">
							<div class="price title pps_right pps_flex_11">Общая стоимость товаров: <span>{$cartSummary.priceAmount|money}</span></div>
						</div>
					</div>
				</div>
				<div class="pps_right pps_flex_45">
					<label class="pps pps_button pps_button2"> <input type="button"
						value="Оформить заказ" id="orderCart" data-auth="{if $smarty.session.user.Id}1{else}0{/if}"/>
					</label>
				</div>
			</div>
		</div>
		{$profileStaffTpl}
	</div>
	{$cartAboutTpl}
</div>
