{foreach name="out" item="item" from=$get.filters}
<div><a href="?{if $get.orderby}orderby={$get.orderby}&{/if}field={$get.field}&filter={$item[$get.fieldkey|default:$get.field]}">{$item[$get.fieldname|default:$get.field]}</a></div>
{/foreach}