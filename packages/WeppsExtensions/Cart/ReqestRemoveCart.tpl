{assign var=images value=$get.product.Image_FileUrl|strarr}
<div class="uk-flex uk-flex-middle uk-flex-space-between modalProduct">
	<div class="uk-width-1-1 uk-width-small-1-3 img">
		<img src="/pic/catprev{$images.0|default:'/files/template/default.png'}"/>
	</div>
	<div class="uk-width-1-1 uk-width-small-2-3 descr">
	
		<div class="title">{$get.product.Name}</div>
		<div class="price">{$get.product.Price}</div>
	</div>
</div>

<script type="text/javascript">
	var id = '{$get.id}';
</script>
{$get.cssjs}