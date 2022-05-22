{foreach name="out" item="item" from=$filters}
<div><a href="?{if $get.orderby}orderby={$get.orderby}&{/if}field={$get.field}&filter={$item[$fieldkey|default:$get.field]}">{$item[$fieldname|default:$get.field]}</a></div>
{/foreach}