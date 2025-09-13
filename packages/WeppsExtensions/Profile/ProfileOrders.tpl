<h2>Заказы</h2>
<section class="orders-table">
    <table class="w_table">
        <tbody>
            <tr class="w_table_active">
                <th>Номер</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Оплата</th>
                <th>Доставка</th>
                <th>Статус</th>
            </tr>
            {foreach from=$orders item='item'}
                <tr data-id="{$item.Id}">
                    <td class="pps_right">{$item.Id}</td>
                    <td>{$item.ODate|date_format:'%d.%m.%Y'}</td>
                    <td class="pps_right">{$item.OSum}</td>
                    <td>{$item.OPayment_Name}</td>
                    <td>{$item.ODelivery_Name}</td>
                    <td>{$item.OStatus_Name}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</section>