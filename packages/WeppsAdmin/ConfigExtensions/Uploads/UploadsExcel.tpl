<form class="list-data pps_flex pps_flex_row pps_flex_start pps_flex_row_top controls-area" 
	action="javascript:formSenderWepps.send('excel','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Uploads/Request.php')"
	id="list-data-form">
	<div class="pps_flex_23 pps_flex_11_view_medium pps_border item" data-id="upload">
		<div class="selectUpload pps_flex_12 pps_flex pps_flex_row pps_flex_start">
			<label class="pps pps_select pps_flex_12">
				<select name="source">
					<option value='0'>Цель: </option>
					{foreach name="out" item="item" key="key" from=$source}
					<option value="{$item.Id}">{$item.Name}{if $item.Descr} ({$item.Descr}){/if}</option>
					{/foreach}
				</select>
			</label>
			<label class="pps pps_upload pps_flex_fix">
				<input type="file" name="upload" multiple="multiple"/> <span>Загрузить</span>
			</label>
		</div>
		
		<div class="pps_interval_small"></div>
		{foreach name="o" item="i" key="k" from=$uploaded.upload}
			<p class="fileadd pps_flex_13">
				{$i.title} <a href="" class="file-remove" rel="{$i.url}"><i class="fa fa-remove"></i></a>
			</p>
		{/foreach}
	</div>
	<span class="pps_interval_small"></span>
	<div class="pps_flex_23 pps_flex_11_view_small pps_flex pps_flex_row pps_flex_start pps_border">
		<label class="pps pps_button"><input type="submit" value="Далее"/></label>
	</div>
	<span class="pps_interval_small"></span>
	<div class="pps_flex_23 pps_flex_11_view_small pps_border">
		{if $files.0.Id}
		<div class="title">Последние загрузки</div>
		<div class="pps_interval_small"></div>
		<ul class="pps_list lastUploads">
			{foreach name="out" item="item" from=$files}
			<li class="pps_flex pps_flex_row"><div class="pps_flex_12"><i class="fa fa-file-o"></i> <a href="/f{$item.FileUrl}">{$item.Name}</a></div><div class="descr pps_flex_12">{$item.FileDate|date_format:"%d.%m.%Y"}</div></li>
			{/foreach}
			<li class="pps_flex pps_flex_row"><div class="pps_flex_12"><i class="fa fa-file-o"></i> <a href="/_pps/lists/s_UploadsSource/">Все загрузки</a></div><div class="descr pps_flex_12"></div></li>
		</ul>
	
	{/if}
	</div>
</form>






