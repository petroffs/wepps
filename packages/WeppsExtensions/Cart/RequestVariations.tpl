<h1>{$element.Name}</h1>
<p>⚡ Выберите размер</p>
<div class="w_interval"></div>
<div class="product-price">
    <div class="prices">
        {foreach from=$element.W_Variations item="item" key="key" name="out"}
            <section>
                <div class="price-title">{$key}</div>
                {foreach from=$item item="i" name="o"}
                    <a href="" class="pps_button cart-add-v{if $i.Stocks<=0} pps_disabled{/if}" data-id="{$i.Id}">{$i.Size}</a>
                {/foreach}
            </section>
        {/foreach}
        <div class="pps_interval"></div>
        <label class="pps pps_button">
            <input type="button" value="В корзину" class="cart-add" data-id="{$element.Id}" data-idv="-1" disabled
                autocomplete="off" />
        </label>
    </div>
</div>
{$get.cssjs}