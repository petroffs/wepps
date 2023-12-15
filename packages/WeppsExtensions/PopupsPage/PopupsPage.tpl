<label class="pps pps_button">
	<input type="button" value="plain popup" onclick="layoutWepps.win({ size:'medium',content: $('#test')});"/>
</label>
<label class="pps pps_button">
	<input type="button" value="ajax popup" onclick="layoutWepps.win({ size:'small',data:'action=test1',url:'/ext/PopupsPage/Request.php' });"/>
</label>
<label class="pps pps_button">
	<input type="button" value="ajax to obj" onclick="layoutWepps.request({ data:'action=test',url:'/ext/PopupsPage/Request.php',obj:$('#test2') });"/>
</label>
<label class="pps pps_button">
	<input type="button" value="ajax hidden" onclick="layoutWepps.request({ data:'action=test',url:'/ext/PopupsPage/Request.php' });"/>
</label>

<div class="pps_interval"></div>
<div class="test pps_hide" id="test">
Окно с загруженными данными для отображения в попапе
</div>

<div class="" id="test2">
Окно для загрузки ответа/response
</div>