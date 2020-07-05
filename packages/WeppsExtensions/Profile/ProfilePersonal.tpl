<div id="message"></div>
<div class="pps_flex pps_flex_row pps_flex_center">
	<form
		action="javascript:sendformwin('setUser','setPersonalForm','/ext/User/Request.php')"
		id="setPersonalForm" class="pps_flex_23">
		<input type="hidden" name="formtype" value="profile"/>
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">
			<div class="pps_flex_11 pps_padding">
					<label class="pps pps_input"><div>ФИО</div> <input type="text"
						value="{$user.Name}" name="name" /> </label>
				</div>
				<div class="pps_flex_11 pps_padding">
					<label class="pps pps_input"><div>Город</div> <input type="text"
						value="{$user.City_Name}" name="city" id="cities"/> </label>
				</div>
				<div class="pps_flex_13 pps_padding">
					<label class="pps pps_input"><div>Индекс</div> <input type="text"
						value="{$user.AddressIndex}" name="addressIndex" /> </label>
				</div>
				<div class="pps_flex_23 pps_padding">
					<label class="pps pps_input"><div>Полный адрес</div> <input
						type="text" value="{$user.Address}" name="address" /> </label>
				</div>
			</div>
			<div class="pps_interval_small"></div>
			<div class="pps_flex pps_flex_row pps_flex_str pps_bg_silver">

				<div
					class="pps_flex_11 pps_flex_11_view_small pps_padding pps_center">
					<label class="pps pps_button"> <input type="submit"
						value="Сохранить" />
					</label>
				</div>
				
			</div>
		</div>
	</form>
</div>