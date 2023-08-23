<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_row_str">
	<div class="pps_flex_11 way">
		<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
			<li><a href="/_pps/">Главная</a></li>
			<li>Авторизация</li>
		</ul>
	</div>
	<div class="pps_flex_11 pps_flex pps_flex_col pps_padding centercontent">
		<div class="pps_shadow pps_flex_max pps_padding">
			<h2>{$content.Name}</h2>
			<form class="list-data pps_flex pps_flex_row pps_flex_center" action="javascript:formWepps.send('auth','auth-form','/packages/WeppsAdmin/Admin/Request.php')"
				id="auth-form">
				<div class="pps_flex_13 pps_flex_12_view_medium pps_flex_11_view_small pps_flex pps_flex_row pps_flex_start pps_flex_margin">
					<label class="pps pps_input pps_flex_11"><input type="email" name="email" value="" id="login"/></label>
					<label class="pps pps_input pps_flex_11"><input type="password" name="passw" value=""/></label>
					<label class="pps pps_button"><input type="button" value="Войти"/></label>
				</div>
			</form>
		</div>
	</div>
</div>
