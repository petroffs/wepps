<div class="pps_flex_11 way">
	<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
		<li><a href="/_pps/">Главная</a></li>
		<li>Авторизация</li>
	</ul>
</div>
<div class="pps_flex pps_flex_row pps_flex_row_str">
	<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_center centercontent">
		<div class="pps_flex_13 pps_flex_23_view_medium pps_flex_11_view_small pps_padding pps_bg_silver">
			<h2>{$content.Name}</h2>
			<form class="list-data pps_flex pps_flex_row" action="javascript:formWepps.send('sign-in','sign-in-form','/packages/WeppsAdmin/Admin/Request.php')"
				id="sign-in-form">
				<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_start pps_flex_margin">
					<label class="pps pps_input pps_flex_11"><input type="text" name="login"/></label>
					<label class="pps pps_input pps_flex_11"><input type="password" name="password"/></label>
					<label class="pps pps_button"><input type="submit" value="Войти"/></label>
				</div>
			</form>
		</div>
	</div>
</div>
