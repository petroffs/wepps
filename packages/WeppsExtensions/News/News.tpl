<input type="button" value="ajax popup" onclick="layout2Wepps.win({ size:'small',data:'action=test1',url:'/ext/Addons/Request.php' });"/>
<input type="button" value="plain popup" onclick="layout2Wepps.win({ size:'medium',content: $('#test')});"/>
<input type="button" value="ajaxtoobj" onclick="layout2Wepps.request({ data:'action=test',url:'/ext/Addons/Request.php',obj:$('#test2') });"/>
<input type="button" value="ajaxplain" onclick="layout2Wepps.request({ data:'action=test',url:'/ext/Addons/Request.php' });"/>


<div class="test pps_hide" id="test">
test тестовое окно
</div>

<div class="" id="test2">
test тестовое окно 2
</div>

<div class="elements News">
	<div class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_large">
		{foreach name="out" item="item" from=$elements} {assign var=images
		value=$item.Images_FileUrl|strarr}
		<div class="item pps_flex_13">
			<div class="img">
				<img
					src="{if $images.0}/pic/catbig{$images.0}{else}/pic/catbig/files/lists/DataTbls/6_Image_1337064015_BS16032.jpg{/if}"
					class="pps_image" />
			</div>
			<div
				class="descr pps_padding  pps_relative">
				
				<div class="title">
					<a href="{$item.Url}">{$item.Name}</a>
				</div>
				<div class="descr2">{$item.Announce}</div>
				{$item.Id|pps:"News"}
			</div>
		</div>
		{/foreach}
	</div>
</div>

{$paginatorTpl}
