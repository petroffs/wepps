<form class="list-data w_flex w_flex_row w_flex_start w_flex_row_top controls-area"
	action="javascript:formWepps.send('excel','list-data-form','/packages/WeppsAdmin/ConfigExtensions/Uploads/Request.php')"
	id="list-data-form">
	<div class="item w_rounded w_flex_23 w_flex_11_view_medium w_border" data-id="upload">
		<div class="selectUpload w_flex_12 w_flex w_flex_row w_flex_start w_flex_margin">
			<label class="w_label w_select w_flex_12">
				<select name="source">
					<option value='0'>Цель: </option>
					{foreach name="out" item="item" key="key" from=$source}
						<option value="{$item.Id}">{$item.Name}{if $item.Descr} ({$item.Descr}){/if}</option>
					{/foreach}
				</select>
			</label>
			<label class="w_label w_upload w_flex_fix">
				<input type="file" name="upload" multiple="multiple" /> <span>Загрузить</span>
			</label>
		</div>
		<div class="w_interval_small"></div>
		{foreach name="o" item="i" key="k" from=$uploaded.upload}
			<p class="fileadd w_flex_11">
				{$i.title} <a href="" class="file-remove" rel="{$i.url}"><i class="fa fa-remove"></i></a>
			</p>
		{/foreach}
	</div>
	<span class="w_interval"></span>
	<div class="w_rounded w_flex_23 w_flex_11_view_small w_flex w_flex_row w_flex_start w_border">
		<label class="w_label w_button"><input type="submit" value="Далее" /></label>
	</div>
	<span class="w_interval"></span>
	<div class="lastUploads w_rounded w_flex_23 w_flex_11_view_small w_border">
		{if $files.0.Id}
			<div class="title">Последние загрузки</div>
			<div class="w_interval_small"></div>
			<ul class="w_list lastUploads">
				{foreach name="out" item="item" from=$files}
					<li class="w_flex w_flex_row">
						<div class="w_flex_12"><i class="fa fa-file-o"></i> <a href="/f{$item.FileUrl}">{$item.Name}</a></div>
						<div class="descr w_flex_12">{$item.FileDate|date_format:"%d.%m.%Y"}</div>
					</li>
				{/foreach}
				<li class="w_flex w_flex_row">
					<div class="w_flex_12"><i class="fa fa-file-o"></i> <a href="/_wepps/lists/s_UploadsSource/">Все
							загрузки</a></div>
					<div class="descr w_flex_12"></div>
				</li>
			</ul>

		{/if}
	</div>
</form>