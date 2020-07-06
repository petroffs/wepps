<div id="message"></div>
<div class="settings pps_flex pps_flex_row pps_flex_center">
	<form
		action="javascript:formSenderWepps.send('setSettings','emailForm','/ext/User/Request.php')"
		id="emailForm" class="pps_flex_23">
		<input type="hidden" name="code" value=""/>
		<div id="setEmailContent">
			<div class="text">На E-mail <i></i> отправлен код. Введите его в поле и нажмите &laquo;Далее&raquo;</div>
			<div class="form">
				<label class="pps pps_input"> <div>Код</div> <input type="text"
					value="" id="setEmailCode"/>
				</label>
			</div>
		</div>
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_row_bottom pps_flex_str pps_bg_silver">
				<div class="pps_flex_23 pps_padding">
					<label class="pps pps_input"><div>E-mail</div> <input type="text"
						value="{$user.Email}" name="email" id="setEmailInput"/> </label>
				</div>
				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_button"> <input type="submit"
						value="Изменить" id="setEmailButton___1"/>
					</label>
				</div>
			</div>
		</div>
	</form>
	<form
		action="javascript:formSenderWepps.send('setSettings','phoneForm','/ext/User/Request.php')"
		id="phoneForm" class="pps_flex_23">
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_row_bottom pps_flex_str pps_bg_silver">
				<div class="pps_flex_23 pps_padding">
					<label class="pps pps_input"><div>Телефон</div> <input type="text"
						value="{$user.Phone}" name="phone" /> </label>
				</div>

				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_button"> <input type="submit"
						value="Изменить" />
					</label>
				</div>
			</div>
		</div>
	</form>
	<form
		action="javascript:formSenderWepps.send('setSettings','passForm','/ext/User/Request.php')"
		id="passForm" class="pps_flex_23">
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_row_bottom pps_flex_str pps_bg_silver">
				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_input"><div>Пароль</div> <input
						type="password" value="" name="pass" /> </label>
				</div>
				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_input"><div>Повторите пароль</div> <input
						type="password" value="" name="pass2" autocomplete="off" /> </label>
				</div>
				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_button"> <input type="submit"
						value="Изменить" />
					</label>
				</div>
			</div>
		</div>
	</form>
</div>