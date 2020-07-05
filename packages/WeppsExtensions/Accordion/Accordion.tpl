<div class="elements Accordion">
	<div class="items">
		{foreach name="out" item="item" key="key" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div class="item{if $key==0} active{/if}" data-key="{$key}">
			<div class="title">{$item.Name}</div>
			<div class="descr">{$item.Descr}</div>
		</div>
		{/foreach}
	</div>
</div>