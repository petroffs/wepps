<div class="pps_flex pps_flex_row pps_flex_center">
	<form
		action="javascript:sendformwin('getAuth','getAuthForm','/ext/User/Request.php')"
		id="getAuthForm" class="pps_flex_23">
		<div class="cartForm">
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>Логин</div> <input type="text"
						value="" name="email" /> </label>
				</div>
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>Пароль</div> <input
						type="password" value="{$user.Email}" name="pass" /> </label>
				</div>
			</div>
			<div class="pps_interval_small"></div>
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">

				<div
					class="pps_flex_12 pps_flex_11_view_small pps_padding pps_center">
					<label class="pps pps_button"> <input type="submit"
						value="Войти в личный кабинет" />
					</label>
				</div>
				<div
					class="service pps_flex_12 pps_flex_11_view_small pps_flex pps_flex_row pps_flex_start pps_padding pps_center">
					<a href="/profile/reg.html"
						class="pps_flex_13 pps_flex_11_view_medium">Регистрация</a> <a
						href="/profile/passback.html"
						class="pps_flex_13 pps_flex_11_view_medium">Забыл пароль</a>
				</div>
			</div>
		</div>
	</form>
</div>