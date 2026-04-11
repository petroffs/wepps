<h1>{$element.Name}</h1>
<p>⚡ Выберите размер</p>
<div class="w_interval"></div>
<div class="product-price">
    <div class="prices">
        {foreach from=$element.W_Variations item="group" name="variations"}
            {assign var="firstInGroup" value=$group[0]}
            <section>
                <div class="price-title">{$firstInGroup.color|default:'-'}</div>
                {foreach from=$group item="item"}
                    <a href="" class="w_button cart-add-v{if $item.stocks<=0} w_disabled{/if}{if $item.id|in_array:$cartMetrics.itemsv} cart-add-v-exists{/if}" data-id="{$item.id}">{$item.size}</a>
                {/foreach}
            </section>
        {/foreach}
        <div class="w_interval"></div>
        <label class="w_label w_button">
            {if $element.Id|in_array:$cartMetrics.items}
                <a href="/cart/" class="cart-exists"></a>
            {/if}
            <input type="button" value="В корзину" class="cart-add" data-id="{$element.Id}" data-idv="-1" disabled
                autocomplete="off" />
        </label>
    </div>
</div>
{$get.cssjs}