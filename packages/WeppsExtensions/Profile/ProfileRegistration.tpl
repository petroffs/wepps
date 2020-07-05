<div class="pps_flex pps_flex_row pps_flex_center">
	<form
		action="javascript:sendformwin('addUser','addUserForm','/ext/User/Request.php')"
		id="addUserForm" class="pps_flex_23">
		<input type="hidden" name="formtype" value="profile"/>
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">
			<div class="pps_flex_11 pps_padding">
					<label class="pps pps_input"><div>ФИО</div> <input type="text"
						value="" name="name" /> </label>
				</div>
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>E-mail</div> <input type="text"
						value="" name="email" /> </label>
				</div>
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>Телефон</div> <input type="text"
						value="" name="phone" /> </label>
				</div>
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>Пароль</div> <input
						type="password" value="" name="pass" /> </label>
				</div>
				<div class="pps_flex_12 pps_padding">
					<label class="pps pps_input"><div>Повторите пароль</div> <input
						type="password" value="" name="pass2" /> </label>
				</div>
			</div>
			<div class="pps_interval_small"></div>
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">

				<div
					class="pps_flex_12 pps_flex_11_view_small pps_padding pps_center">
					<label class="pps pps_button"> <input type="submit"
						value="Отправить данные" />
					</label>
				</div>
				<div
					class="service pps_flex_12 pps_flex_11_view_small pps_flex pps_flex_row pps_flex_start pps_padding pps_center">
					<a href="/profile/"
						class="pps_flex_13 pps_flex_11_view_medium">Личный кабинет</a> <a
						href="/profile/passback.html"
						class="pps_flex_13 pps_flex_11_view_medium">Забыл пароль</a>
				</div>
			</div>
		</div>
	</form>
</div>