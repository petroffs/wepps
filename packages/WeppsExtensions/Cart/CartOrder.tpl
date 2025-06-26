<div class="w_grid w_3col w_gap_medium">
    <div class="w_2scol w_3scol_view_medium">
        <div class="content-block">
            <h1>Ваш заказ оформлен!</h1>
            {assign var="jdata" value=$order.JData|json_decode:1}
            {if $order.Address}
                {assign var="address" value=' ('|cat:$order['PostalCode']|cat:', '|cat:$order['Address']|cat:')'}
            {/if}
            <p><strong>Заказ №{$order.Id} от {$order.ODate|date_format:'%d.%m.%Y'} на сумму <span class="price"><span>{$order.OSum|money:2}</span></span></strong></p>
            <ul>
                <li>Статус: {$order.OStatus_Name}</li>
                <li>Доставка: {$jdata.delivery.tariff.title}{$address}</li>
                <li>Оплата: {$jdata.payments.tariff.title}</li>
            </ul>
            <p><strong>Покупатель</strong></p>
            <ul>
                <li>{$order.Name}</li>
                <li>{$order.Phone}</li>
                <li>{$order.Email}</li>
            </ul>
            <p><b>Что дальше?</b></p>
            <ul>
                <li>Отслеживайте статус заказа в Личном кабинете → раздел «Мои заказы»</li>
                <li>Вернуться на главную, чтобы продолжить покупки → Главная страница</li>
                <li>Акции и новинки, которые могут вас заинтересовать: Спецпредложения</li>
            </ul>
            <p>Если вам нужна помощь, обратитесь в <a href="/contacts/" target="_blank" rel="noopener noreferrer">службу
                    поддержки</a>. Мы обязательно поможем!</p>
            <p>Спасибо, что выбрали наш интернет-магазин.</p>
        </div>
    </div>
    {if $operationsTpl}
        {$operationsTpl}
    {/if}
</div>
